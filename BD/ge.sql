-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-09-2026 a las 22:03:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ge`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mail` varchar(1000) DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `address`, `phone`, `mail`, `date`, `image`, `active`) VALUES
(1, 'Gina Essence', 'Medellín', '3128106683', 'ginacardona77@gmail.com', NULL, 'uploads/accounts/img_6ab9750d938471.19651441.png', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `account_movements`
--

CREATE TABLE `account_movements` (
  `id` int(11) UNSIGNED NOT NULL,
  `reference` varchar(50) NOT NULL,
  `financial_account_id` int(11) UNSIGNED NOT NULL,
  `related_table` enum('Ventas','Compras','Ajuste Manual') NOT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `movement_type` enum('Débito','Crédito') NOT NULL,
  `amount` decimal(25,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `date` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `account_movements`
--
DELIMITER $$
CREATE TRIGGER `	 trg_account_movements_after_insert` AFTER INSERT ON `account_movements` FOR EACH ROW BEGIN
    IF NEW.movement_type = 'Crédito' THEN
        UPDATE financial_accounts
        SET balance = balance + NEW.amount
        WHERE id = NEW.financial_account_id;
    ELSE
        UPDATE financial_accounts
        SET balance = balance - NEW.amount
        WHERE id = NEW.financial_account_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_account_movements_after_delete` AFTER DELETE ON `account_movements` FOR EACH ROW BEGIN
    IF OLD.movement_type = 'Crédito' THEN
        UPDATE financial_accounts
        SET balance = balance - OLD.amount
        WHERE id = OLD.financial_account_id;
    ELSE
        UPDATE financial_accounts
        SET balance = balance + OLD.amount
        WHERE id = OLD.financial_account_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_account_movements_after_update` AFTER UPDATE ON `account_movements` FOR EACH ROW BEGIN
    /* revertir efecto anterior */
    IF OLD.movement_type = 'Crédito' THEN
        UPDATE financial_accounts
        SET balance = balance - OLD.amount
        WHERE id = OLD.financial_account_id;
    ELSE
        UPDATE financial_accounts
        SET balance = balance + OLD.amount
        WHERE id = OLD.financial_account_id;
    END IF;

    /* aplicar efecto nuevo */
    IF NEW.movement_type = 'Crédito' THEN
        UPDATE financial_accounts
        SET balance = balance + NEW.amount
        WHERE id = NEW.financial_account_id;
    ELSE
        UPDATE financial_accounts
        SET balance = balance - NEW.amount
        WHERE id = NEW.financial_account_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `account_opening_hours`
--

CREATE TABLE `account_opening_hours` (
  `id` int(11) UNSIGNED NOT NULL,
  `id_account` int(11) UNSIGNED NOT NULL,
  `day_week` tinyint(1) UNSIGNED NOT NULL COMMENT '1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo',
  `opening_time` time NOT NULL DEFAULT '08:00:00',
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `account_opening_hours`
--

INSERT INTO `account_opening_hours` (`id`, `id_account`, `day_week`, `opening_time`, `active`) VALUES
(1, 1, 1, '08:00:00', 1),
(2, 1, 2, '08:00:00', 1),
(3, 1, 3, '08:00:00', 1),
(4, 1, 4, '08:00:00', 1),
(5, 1, 5, '08:00:00', 1),
(6, 1, 6, '08:00:00', 1),
(7, 1, 7, '08:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id` int(11) UNSIGNED NOT NULL,
  `dni` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mail` varchar(50) DEFAULT NULL,
  `address` varchar(1000) DEFAULT NULL,
  `note` varchar(5000) DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `employees`
--

CREATE TABLE `employees` (
  `id` int(11) UNSIGNED NOT NULL,
  `dni` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mail` varchar(255) DEFAULT NULL,
  `account` int(11) UNSIGNED NOT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `employees_attendance`
--

CREATE TABLE `employees_attendance` (
  `id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `check_in` datetime NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `lunch_time_departure` datetime DEFAULT NULL,
  `lunch_time_entrance` datetime DEFAULT NULL,
  `account` int(11) UNSIGNED NOT NULL,
  `opening_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `financial_accounts`
--

CREATE TABLE `financial_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Caja','Banco','Datáfono') NOT NULL DEFAULT 'Caja',
  `description` varchar(255) DEFAULT NULL,
  `balance` decimal(25,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `buy_price` decimal(25,2) NOT NULL,
  `total` decimal(25,2) NOT NULL,
  `user` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL,
  `movement_type` enum('Compra','Retiro','Traslados entre Cuentas','Traslados entre Ubicaciones') DEFAULT 'Compra'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_transfer`
--

CREATE TABLE `inventory_transfer` (
  `id` int(11) UNSIGNED NOT NULL,
  `user` varchar(255) DEFAULT NULL,
  `from_location_id` int(11) UNSIGNED NOT NULL,
  `to_location_id` int(11) UNSIGNED NOT NULL,
  `product_id_origin` int(11) UNSIGNED NOT NULL,
  `product_id_destination` int(11) UNSIGNED NOT NULL,
  `receiving_account_id` int(11) UNSIGNED NOT NULL,
  `qty` int(11) UNSIGNED NOT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `account` int(11) UNSIGNED NOT NULL,
  `transfer_type` enum('Traslados entre Cuentas','Traslados entre Ubicaciones') NOT NULL DEFAULT 'Traslados entre Cuentas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations`
--

CREATE TABLE `locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `location_type` enum('Interna','Externa') NOT NULL DEFAULT 'Interna',
  `date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `meli_event`
--

CREATE TABLE `meli_event` (
  `id` int(11) NOT NULL,
  `topic` varchar(50) NOT NULL,
  `resource` varchar(255) NOT NULL,
  `application_id` varchar(32) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL COMMENT 'User ID de Mercado Libre',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Notificación cruda enviada por Mercado Libre' CHECK (json_valid(`payload`)),
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `account` int(11) UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `meli_event_detail`
--

CREATE TABLE `meli_event_detail` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL COMMENT 'FK a meli_webhook_events',
  `entity_type` enum('orders','order_items','payments','shipments','claims','items','items_prices') NOT NULL COMMENT 'Entidad consultada en Mercado Libre',
  `entity_id` varchar(50) NOT NULL COMMENT 'ID del recurso en Mercado Libre',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Respuesta de la API de Mercado Libre',
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `account` int(11) UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) UNSIGNED NOT NULL,
  `item_ml` varchar(50) DEFAULT NULL COMMENT 'ID del producto en Mercado Libre',
  `variation_ml` varchar(50) DEFAULT NULL COMMENT 'ID de variación en Mercado Libre',
  `name` varchar(255) NOT NULL,
  `sale_price` decimal(25,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `categorie_id` int(11) UNSIGNED NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `order_image` int(11) NOT NULL,
  `date` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_locations`
--

CREATE TABLE `product_locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `account` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Disparadores `product_locations`
--
DELIMITER $$
CREATE TRIGGER `trg_product_locations_insert` AFTER INSERT ON `product_locations` FOR EACH ROW BEGIN
    UPDATE products
    SET qty = (
        SELECT IFNULL(SUM(qty),0)
        FROM product_locations
        WHERE product_id = NEW.product_id
    )
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_product_locations_update` AFTER UPDATE ON `product_locations` FOR EACH ROW BEGIN
    UPDATE products
    SET qty = (
        SELECT IFNULL(SUM(qty),0)
        FROM product_locations
        WHERE product_id = NEW.product_id
    )
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales`
--

CREATE TABLE `sales` (
  `id` int(11) UNSIGNED NOT NULL,
  `client_id` int(11) UNSIGNED NOT NULL,
  `status` enum('En Validación','Emitida','Cancelada') NOT NULL DEFAULT 'En Validación',
  `sale_type` enum('Diaria','Mayorista','Mercado Libre') NOT NULL DEFAULT 'Diaria',
  `total` decimal(25,2) UNSIGNED NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(1000) DEFAULT NULL,
  `note` varchar(5000) DEFAULT NULL,
  `user` varchar(20) DEFAULT NULL,
  `user_status` varchar(20) DEFAULT NULL,
  `account` int(11) UNSIGNED NOT NULL,
  `date` timestamp NULL DEFAULT current_timestamp(),
  `date_delivery` timestamp NULL DEFAULT NULL,
  `account_sender` int(11) UNSIGNED NOT NULL,
  `order_ml` varchar(50) DEFAULT NULL COMMENT 'ID de orden de Mercado Libre',
  `status_ml` enum('payment_required','paid','in_process','ready_to_ship','shipped','delivered','cancelled','N/A') NOT NULL DEFAULT 'payment_required' COMMENT 'Estado real de la orden en Mercado Libre',
  `commission_ml` decimal(25,2) DEFAULT NULL COMMENT 'Comisión total descontada por Mercado Libre en la venta',
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales_detail`
--

CREATE TABLE `sales_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `sale_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `location_product_id` int(11) UNSIGNED DEFAULT NULL,
  `dispatch_type` enum('Interno','Externo') NOT NULL DEFAULT 'Interno',
  `qty` int(11) DEFAULT NULL,
  `price` decimal(25,2) UNSIGNED NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discounted_price` decimal(25,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(25,2) NOT NULL DEFAULT 0.00,
  `total` decimal(25,2) GENERATED ALWAYS AS (`qty` * `price`) STORED,
  `note` varchar(5000) DEFAULT NULL,
  `account` int(11) UNSIGNED NOT NULL,
  `date` timestamp NULL DEFAULT current_timestamp(),
  `item_ml` varchar(50) DEFAULT NULL COMMENT 'ID del item en Mercado Libre',
  `variation_ml` varchar(50) DEFAULT NULL COMMENT 'ID de la variación en Mercado Libre',
  `delivered_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad entregada',
  `return_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad devuelta',
  `status` enum('not_delivered','shipped','delivered','returned','partially_returned','N/A') NOT NULL DEFAULT 'not_delivered' COMMENT 'Estado real del item en Mercado Libre',
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_level` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'no_image.jpg',
  `status` int(1) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `user_level`, `image`, `status`, `last_login`, `active`) VALUES
(1, 'Jaime Parra', 'jaime', 'd54eb6dcaa753f1b0d19992d93ab64d93722a9cb', 1, 'no_image.jpg', 1, NULL, 1),
(2, 'Gina Cardona', 'gina', 'd54eb6dcaa753f1b0d19992d93ab64d93722a9cb', 1, 'no_image.jpg', 1, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_accounts`
--

CREATE TABLE `user_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `account_id` int(11) UNSIGNED NOT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_accounts`
--

INSERT INTO `user_accounts` (`id`, `user_id`, `account_id`, `date`, `active`) VALUES
(1, 1, 1, '2026-03-12 12:26:33', 1),
(2, 2, 1, '2026-03-28 13:36:33', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_level` int(11) NOT NULL,
  `group_status` int(1) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `user_groups`
--

INSERT INTO `user_groups` (`id`, `group_name`, `group_level`, `group_status`, `active`) VALUES
(1, 'Administrador', 1, 1, 1),
(2, 'Usuario Comun', 2, 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `account_movements`
--
ALTER TABLE `account_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`account`),
  ADD KEY `idx_movements_account` (`financial_account_id`),
  ADD KEY `idx_movements_related` (`related_table`,`related_id`);

--
-- Indices de la tabla `account_opening_hours`
--
ALTER TABLE `account_opening_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_account_day` (`id_account`,`day_week`),
  ADD KEY `idx_account` (`id_account`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categories_account` (`account`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_clients_account` (`account`);

--
-- Indices de la tabla `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account` (`account`),
  ADD KEY `idx_dni` (`dni`);

--
-- Indices de la tabla `employees_attendance`
--
ALTER TABLE `employees_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_account` (`account`),
  ADD KEY `idx_check_in` (`check_in`);

--
-- Indices de la tabla `financial_accounts`
--
ALTER TABLE `financial_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`account`);

--
-- Indices de la tabla `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_inventory` (`product_id`),
  ADD KEY `fk_inventory_account` (`account`),
  ADD KEY `location_id` (`location_id`);

--
-- Indices de la tabla `inventory_transfer`
--
ALTER TABLE `inventory_transfer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_origin` (`product_id_origin`),
  ADD KEY `idx_product_destination` (`product_id_destination`),
  ADD KEY `idx_receiving_account` (`receiving_account_id`),
  ADD KEY `idx_account` (`account`);

--
-- Indices de la tabla `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account` (`account`);

--
-- Indices de la tabla `meli_event`
--
ALTER TABLE `meli_event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account` (`account`),
  ADD KEY `idx_topic` (`topic`),
  ADD KEY `idx_resource` (`resource`);

--
-- Indices de la tabla `meli_event_detail`
--
ALTER TABLE `meli_event_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_entity` (`event_id`,`entity_type`,`entity_id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_account` (`account`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `fk_products_account` (`account`);

--
-- Indices de la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_images_product` (`product_id`),
  ADD KEY `fk_product_images_account` (`account`);

--
-- Indices de la tabla `product_locations`
--
ALTER TABLE `product_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_location_unique` (`product_id`,`location_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `account` (`account`);

--
-- Indices de la tabla `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_client` (`client_id`),
  ADD KEY `fk_sales_account` (`account`),
  ADD KEY `fk_sales_account_sender` (`account_sender`);

--
-- Indices de la tabla `sales_detail`
--
ALTER TABLE `sales_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_salesdetail_sale` (`sale_id`),
  ADD KEY `fk_salesdetail_product` (`product_id`),
  ADD KEY `fk_salesdetail_account` (`account`),
  ADD KEY `idx_sales_detail_location_product` (`location_product_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `user_level` (`user_level`);

--
-- Indices de la tabla `user_accounts`
--
ALTER TABLE `user_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_accounts_user` (`user_id`),
  ADD KEY `idx_user_accounts_account` (`account_id`);

--
-- Indices de la tabla `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_level` (`group_level`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `account_movements`
--
ALTER TABLE `account_movements`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `account_opening_hours`
--
ALTER TABLE `account_opening_hours`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `employees_attendance`
--
ALTER TABLE `employees_attendance`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `financial_accounts`
--
ALTER TABLE `financial_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventory_transfer`
--
ALTER TABLE `inventory_transfer`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `meli_event`
--
ALTER TABLE `meli_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `meli_event_detail`
--
ALTER TABLE `meli_event_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `product_locations`
--
ALTER TABLE `product_locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sales_detail`
--
ALTER TABLE `sales_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `user_accounts`
--
ALTER TABLE `user_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `account_movements`
--
ALTER TABLE `account_movements`
  ADD CONSTRAINT `account_movements_ibfk_1` FOREIGN KEY (`financial_account_id`) REFERENCES `financial_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `account_movements_ibfk_2` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `account_opening_hours`
--
ALTER TABLE `account_opening_hours`
  ADD CONSTRAINT `fk_account_opening_hours_account` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employee_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `employees_attendance`
--
ALTER TABLE `employees_attendance`
  ADD CONSTRAINT `fk_attendance_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `financial_accounts`
--
ALTER TABLE `financial_accounts`
  ADD CONSTRAINT `financial_accounts_ibfk_1` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_inventory` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_location_fk` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventory_transfer`
--
ALTER TABLE `inventory_transfer`
  ADD CONSTRAINT `fk_transfer_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transfer_product_destination` FOREIGN KEY (`product_id_destination`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transfer_product_origin` FOREIGN KEY (`product_id_origin`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transfer_receiving_account` FOREIGN KEY (`receiving_account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_account_fk` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `meli_event`
--
ALTER TABLE `meli_event`
  ADD CONSTRAINT `fk_meli_events_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `meli_event_detail`
--
ALTER TABLE `meli_event_detail`
  ADD CONSTRAINT `fk_meli_event_detail_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_meli_event_detail_event` FOREIGN KEY (`event_id`) REFERENCES `meli_event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `FK_products` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `product_locations`
--
ALTER TABLE `product_locations`
  ADD CONSTRAINT `pl_account_fk` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pl_location_fk` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pl_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_account_sender` FOREIGN KEY (`account_sender`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sales_detail`
--
ALTER TABLE `sales_detail`
  ADD CONSTRAINT `fk_sales_detail_location_product` FOREIGN KEY (`location_product_id`) REFERENCES `product_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salesdetail_account` FOREIGN KEY (`account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salesdetail_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salesdetail_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_user` FOREIGN KEY (`user_level`) REFERENCES `user_groups` (`group_level`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `user_accounts`
--
ALTER TABLE `user_accounts`
  ADD CONSTRAINT `fk_user_accounts_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
