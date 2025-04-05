<!DOCTYPE html>
<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_loja";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default client ID if not set
if (!isset($_SESSION['ID_cliente'])) {
    $_SESSION['ID_cliente'] = 1;
}

// Handle search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if (!empty($search_query)) {
    $search_condition = " AND p.marca LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

// Handle adding to cart
if (isset($_POST['add_to_cart'])) {
    $ID_produto = $_POST['ID_produto'];
    $ID_cliente = $_SESSION['ID_cliente'];
    
    // Check stock
    $stmt = $conn->prepare("SELECT estoque FROM tb_produtos WHERE ID_produto = ?");
    $stmt->bind_param("i", $ID_produto);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row && $row['estoque'] > 0) {
        // Get or create a cart
        $sql_cart = "SELECT ID_carrinho FROM tb_carrinho WHERE ID_cliente = ? AND data_criacao > NOW() - INTERVAL 1 DAY ORDER BY data_criacao DESC LIMIT 1";
        $stmt_cart = $conn->prepare($sql_cart);
        $stmt_cart->bind_param("i", $ID_cliente);
        $stmt_cart->execute();
        $result_cart = $stmt_cart->get_result();
        $ID_carrinho = null;
        
        if ($result_cart->num_rows > 0) {
            $row_cart = $result_cart->fetch_assoc();
            $ID_carrinho = $row_cart['ID_carrinho'];
        } else {
            $sql_insert_cart = "INSERT INTO tb_carrinho (ID_cliente) VALUES (?)";
            $stmt_insert_cart = $conn->prepare($sql_insert_cart);
            $stmt_insert_cart->bind_param("i", $ID_cliente);
            $stmt_insert_cart->execute();
            $ID_carrinho = $conn->insert_id;
            $stmt_insert_cart->close();
        }
        $stmt_cart->close();

        // Add or update item in cart
        if ($ID_carrinho) {
            $sql_item = "INSERT INTO tb_itens_carrinho (ID_carrinho, ID_produto, quantidade)
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE quantidade = quantidade + 1";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("ii", $ID_carrinho, $ID_produto);
            $stmt_item->execute();
            $stmt_item->close();

            // Decrease stock
            $sql_stock = "UPDATE tb_produtos SET estoque = estoque - 1 WHERE ID_produto = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("i", $ID_produto);
            $stmt_stock->execute();
            $stmt_stock->close();
        }
    }
    $stmt->close();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Handle liking/unliking a product
