-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 05/04/2025 às 03:03
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `db_loja`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_carrinho`
--

CREATE TABLE `tb_carrinho` (
  `ID_carrinho` int(11) NOT NULL,
  `ID_cliente` int(11) DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_carrinho`
--

INSERT INTO `tb_carrinho` (`ID_carrinho`, `ID_cliente`, `data_criacao`) VALUES
(1, 1, '2025-04-02 22:12:23'),
(2, 1, '2025-04-03 22:26:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_clientes`
--

CREATE TABLE `tb_clientes` (
  `ID_cliente` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_clientes`
--

INSERT INTO `tb_clientes` (`ID_cliente`, `nome`, `email`, `telefone`) VALUES
(1, 'João Silva', 'joao.silva@email.com', '11987654321'),
(2, 'Maria Oliveira', 'maria.oliveira@email.com', '21987654321'),
(3, 'Pedro Santos', 'pedro.santos@email.com', '31987654321');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_curtidas`
--

CREATE TABLE `tb_curtidas` (
  `ID_curtida` int(11) NOT NULL,
  `ID_cliente` int(11) DEFAULT NULL,
  `ID_produto` int(11) DEFAULT NULL,
  `data_curtida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_itens_carrinho`
--

CREATE TABLE `tb_itens_carrinho` (
  `ID_item` int(11) NOT NULL,
  `ID_carrinho` int(11) DEFAULT NULL,
  `ID_produto` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_itens_carrinho`
--

INSERT INTO `tb_itens_carrinho` (`ID_item`, `ID_carrinho`, `ID_produto`, `quantidade`) VALUES
(28, 2, 8, 1),
(29, 2, 7, 1),
(30, 2, 7, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_produtos`
--

CREATE TABLE `tb_produtos` (
  `ID_produto` int(11) NOT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `tamanho` varchar(10) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `ID_tipo` int(11) DEFAULT NULL,
  `estoque` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_produtos`
--

INSERT INTO `tb_produtos` (`ID_produto`, `cor`, `tamanho`, `marca`, `preco`, `imagem`, `ID_tipo`, `estoque`) VALUES
(2, 'Preta', 'M', 'Zara', 1999.00, 'https://static.zara.net/assets/public/09f4/9d1d/f3fa4515aa52/187b30d39e8d/05479263800-003-p/05479263800-003-p.jpg?ts=1727969954091&w=750', 4, 8),
(3, 'Vermelho-bordô', 'M', 'Zara', 399.00, 'https://static.zara.net/assets/public/0f21/d549/80c14075bc12/11d2961fe8c1/11219510022-e1/11219510022-e1.jpg?ts=1736250089402&w=563', 6, 8),
(4, 'Vermelho', '42', 'Zara', 129.90, 'https://static.zara.net/assets/public/74e1/4505/a17548f5834a/9c856855c7bc/02377198600-p/02377198600-p.jpg?ts=1739619912289&w=750', 2, 9),
(5, 'Branco', '30', 'Zara', 349.00, 'https://static.zara.net/assets/public/3bee/c661/9797483fa44a/57e6b6f9770f/12901510001-e1/12901510001-e1.jpg?ts=1742983097916&w=750', 6, 10),
(6, 'Marrom', '35', 'Zara', 629.00, 'https://static.zara.net/assets/public/2d3d/ddde/ab204d068c69/d0902f080f3d/11018410605-e1/11018410605-e1.jpg?ts=1737389110006&w  =850', 6, 10),
(7, 'Azul', 'M', 'Zara', 299.00, 'https://static.zara.net/assets/public/03e5/66d0/1c9347308c47/7e870db28fe4/08307041407-p/08307041407-p.jpg?ts=1740474607696&w=750', 2, 8),
(8, 'Branco', 'P', 'Zara', 279.00, 'https://static.zara.net/assets/public/8a1d/9f57/520645b7a8cb/3a2930c3f763/08741032250-p/08741032250-p.jpg?ts=1742820593549&w  =750', 1, 7),
(18, 'Preto', 'M', 'Zara', 629.00, 'https://static.zara.net/assets/public/2a72/2693/ed474c498705/5f9bb0add737/03046353704-p/03046353704-p.jpg?ts=1734514929145&w=750', 5, 10),
(19, 'Azul-marinho', 'G', 'Zara', 729.00, 'https://static.zara.net/assets/public/88a2/2ec3/6108454dbdf9/062f8babf9db/07818202401-p/07818202401-p.jpg?ts=1743602606309&w=750', 5, 8),
(20, 'Branco', '40', 'Zara', 399.00, 'https://static.zara.net/assets/public/d5fa/afc2/4df74dacbbb0/45626e5292f0/15051410202-000-e1/15051410202-000-e1.jpg?ts=1741345587281&w=750', 3, 12),
(21, 'Azul/Cinza', 'M', 'Zara', 299.00, 'https://static.zara.net/assets/public/3cc8/4417/85ed4a6693e1/6b0d617d4813/01608234485-p/01608234485-p.jpg?ts=1743092849832&w=750', 2, 15);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_tipo`
--

CREATE TABLE `tb_tipo` (
  `ID_tipo` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_tipo`
--

INSERT INTO `tb_tipo` (`ID_tipo`, `tipo`) VALUES
(1, 'Camisetas'),
(2, 'Calças'),
(3, 'Tênis'),
(4, 'Jaquetas'),
(5, 'Casacos'),
(6, 'Calçados');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `tb_carrinho`
--
ALTER TABLE `tb_carrinho`
  ADD PRIMARY KEY (`ID_carrinho`),
  ADD KEY `ID_cliente` (`ID_cliente`);

--
-- Índices de tabela `tb_clientes`
--
ALTER TABLE `tb_clientes`
  ADD PRIMARY KEY (`ID_cliente`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `tb_curtidas`
--
ALTER TABLE `tb_curtidas`
  ADD PRIMARY KEY (`ID_curtida`),
  ADD UNIQUE KEY `ID_cliente` (`ID_cliente`,`ID_produto`),
  ADD KEY `ID_produto` (`ID_produto`);

--
-- Índices de tabela `tb_itens_carrinho`
--
ALTER TABLE `tb_itens_carrinho`
  ADD PRIMARY KEY (`ID_item`),
  ADD KEY `ID_carrinho` (`ID_carrinho`),
  ADD KEY `ID_produto` (`ID_produto`);

--
-- Índices de tabela `tb_produtos`
--
ALTER TABLE `tb_produtos`
  ADD PRIMARY KEY (`ID_produto`),
  ADD KEY `ID_tipo` (`ID_tipo`);

--
-- Índices de tabela `tb_tipo`
--
ALTER TABLE `tb_tipo`
  ADD PRIMARY KEY (`ID_tipo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `tb_carrinho`
--
ALTER TABLE `tb_carrinho`
  MODIFY `ID_carrinho` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `tb_clientes`
--
ALTER TABLE `tb_clientes`
  MODIFY `ID_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tb_curtidas`
--
ALTER TABLE `tb_curtidas`
  MODIFY `ID_curtida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `tb_itens_carrinho`
--
ALTER TABLE `tb_itens_carrinho`
  MODIFY `ID_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `tb_produtos`
--
ALTER TABLE `tb_produtos`
  MODIFY `ID_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `tb_tipo`
--
ALTER TABLE `tb_tipo`
  MODIFY `ID_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `tb_carrinho`
--
ALTER TABLE `tb_carrinho`
  ADD CONSTRAINT `tb_carrinho_ibfk_1` FOREIGN KEY (`ID_cliente`) REFERENCES `tb_clientes` (`ID_cliente`);

--
-- Restrições para tabelas `tb_curtidas`
--
ALTER TABLE `tb_curtidas`
  ADD CONSTRAINT `tb_curtidas_ibfk_1` FOREIGN KEY (`ID_cliente`) REFERENCES `tb_clientes` (`ID_cliente`),
  ADD CONSTRAINT `tb_curtidas_ibfk_2` FOREIGN KEY (`ID_produto`) REFERENCES `tb_produtos` (`ID_produto`);

--
-- Restrições para tabelas `tb_itens_carrinho`
--
ALTER TABLE `tb_itens_carrinho`
  ADD CONSTRAINT `tb_itens_carrinho_ibfk_1` FOREIGN KEY (`ID_carrinho`) REFERENCES `tb_carrinho` (`ID_carrinho`),
  ADD CONSTRAINT `tb_itens_carrinho_ibfk_2` FOREIGN KEY (`ID_produto`) REFERENCES `tb_produtos` (`ID_produto`);

--
-- Restrições para tabelas `tb_produtos`
--
ALTER TABLE `tb_produtos`
  ADD CONSTRAINT `tb_produtos_ibfk_1` FOREIGN KEY (`ID_tipo`) REFERENCES `tb_tipo` (`ID_tipo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
