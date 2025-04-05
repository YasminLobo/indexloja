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
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

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
?>

<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YSL</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e60000;
            --secondary-color: #333;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #666;
            --white: #fff;
            --black: #000;
            --discount-color: #00a650;
        }
        
        /* Reset e Estilos Gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Montserrat', sans-serif;
            color: var(--secondary-color);
            background: var(--white);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Header Superior */
        .top-bar {
            background: var(--black);
            color: var(--white);
            padding: 8px 0;
            font-size: 12px;
        }

        .top-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .top-links a {
            margin-left: 15px;
            transition: color 0.3s;
        }

        .top-links a:hover {
            color: var(--primary-color);
        }

        /* Header Principal */
        .main-header {
            background: var(--white);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .search-bar {
            flex-grow: 1;
            margin: 0 30px;
            position: relative;
        }

        .search-bar form {
            position: relative;
            width: 100%;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(230, 0, 0, 0.1);
        }

        .search-bar button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--dark-gray);
            cursor: pointer;
            transition: color 0.3s;
        }

        .search-bar button:hover {
            color: var(--primary-color);
        }

        .search-loading {
            display: none;
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }

        .user-actions {
            display: flex;
            gap: 20px;
        }

        .user-actions a {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
        }

        .user-actions i {
            font-size: 20px;
            margin-bottom: 3px;
        }

        /* Navegação Principal */
        .main-nav {
            background: var(--white);
            border-top: 1px solid var(--medium-gray);
            border-bottom: 1px solid var(--medium-gray);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-list {
            display: flex;
            list-style: none;
        }

        .nav-list li {
            position: relative;
        }

        .nav-list a {
            display: block;
            padding: 15px 20px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-list a:hover {
            color: var(--primary-color);
        }

        /* Banner Promocional */
        .promo-banner {
            background: var(--primary-color);
            color: var(--white);
            text-align: center;
            padding: 10px;
            font-weight: 500;
        }

        /* Breadcrumb */
        .breadcrumb {
            padding: 15px 0;
            font-size: 12px;
            color: var(--dark-gray);
        }

        .breadcrumb a {
            color: var(--dark-gray);
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Filtros */
        .filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--medium-gray);
        }

        .filter-group {
            display: flex;
            gap: 15px;
        }

        .filter-select {
            padding: 8px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            font-size: 14px;
        }

        /* Grid de Produtos */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            padding: 20px 0;
            margin-left: 20px;
        }

        .product-card {
            position: relative;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            position: relative;
            overflow: hidden;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .product-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--discount-color);
            color: var(--white);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-wishlist {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--white);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .like-button {
            background: none;
            border: none;
            cursor: pointer;
            color: #ccc;
            font-size: 18px;
            transition: all 0.3s ease;
            padding: 5px;
            outline: none;
        }

        .like-button:hover {
            color: #ff4444;
            transform: scale(1.1);
        }

        .like-button.liked {
            color: #ff4444;
        }

        .product-wishlist form {
            margin: 0;
            display: inline;
        }

        .product-wishlist button {
            background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
        }

        .product-info {
            padding: 0 5px;
        }

        .product-name {
            font-size: 14px;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .current-price {
            font-weight: 700;
            color: var(--black);
        }

        .original-price {
            font-size: 12px;
            color: var(--dark-gray);
            text-decoration: line-through;
        }

        .installments {
            font-size: 12px;
            color: var(--dark-gray);
        }

        .add-to-cart {
            width: 100%;
            padding: 8px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .add-to-cart:hover {
            background: #c50000;
        }

        .add-to-cart:disabled {
            background: var(--medium-gray);
            cursor: not-allowed;
        }

        /* Footer */
        .main-footer {
            background: var(--light-gray);
            padding: 40px 0 20px;
            margin-top: 40px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 10px;
        }

        .footer-column a {
            font-size: 14px;
            color: var(--dark-gray);
            transition: color 0.3s;
        }

        .footer-column a:hover {
            color: var(--primary-color);
        }

        .footer-bottom {
            border-top: 1px solid var(--medium-gray);
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: var(--dark-gray);
        }

        /* Modal de Favoritos */
        .favorites-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .favorites-modal-content {
            background: #fff;
            max-width: 600px;
            width: 100%;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .favorites-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .favorites-modal-header h2 {
            color: #e60000;
            font-size: 22px;
            margin: 0;
        }

        .favorites-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #888;
            transition: color 0.3s ease;
        }

        .favorites-close:hover {
            color: #e60000;
        }

        .favorites-list {
            margin-bottom: 20px;
        }

        .favorite-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .favorite-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 15px;
        }

        .favorite-info {
            flex-grow: 1;
        }

        .favorite-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .favorite-info .price {
            font-weight: bold;
            color: #e60000;
        }

        .favorite-actions {
            display: flex;
            gap: 10px;
        }

        .btn-remove-favorite {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-add-to-cart {
            background: #e60000;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .empty-favorites {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        /* Modal do Carrinho */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: #fff;
            max-width: 700px;
            width: 100%;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            position: relative;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .modal-header h2 {
            color: #e60000;
            font-size: 22px;
            margin: 0;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #888;
            transition: color 0.3s ease;
            line-height: 1;
            padding: 0 5px;
        }

        .close:hover {
            color: #e60000;
        }

        .modal-body {
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .modal-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-content th,
        .modal-content td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .modal-content th {
            background: #f9f9f9;
            color: #444;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .modal-content td {
            color: #555;
            font-size: 14px;
        }

        .modal-content td.action-cell {
            text-align: center;
            width: 60px;
        }

        .modal-content .total-row td {
            font-weight: bold;
            background: #f9f9f9;
            border-top: 2px solid #ddd;
            font-size: 15px;
        }

        .modal-content .total-row td:first-child {
            text-align: right;
        }

        .btn-delete {
            background: #ff4444;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-delete .fa {
            font-size: 14px;
        }

        .btn-delete:hover {
            background: #cc0000;
        }

        td form {
            margin: 0;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #eee;
            flex-shrink: 0;
        }

        .btn-checkout {
            background: #e60000;
            color: #fff;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-checkout:hover {
            background: #c50000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .empty-cart-message {
            text-align: center;
            padding: 40px 20px;
            color: #777;
            font-size: 16px;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .header-container {
                flex-wrap: wrap;
            }
            
            .search-bar {
                order: 3;
                width: 100%;
                margin: 15px 0;
            }
            
            .nav-list {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 10px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                display: none;
            }
            
            .user-actions {
                gap: 15px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 24px;
            }
            
            .user-actions span {
                display: none;
            }
            
            .user-actions i {
                font-size: 18px;
            }
            
            .product-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .favorite-item {
                flex-direction: column;
                text-align: center;
            }
            
            .favorite-item img {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .favorite-actions {
                justify-content: center;
                margin-top: 10px;
            }
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
        <a href="index.php" class="logo">YSL</a>
        
        <div class="search-bar">
            <form method="GET" action="index.php" id="searchForm">
                <input type="text" name="search" placeholder="O que você está procurando?" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
                <div class="search-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
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
                $all_active = !isset($_GET['tipo']) || empty($_GET['tipo']) ? 'active' : '';
                echo "<li><a href='?' class='$all_active'>Todos</a></li>";

                while ($row_type = $result_types->fetch_assoc()) {
                    $tipo = htmlspecialchars($row_type["tipo"]);
                    $id_tipo = $row_type["ID_tipo"];
                    $active_class = (isset($_GET['tipo']) && $_GET['tipo'] == $id_tipo) ? 'active' : '';
                    echo "<li><a href='?tipo=$id_tipo' class='$active_class'>" . ucfirst($tipo) . "</a></li>";
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
            <a href="#">Home</a> > <a href="#">Moda</a> > <span>Calçados</span>
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
                
                <select class="filter-select">
                    <option>Tamanho</option>
                    <option>P</option>
                    <option>M</option>
                    <option>G</option>
                    <option>GG</option>
                </select>
            </div>
            
            <div class="product-count">
                <?php
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

                $sql_products = "SELECT p.ID_produto FROM tb_produtos p";
                if ($selected_tipo_id) {
                    $sql_products .= " WHERE p.ID_tipo = ?";
                    if (!empty($search_term)) {
                        $sql_products .= " AND p.marca LIKE ?";
                    }
                } else if (!empty($search_term)) {
                    $sql_products .= " WHERE p.marca LIKE ?";
                }

                $stmt_products = $conn->prepare($sql_products);
                if ($selected_tipo_id && !empty($search_term)) {
                    $search_param = "%$search_term%";
                    $stmt_products->bind_param("is", $selected_tipo_id, $search_param);
                } else if ($selected_tipo_id) {
                    $stmt_products->bind_param("i", $selected_tipo_id);
                } else if (!empty($search_term)) {
                    $search_param = "%$search_term%";
                    $stmt_products->bind_param("s", $search_param);
                }
                $stmt_products->execute();
                $result_products = $stmt_products->get_result();
                $item_count = $result_products->num_rows;
                
                echo "<span>$item_count produto(s) encontrado(s) em $selected_tipo_name";
                if (!empty($search_term)) {
                    echo " para '<strong>" . htmlspecialchars($search_term) . "</strong>'";
                }
                echo "</span>";
                ?>
            </div>
        </div>
        
        <!-- Product Grid -->
        <div class="product-grid">
            <?php
            $sql_products = "SELECT p.ID_produto, p.cor, p.tamanho, p.marca, p.preco, p.imagem, p.estoque
                            FROM tb_produtos p";
            if ($selected_tipo_id) {
                $sql_products .= " WHERE p.ID_tipo = ?";
                if (!empty($search_term)) {
                    $sql_products .= " AND p.marca LIKE ?";
                }
            } else if (!empty($search_term)) {
                $sql_products .= " WHERE p.marca LIKE ?";
            }

            $stmt_products = $conn->prepare($sql_products);
            if ($selected_tipo_id && !empty($search_term)) {
                $search_param = "%$search_term%";
                $stmt_products->bind_param("is", $selected_tipo_id, $search_param);
            } else if ($selected_tipo_id) {
                $stmt_products->bind_param("i", $selected_tipo_id);
            } else if (!empty($search_term)) {
                $search_param = "%$search_term%";
                $stmt_products->bind_param("s", $search_param);
            }
            $stmt_products->execute();
            $result_products = $stmt_products->get_result();
            
            if ($item_count > 0) {
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
                    echo "      <h3 class='product-name'>" . htmlspecialchars(ucfirst($row["marca"])) . "</h3>";
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
                echo "<p style='grid-column: 1/-1; text-align: center; padding: 40px;'>Nenhum produto encontrado " . (!empty($search_term) ? "para sua pesquisa." : "nesta categoria.") . "</p>";
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
                    <li><a href="#">Política de Privacidade</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Ajuda</h3>
                <ul>
                    <li><a href="#">Atendimento</a></li>
                    <li><a href="#">Perguntas Frequentes</a></li>
                    <li><a href="#">Trocas e Devoluções</a></li>
                    <li><a href="#">Formas de Pagamento</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
               
                <ul>
                    <li><a href="#">Meus Pedidos</a></li>
                    <li><a href="#">Meus Cupons</a></li>
                    
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contato</h3>
                <ul>
                    <li><a href="#">WhatsApp</a></li>
                    <li><a href="#">Chat Online</a></li>
                    <li><a href="#">SAC: 0800 123 4567</a></li>
                    <li><a href="#">E-mail: contato@YSL.com.br</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>YSL © 2023 - Todos os direitos reservados</p>
        </div>
    </div>
</footer>

<!-- Modal de Favoritos -->
<div id="favoritesModal" class="favorites-modal">
    <div class="favorites-modal-content">
        <div class="favorites-modal-header">
            <h2>Meus Favoritos</h2>
            <span class="favorites-close" onclick="closeFavoritesModal()">&times;</span>
        </div>
        
        <div class="favorites-list">
            <?php
            if (isset($_SESSION['ID_cliente'])) {
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
                        echo "<div class='favorite-item'>";
                        echo "   <img src='" . htmlspecialchars($row['imagem']) . "' alt='" . htmlspecialchars($row['marca']) . "'>";
                        echo "   <div class='favorite-info'>";
                        echo "      <h3>" . htmlspecialchars($row['marca']) . "</h3>";
                        echo "      <span class='price'>R$ " . number_format($row['preco'], 2, ',', '.') . "</span>";
                        echo "   </div>";
                        echo "   <div class='favorite-actions'>";
                        echo "      <form method='POST' style='display:inline;'>";
                        echo "         <input type='hidden' name='ID_produto' value='" . $row['ID_produto'] . "'>";
                        echo "         <button type='submit' name='toggle_like' class='btn-remove-favorite'>Remover</button>";
                        echo "      </form>";
                        echo "      <form method='POST' style='display:inline;'>";
                        echo "         <input type='hidden' name='ID_produto' value='" . $row['ID_produto'] . "'>";
                        echo "         <button type='submit' name='add_to_cart' class='btn-add-to-cart'>Comprar</button>";
                        echo "      </form>";
                        echo "   </div>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='empty-favorites'>";
                    echo "   <p>Você ainda não tem produtos favoritos</p>";
                    echo "   <p>Clique no coração nos produtos para adicioná-los aqui</p>";
                    echo "</div>";
                }
                $stmt_favorites->close();
            }
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
            $ID_cliente_modal = $_SESSION['ID_cliente'];
            $sql_find_cart = "SELECT ID_carrinho FROM tb_carrinho WHERE ID_cliente = ? ORDER BY data_criacao DESC LIMIT 1";
            $stmt_find_cart = $conn->prepare($sql_find_cart);
            $stmt_find_cart->bind_param("i", $ID_cliente_modal);
            $stmt_find_cart->execute();
            $result_find_cart = $stmt_find_cart->get_result();
            $active_cart_id = null;
            if ($result_find_cart->num_rows > 0) {
                $active_cart_id = $result_find_cart->fetch_assoc()['ID_carrinho'];
            }
            $stmt_find_cart->close();

            $total_geral = 0;

            if ($active_cart_id) {
                $sql_cart_items = "SELECT ic.ID_item, p.ID_produto, p.marca, p.preco, p.imagem, ic.quantidade
                                   FROM tb_itens_carrinho ic
                                   JOIN tb_produtos p ON ic.ID_produto = p.ID_produto
                                   WHERE ic.ID_carrinho = ?";
                $stmt_cart_items = $conn->prepare($sql_cart_items);
                $stmt_cart_items->bind_param("i", $active_cart_id);
                $stmt_cart_items->execute();
                $result_cart_items = $stmt_cart_items->get_result();

                if ($result_cart_items->num_rows > 0) {
                    echo "<table>";
                    echo "<thead><tr><th>Produto</th><th>Preço Unit.</th><th>Quantidade</th><th>Subtotal</th><th></th></tr></thead>";
                    echo "<tbody>";
                    while ($row_item = $result_cart_items->fetch_assoc()) {
                        $subtotal = $row_item['preco'] * $row_item['quantidade'];
                        $total_geral += $subtotal;
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row_item['marca']) . "</td>";
                        echo "<td>R$ " . number_format($row_item["preco"], 2, ',', '.') . "</td>";
                        echo "<td>" . $row_item['quantidade'] . "</td>";
                        echo "<td>R$ " . number_format($subtotal, 2, ',', '.') . "</td>";
                        echo "<td class='action-cell'>";
                        echo "<form method='POST' onsubmit='return confirm(\"Remover este item do carrinho?\");'>";
                        echo "<input type='hidden' name='ID_item' value='" . $row_item['ID_item'] . "'>";
                        echo "<button type='submit' name='delete_item' class='btn btn-delete' title='Remover Item'><i class='fa fa-trash'></i></button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "<tr class='total-row'><td colspan='3'><b>Total Geral</b></td><td><b>R$ " . number_format($total_geral, 2, ',', '.') . "</b></td><td></td></tr>";
                    echo "</table>";

                    echo "<div class='modal-footer'>";
                    echo " <button class='btn btn-checkout'>Finalizar Compra</button>";
                    echo "</div>";
                } else {
                    echo "<p class='empty-cart-message'>Seu carrinho está vazio.</p>";
                }
                $stmt_cart_items->close();
            } else {
                echo "<p class='empty-cart-message'>Seu carrinho está vazio.</p>";
            }
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
    
    // Fechar modais ao clicar fora ou pressionar ESC
    window.onclick = function(event) {
        if (event.target == document.getElementById('favoritesModal')) {
            closeFavoritesModal();
        }
        if (event.target == document.getElementById('cartModal')) {
            closeCartModal();
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeFavoritesModal();
            closeCartModal();
        }
    });

    // Loading para pesquisa
    document.getElementById('searchForm').addEventListener('submit', function() {
        this.querySelector('.search-loading').style.display = 'block';
        this.querySelector('button i').style.display = 'none';
    });
</script>

</body>
</html>