if (isset($_POST['toggle_like'])) {
    $ID_produto = $_POST['ID_produto'];
    $ID_cliente = $_SESSION['ID_cliente'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tb_curtidas WHERE ID_cliente = ? AND ID_produto = ?");
    $stmt->bind_param("ii", $ID_cliente, $ID_produto);
    $stmt->execute();
    $liked = $stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();

    if ($liked) {
        $stmt = $conn->prepare("DELETE FROM tb_curtidas WHERE ID_cliente = ? AND ID_produto = ?");
    } else {
        $stmt = $conn->prepare("INSERT INTO tb_curtidas (ID_cliente, ID_produto) VALUES (?, ?)");
    }
    $stmt->bind_param("ii", $ID_cliente, $ID_produto);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Handle deleting an item from the cart
if (isset($_POST['delete_item'])) {
    $ID_item = $_POST['ID_item'];

    $conn->begin_transaction();

    try {
        $stmt_get = $conn->prepare("SELECT ID_produto, quantidade FROM tb_itens_carrinho WHERE ID_item = ?");
        $stmt_get->bind_param("i", $ID_item);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $item_data = $result_get->fetch_assoc();
        $stmt_get->close();

        if ($item_data) {
            $ID_produto = $item_data['ID_produto'];
            $quantidade = $item_data['quantidade'];

            $stmt_delete = $conn->prepare("DELETE FROM tb_itens_carrinho WHERE ID_item = ?");
            $stmt_delete->bind_param("i", $ID_item);
            $stmt_delete->execute();
            $stmt_delete->close();

            $stmt_update = $conn->prepare("UPDATE tb_produtos SET estoque = estoque + ? WHERE ID_produto = ?");
            $stmt_update->bind_param("ii", $quantidade, $ID_produto);
            $stmt_update->execute();
            $stmt_update->close();

            $conn->commit();
        } else {
            $conn->rollback();
        }
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Get product type filter
$selected_tipo_id = isset($_GET['tipo']) ? (int)$_GET['tipo'] : null;
$selected_tipo_name = "Todos os Produtos";
if ($selected_tipo_id) {
    $stmt_title = $conn->prepare("SELECT tipo FROM tb_tipo WHERE ID_tipo = ?");
    $stmt_title->bind_param("i", $selected_tipo_id);
    $stmt_title->execute();
    $result_title = $stmt_title->get_result();
    if ($result_title->num_rows > 0) {
        $row_title = $result_title->fetch_assoc();
        $selected_tipo_name = ucfirst(htmlspecialchars($row_title["tipo"]));
    }
    $stmt_title->close();
}
?>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
    <link rel="shortcut icon" href="redo.png" type="image/x-icon">
    <style>
        /* Estilos CSS existentes... */
        
        /* Novo estilo para a barra de pesquisa */
        .search-container {
            display: flex;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-button {
            padding: 10px 20px;
            background-color: #000;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .search-button:hover {
            background-color: #333;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-img {
            height: 50px;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-container">
        <div>
            <span>Bem-vindo à YSL</span>
        </div>
        <div class="top-links">
            <a href="#">Meus Pedidos</a>
            <a href="#">Atendimento</a>
            <a href="#">Lojas</a>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="main-header">
    <div class="header-container">
        <div class="logo-container">
            <img src="ysl.png" alt="YSL Logo" class="logo-img">
           
        </div>
        
        <!-- Barra de Pesquisa -->
        <div class="search-container">
            <form action="index.php" method="GET" style="display: flex; width: 100%;">
                <input type="text" name="search" class="search-input" placeholder="Pesquisar produtos..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div class="user-actions">
            <a href="#" onclick="openFavoritesModal()">
                <i class="far fa-heart"></i>
                <span>Favoritos</span>
            </a>
            <a href="#" onclick="openCartModal()">
                <i class="fas fa-shopping-bag"></i>
                <span>Carrinho</span>
            </a>
        </div>
    </div>
</header>

<!-- Main Navigation -->
<nav class="main-nav">
    <div class="nav-container">
        <ul class="nav-list">
            <?php
            $sql_types = "SELECT ID_tipo, tipo FROM tb_tipo ORDER BY tipo ASC";
            $stmt_types = $conn->prepare($sql_types);
            $stmt_types->execute();
            $result_types = $stmt_types->get_result();
            
            if ($result_types->num_rows > 0) {
                $all_active = (!$selected_tipo_id && empty($search_query)) ? 'active' : '';
                echo "<li><a href='?' class='$all_active'>Todos</a></li>";

                while ($row_type = $result_types->fetch_assoc()) {
                    $active_class = $selected_tipo_id == $row_type["ID_tipo"] ? 'active' : '';
                    echo "<li><a href='?tipo=".$row_type["ID_tipo"]."' class='$active_class'>" 
                         . ucfirst($row_type["tipo"]) . "</a></li>";
                }
            }
            $stmt_types->close();
            ?>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<main class="main-container">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="#">Home</a> > <a href="#">Categorias</a> > <span><?php 
                if (!empty($search_query)) {
                    echo "Resultados para: " . htmlspecialchars($search_query);
                } else {
                    echo $selected_tipo_name; 
                }
            ?></span>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <select class="filter-select">
                    <option>Ordenar por</option>
                    <option>Mais vendidos</option>
                    <option>Menor preço</option>
                    <option>Maior preço</option>
                    <option>Lançamentos</option>
                </select>
            </div>
            
            <div class="product-count">
                <?php
                $sql_count = "SELECT COUNT(*) as total FROM tb_produtos p WHERE 1=1";
                $params = array();
                $types = "";
                
                if ($selected_tipo_id) {
                    $sql_count .= " AND p.ID_tipo = ?";
                    $params[] = $selected_tipo_id;
                    $types .= "i";
                }
                
                if (!empty($search_query)) {
                    $sql_count .= " AND p.marca LIKE ?";
                    $params[] = "%" . $search_query . "%";
                    $types .= "s";
                }
                
                $stmt_count = $conn->prepare($sql_count);
                if (!empty($params)) {
                    $stmt_count->bind_param($types, ...$params);
                }
                
                $stmt_count->execute();
                $result_count = $stmt_count->get_result();
                $row_count = $result_count->fetch_assoc();
                $item_count = $row_count['total'];
                $stmt_count->close();
                
                if (!empty($search_query)) {
                    echo "<span>$item_count resultado(s) para \"" . htmlspecialchars($search_query) . "\"</span>";
                } else {
                    echo "<span>$item_count produto(s) em $selected_tipo_name</span>";
                }
                ?>
            </div>
        </div>
        
        <!-- Product Grid -->
        <div class="product-grid">
            <?php
            $sql_products = "SELECT p.ID_produto, p.marca, p.preco, p.imagem, p.estoque 
                           FROM tb_produtos p WHERE 1=1";
            $params = array();
            $types = "";
            
            if ($selected_tipo_id) {
                $sql_products .= " AND p.ID_tipo = ?";
                $params[] = $selected_tipo_id;
                $types .= "i";
            }
            
            if (!empty($search_query)) {
                $sql_products .= " AND p.marca LIKE ?";
                $params[] = "%" . $search_query . "%";
                $types .= "s";
            }

            $stmt_products = $conn->prepare($sql_products);
            if (!empty($params)) {
                $stmt_products->bind_param($types, ...$params);
            }
            
            $stmt_products->execute();
            $result_products = $stmt_products->get_result();
            
            if ($result_products->num_rows > 0) {
                while ($row = $result_products->fetch_assoc()) {
                    $estoque = $row['estoque'];
                    $ID_produto = $row['ID_produto'];
                    $ID_cliente = $_SESSION['ID_cliente'];

                    $stmt_like = $conn->prepare("SELECT COUNT(*) FROM tb_curtidas WHERE ID_cliente = ? AND ID_produto = ?");
                    $stmt_like->bind_param("ii", $ID_cliente, $ID_produto);
                    $stmt_like->execute();
                    $liked = $stmt_like->get_result()->fetch_row()[0] > 0;
                    $stmt_like->close();

                    echo "<div class='product-card'>";
                    echo "   <div class='product-image'>";
                    echo "      <img src='" . htmlspecialchars($row["imagem"]) . "' alt='" . htmlspecialchars($row["marca"]) . "'>";
                    echo "      <div class='product-badge'>-20%</div>";
                    echo "      <div class='product-wishlist'>";
                    echo "         <form method='POST'>";
                    echo "            <input type='hidden' name='ID_produto' value='$ID_produto'>";
                    echo "            <button type='submit' name='toggle_like' class='like-button " . ($liked ? "liked" : "") . "'>";
                    echo "               <i class='" . ($liked ? "fas" : "far") . " fa-heart'></i>";
                    echo "            </button>";
                    echo "         </form>";
                    echo "      </div>";
                    echo "   </div>";
                    echo "   <div class='product-info'>";
                    echo "      <h3 class='product-name'>" . htmlspecialchars($row["marca"]) . "</h3>";
                    echo "      <div class='product-price'>";
                    echo "         <span class='current-price'>R$ " . number_format($row["preco"] * 0.8, 2, ',', '.') . "</span>";
                    echo "         <span class='original-price'>R$ " . number_format($row["preco"], 2, ',', '.') . "</span>";
                    echo "      </div>";
                    echo "      <span class='installments'>ou 3x de R$ " . number_format(($row["preco"] * 0.8) / 3, 2, ',', '.') . "</span>";
                    echo "      <form method='POST'>";
                    echo "         <input type='hidden' name='ID_produto' value='$ID_produto'>";
                    echo "         <button type='submit' name='add_to_cart' class='add-to-cart' " . ($estoque <= 0 ? "disabled" : "") . ">";
                    echo            ($estoque <= 0 ? "ESGOTADO" : "COMPRAR");
                    echo "         </button>";
                    echo "      </form>";
                    echo "   </div>";
                    echo "</div>";
                }
            } else {
                echo "<p style='grid-column: 1/-1; text-align: center; padding: 40px;'>Nenhum produto encontrado" . (!empty($search_query) ? " para \"" . htmlspecialchars($search_query) . "\"" : "") . ".</p>";
            }
            $stmt_products->close();
            ?>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>Institucional</h3>
                <ul>
                    <li><a href="#">Sobre a YSL</a></li>
                    <li><a href="#">Nossas Lojas</a></li>
                    <li><a href="#">Trabalhe Conosco</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Ajuda</h3>
                <ul>
                    <li><a href="#">Atendimento</a></li>
                    <li><a href="#">Trocas e Devoluções</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contato</h3>
                <ul>
                    <li><a href="#">WhatsApp</a></li>
                    <li><a href="#">SAC: 0800 123 4567</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>YSL © 2023 - Todos os direitos reservados</p>
        </div>
    </div>
</footer>

<!-- Modal de Favoritos -->
<div id="favoritesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Meus Favoritos</h2>
            <span class="close" onclick="closeFavoritesModal()">&times;</span>
        </div>
        <div class="modal-body">
            <?php
            $ID_cliente = $_SESSION['ID_cliente'];
            $sql_favorites = "SELECT p.ID_produto, p.marca, p.preco, p.imagem 
                            FROM tb_produtos p
                            JOIN tb_curtidas c ON p.ID_produto = c.ID_produto
                            WHERE c.ID_cliente = ?";
            $stmt_favorites = $conn->prepare($sql_favorites);
            $stmt_favorites->bind_param("i", $ID_cliente);
            $stmt_favorites->execute();
            $result_favorites = $stmt_favorites->get_result();
            
            if ($result_favorites->num_rows > 0) {
                while ($row = $result_favorites->fetch_assoc()) {
                    echo "<div style='display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee;'>";
                    echo "   <img src='" . htmlspecialchars($row['imagem']) . "' style='width: 60px; height: 60px; object-fit: cover; margin-right: 15px;'>";
                    echo "   <div style='flex-grow: 1;'>";
                    echo "      <h3 style='margin: 0; font-size: 16px;'>" . htmlspecialchars($row['marca']) . "</h3>";
                    echo "      <p style='margin: 5px 0; font-weight: bold; color: #e60000;'>R$ " . number_format($row['preco'], 2, ',', '.') . "</p>";
                    echo "   </div>";
                    echo "   <form method='POST' style='margin-left: 10px;'>";
                    echo "      <input type='hidden' name='ID_produto' value='" . $row['ID_produto'] . "'>";
                    echo "      <button type='submit' name='toggle_like' style='background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;'>Remover</button>";
                    echo "   </form>";
                    echo "</div>";
                }
            } else {
                echo "<p style='text-align: center; padding: 40px;'>Você ainda não tem produtos favoritos</p>";
            }
            $stmt_favorites->close();
            ?>
        </div>
    </div>
</div>

<!-- Modal do Carrinho -->
<div id="cartModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Carrinho de Compras</h2>
            <span class="close" onclick="closeCartModal()">&times;</span>
        </div>
        <div class="modal-body">
            <?php
            $ID_cliente = $_SESSION['ID_cliente'];
            $sql_cart = "SELECT c.ID_carrinho FROM tb_carrinho c 
                         WHERE c.ID_cliente = ? 
                         ORDER BY c.data_criacao DESC LIMIT 1";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->bind_param("i", $ID_cliente);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            
            if ($result_cart->num_rows > 0) {
                $cart = $result_cart->fetch_assoc();
                $ID_carrinho = $cart['ID_carrinho'];
                
                $sql_items = "SELECT i.ID_item, p.ID_produto, p.marca, p.preco, p.imagem, i.quantidade 
                             FROM tb_itens_carrinho i
                             JOIN tb_produtos p ON i.ID_produto = p.ID_produto
                             WHERE i.ID_carrinho = ?";
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->bind_param("i", $ID_carrinho);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                
                if ($result_items->num_rows > 0) {
                    $total = 0;
                    echo "<table style='width: 100%; border-collapse: collapse;'>";
                    echo "<thead>";
                    echo "<tr style='background: #f5f5f5;'>";
                    echo "<th style='padding: 10px; text-align: left;'>Produto</th>";
                    echo "<th style='padding: 10px; text-align: right;'>Preço</th>";
                    echo "<th style='padding: 10px; text-align: center;'>Qtd</th>";
                    echo "<th style='padding: 10px; text-align: right;'>Subtotal</th>";
                    echo "<th style='padding: 10px;'></th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    
                    while ($item = $result_items->fetch_assoc()) {
                        $subtotal = $item['preco'] * $item['quantidade'];
                        $total += $subtotal;
                        
                        echo "<tr style='border-bottom: 1px solid #eee;'>";
                        echo "<td style='padding: 10px;'>";
                        echo "<div style='display: flex; align-items: center;'>";
                        echo "<img src='" . htmlspecialchars($item['imagem']) . "' style='width: 50px; height: 50px; object-fit: cover; margin-right: 10px;'>";
                        echo htmlspecialchars($item['marca']);
                        echo "</div>";
                        echo "</td>";
                        echo "<td style='padding: 10px; text-align: right;'>R$ " . number_format($item['preco'], 2, ',', '.') . "</td>";
                        echo "<td style='padding: 10px; text-align: center;'>" . $item['quantidade'] . "</td>";
                        echo "<td style='padding: 10px; text-align: right;'>R$ " . number_format($subtotal, 2, ',', '.') . "</td>";
                        echo "<td style='padding: 10px; text-align: center;'>";
                        echo "<form method='POST'>";
                        echo "<input type='hidden' name='ID_item' value='" . $item['ID_item'] . "'>";
                        echo "<button type='submit' name='delete_item' style='background: #ff4444; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;'>&times;</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    echo "<tr style='background: #f5f5f5; font-weight: bold;'>";
                    echo "<td colspan='3' style='padding: 10px; text-align: right;'>Total:</td>";
                    echo "<td style='padding: 10px; text-align: right;'>R$ " . number_format($total, 2, ',', '.') . "</td>";
                    echo "<td></td>";
                    echo "</tr>";
                    echo "</tbody>";
                    echo "</table>";
                    
                    echo "<div style='text-align: right; margin-top: 20px;'>";
                    echo "<button style='background: #e60000; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;'>Finalizar Compra</button>";
                    echo "</div>";
                } else {
                    echo "<p style='text-align: center; padding: 40px;'>Seu carrinho está vazio</p>";
                }
                $stmt_items->close();
            } else {
                echo "<p style='text-align: center; padding: 40px;'>Seu carrinho está vazio</p>";
            }
            $stmt_cart->close();
            ?>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<script>
    // Funções para abrir e fechar modais
    function openFavoritesModal() {
        document.getElementById('favoritesModal').style.display = 'flex';
    }
    
    function closeFavoritesModal() {
        document.getElementById('favoritesModal').style.display = 'none';
    }
    
    function openCartModal() {
        document.getElementById('cartModal').style.display = 'flex';
    }
    
    function closeCartModal() {
        document.getElementById('cartModal').style.display = 'none';
    }
    
    // Fechar modais ao clicar fora
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>

</body>
</html>