-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-03-2026 a las 21:57:47
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
-- Base de datos: `nutrimax`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comidas_consumidas`
--

CREATE TABLE `comidas_consumidas` (
  `ID_Comidas` int(11) NOT NULL,
  `ID_REG` varchar(36) DEFAULT NULL,
  `ID_RECETA` varchar(36) DEFAULT NULL,
  `tipo` varchar(8) DEFAULT NULL,
  `porcion` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingredientes`
--

CREATE TABLE `ingredientes` (
  `ID` int(11) NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  `kcals` int(11) DEFAULT NULL,
  `prot` float DEFAULT NULL,
  `carbo` float DEFAULT NULL,
  `gras` float DEFAULT NULL,
  `ID_USER` varchar(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ingredientes`
--

INSERT INTO `ingredientes` (`ID`, `name`, `kcals`, `prot`, `carbo`, `gras`, `ID_USER`) VALUES
(1, 'Brócoli', 34, 2.8, 6.6, 0.4, NULL),
(2, 'Pechuga de pollo', 165, 31, 0, 3.6, NULL),
(3, 'Arroz integral', 111, 2.6, 23, 0.9, NULL),
(4, 'Huevo entero', 155, 13, 1.1, 11, NULL),
(5, 'Clara de huevo', 52, 11, 0.7, 0.2, NULL),
(6, 'Salmón', 208, 20, 0, 13, NULL),
(7, 'Atún', 132, 28, 0, 1, NULL),
(8, 'Avena', 389, 16.9, 66.3, 6.9, NULL),
(9, 'Batata', 86, 1.6, 20.1, 0.1, NULL),
(10, 'Papa', 77, 2, 17, 0.1, NULL),
(11, 'Espinaca', 23, 2.9, 3.6, 0.4, NULL),
(12, 'Zanahoria', 41, 0.9, 9.6, 0.2, NULL),
(13, 'Tomate', 18, 0.9, 3.9, 0.2, NULL),
(14, 'Palta', 160, 2, 9, 15, NULL),
(15, 'Almendras', 579, 21, 22, 50, NULL),
(16, 'Nueces', 654, 15, 14, 65, NULL),
(17, 'Yogur griego', 59, 10, 3.6, 0.4, NULL),
(18, 'Leche descremada', 34, 3.4, 5, 0.1, NULL),
(19, 'Lentejas', 116, 9, 20, 0.4, NULL),
(20, 'Garbanzos', 164, 8.9, 27, 2.6, NULL),
(21, 'Quinoa', 120, 4.4, 21.3, 1.9, NULL),
(22, 'Tofu', 76, 8, 1.9, 4.8, NULL),
(23, 'Carne magra', 250, 26, 0, 15, NULL),
(24, 'Pavo', 135, 29, 0, 1, NULL),
(25, 'Manzana', 52, 0.3, 14, 0.2, NULL),
(26, 'Banana', 89, 1.1, 23, 0.3, NULL),
(27, 'Frutilla', 32, 0.7, 7.7, 0.3, NULL),
(28, 'Arándanos', 57, 0.7, 14.5, 0.3, NULL),
(29, 'Mango', 60, 0.8, 15, 0.4, NULL),
(30, 'Piña', 50, 0.5, 13, 0.1, NULL),
(31, 'Pepino', 16, 0.7, 3.6, 0.1, NULL),
(32, 'Lechuga', 15, 1.4, 2.9, 0.2, NULL),
(33, 'Coliflor', 25, 1.9, 5, 0.3, NULL),
(34, 'Kale', 49, 4.3, 9, 0.9, NULL),
(35, 'Remolacha', 43, 1.6, 10, 0.2, NULL),
(36, 'Chía', 486, 17, 42, 31, NULL),
(37, 'Semillas de lino', 534, 18, 29, 42, NULL),
(38, 'Pan integral', 247, 13, 41, 4.2, NULL),
(39, 'Pasta integral', 124, 5, 25, 0.6, NULL),
(40, 'Aceite de oliva', 884, 0, 0, 100, NULL),
(41, 'Queso bajo en grasa', 200, 25, 3, 10, NULL),
(42, 'Ricota', 174, 11, 3, 13, NULL),
(43, 'Jamón cocido', 145, 21, 1.5, 6, NULL),
(44, 'Pollo molido', 143, 17, 0, 8, NULL),
(45, 'Carne de cerdo magra', 242, 27, 0, 14, NULL),
(46, 'Hummus', 166, 8, 14, 9.6, NULL),
(47, 'Porotos negros', 132, 8.9, 24, 0.5, NULL),
(48, 'Porotos rojos', 127, 8.7, 22.8, 0.5, NULL),
(49, 'Edamame', 121, 11, 10, 5, NULL),
(50, 'Leche de almendra', 17, 0.6, 0.3, 1.2, NULL),
(51, 'Leche de soja', 54, 3.3, 6, 1.8, NULL),
(52, 'Proteína en polvo', 400, 80, 10, 5, NULL),
(53, 'Chocolate negro', 546, 4.9, 61, 31, NULL),
(54, 'Miel', 304, 0.3, 82, 0, NULL),
(55, 'Azúcar mascabo', 380, 0, 98, 0, NULL),
(56, 'Pera', 57, 0.4, 15, 0.1, NULL),
(57, 'Durazno', 39, 0.9, 10, 0.3, NULL),
(58, 'Ciruela', 46, 0.7, 11, 0.3, NULL),
(59, 'Kiwi', 61, 1.1, 15, 0.5, NULL),
(60, 'Naranja', 47, 0.9, 12, 0.1, NULL),
(61, 'Mandarina', 53, 0.8, 13, 0.3, NULL),
(62, 'Uva', 69, 0.7, 18, 0.2, NULL),
(63, 'Sandía', 30, 0.6, 8, 0.2, NULL),
(64, 'Melón', 34, 0.8, 8, 0.2, NULL),
(65, 'Coco', 354, 3.3, 15, 33, NULL),
(66, 'Harina de avena', 389, 16.9, 66, 6.9, NULL),
(67, 'Harina integral', 340, 13, 72, 2.5, NULL),
(68, 'Couscous', 112, 3.8, 23, 0.2, NULL),
(69, 'Cebada', 123, 2.3, 28, 0.4, NULL),
(70, 'Maíz', 96, 3.4, 21, 1.5, NULL),
(71, 'Polenta', 70, 1.7, 15, 0.3, NULL),
(72, 'Queso cheddar', 403, 25, 1.3, 33, NULL),
(73, 'Queso mozzarella', 280, 28, 3, 17, NULL),
(74, 'Yogur natural', 61, 3.5, 4.7, 3.3, NULL),
(75, 'Kefir', 41, 3.3, 4.5, 1, NULL),
(76, 'Champiñones', 22, 3.1, 3.3, 0.3, NULL),
(77, 'Berenjena', 25, 1, 6, 0.2, NULL),
(78, 'Zapallo', 26, 1, 7, 0.1, NULL),
(79, 'Zucchini', 17, 1.2, 3.1, 0.3, NULL),
(80, 'Cebolla', 40, 1.1, 9.3, 0.1, NULL),
(81, 'Ajo', 149, 6.4, 33, 0.5, NULL),
(82, 'Jengibre', 80, 1.8, 18, 0.8, NULL),
(83, 'Maní', 567, 26, 16, 49, NULL),
(84, 'Mantequilla de maní', 588, 25, 20, 50, NULL),
(85, 'Semillas de girasol', 584, 21, 20, 51, NULL),
(86, 'Semillas de calabaza', 559, 30, 11, 49, NULL),
(87, 'Granola', 471, 10, 64, 20, NULL),
(88, 'Barrita proteica', 350, 20, 40, 10, NULL),
(89, 'Pasta blanca', 131, 5, 25, 1.1, NULL),
(90, 'Arroz blanco', 130, 2.7, 28, 0.3, NULL),
(91, 'Fideos de arroz', 109, 1.8, 25, 0.2, NULL),
(92, 'Tortilla de trigo', 218, 6, 36, 5, NULL),
(93, 'Pan de centeno', 259, 9, 48, 3.3, NULL),
(94, 'Leche entera', 60, 3.2, 5, 3.3, NULL),
(95, 'Crema de leche', 340, 2, 3, 36, NULL),
(96, 'Helado', 207, 3.5, 24, 11, NULL),
(97, 'Chocolate con leche', 535, 7, 59, 30, NULL),
(98, 'Bebida isotónica', 24, 0, 6, 0, NULL),
(99, 'Café', 1, 0.1, 0, 0, NULL),
(100, 'Té', 1, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `ID_RECETA` varchar(36) NOT NULL DEFAULT uuid(),
  `ID_USER` varchar(36) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `descrip` varchar(250) DEFAULT NULL,
  `instr` varchar(500) DEFAULT NULL,
  `porciones` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`ID_RECETA`, `ID_USER`, `name`, `descrip`, `instr`, `porciones`) VALUES
('09123456-9012-2109-cdef-90123456fabc', NULL, 'Hummus con vegetales', 'Snack salado, cremoso. Garbanzos y aceite de oliva. ~10 min.', '1. Procesar garbanzos. 2. Servir con zanahoria en bastones.', 2),
('0952f6a1-223f-11f1-9409-0a002700000d', NULL, 'Pollo con Arroz Fit', 'Salteado completo de pollo con vegetales y arroz blanco. Alto en proteína y fibra.', '1. Hervir el arroz. 2. Saltear el pollo en cubos con aceite. 3. Agregar zanahoria picada y brócoli. 4. Mezclar con el arroz y servir.', 2),
('10234567-0123-3210-defa-01234567abcd', NULL, 'Tortilla de papa fit', 'Plato salado, clásico adaptado. Papa y huevo. ~20 min.', '1. Cocinar papa. 2. Mezclar con huevo. 3. Cocinar en sartén.', 2),
('21345678-1234-4321-abcd-1234567890ab', NULL, 'Smoothie de banana', 'Bebida dulce, ideal post-entreno. Contiene leche, proteína en polvo y banana. ~5 min.', '1. Colocar ingredientes en licuadora. 2. Licuar hasta homogeneizar.', 1),
('21345678-1234-4321-abcd-12345678f001', NULL, 'Yogur con chía', 'Snack dulce, rico en omega 3. Yogur, chía y frutas. ~5 min.', '1. Mezclar yogur con chía. 2. Agregar frutas.', 1),
('32456789-2345-5432-bcde-234567890abc', NULL, 'Salteado de tofu', 'Plato vegano, salado. Incluye tofu, brócoli y zanahoria. ~20 min.', '1. Cortar tofu. 2. Saltear verduras. 3. Agregar tofu y cocinar.', 2),
('32456789-2345-5432-bcde-23456789f002', NULL, 'Pollo con quinoa', 'Plato completo, salado. Pollo, quinoa y vegetales. ~25 min.', '1. Cocinar quinoa. 2. Cocinar pollo. 3. Mezclar con verduras.', 2),
('43567890-3456-6543-cdef-34567890abcd', NULL, 'Pasta con pollo', 'Plato completo, salado. Incluye pasta integral y pollo. ~25 min.', '1. Cocinar pasta. 2. Cocinar pollo. 3. Mezclar y servir.', 2),
('43567890-3456-6543-cdef-34567890f003', NULL, 'Ensalada atún', 'Baja en calorías, salada. Atún, lechuga y tomate. ~10 min.', '1. Mezclar todos los ingredientes. 2. Aliñar.', 1),
('54678901-4567-7654-defa-45678901abcd', NULL, 'Bowl de frutas', 'Preparación dulce, refrescante. Incluye frutas variadas. ~5 min.', '1. Cortar frutas. 2. Mezclar en bowl.', 1),
('54678901-4567-7654-defa-45678901f004', NULL, 'Panqueques de avena', 'Desayuno dulce y fit. Avena, huevo y leche. ~15 min.', '1. Mezclar ingredientes. 2. Cocinar en sartén.', 2),
('550e8400-e29b-41d4-a716-446655440000', NULL, 'Ensalada de pollo', 'Ensalada salada, fresca y rica en proteínas y grasas saludables. Incluye pollo, palta, tomate y lechuga. Preparación rápida (~15 min).', '1. Cocinar pechuga de pollo a la plancha. 2. Cortar en cubos. 3. Picar palta, tomate y lechuga. 4. Mezclar todo. 5. Agregar aceite de oliva y sal.', 2),
('65789012-5678-8765-efab-56789012bcde', NULL, 'Arroz con atún', 'Plato simple, salado y proteico. Arroz y atún. ~15 min.', '1. Cocinar arroz. 2. Mezclar con atún y aceite.', 1),
('65789012-5678-8765-efab-56789012f005', NULL, 'Frutas con yogur', 'Postre dulce y saludable. Frutas y yogur. ~5 min.', '1. Cortar frutas. 2. Mezclar con yogur.', 1),
('6ba7b810-9dad-11d1-80b4-00c04fd430c8', NULL, 'Bowl arroz y salmón', 'Plato completo, salado, balanceado en macros. Contiene arroz integral, salmón y brócoli. Tiempo ~25 min.', '1. Cocinar arroz integral. 2. Cocinar salmón a la plancha. 3. Hervir brócoli. 4. Servir todo en bowl.', 2),
('74738ff5-5367-4358-9189-28d066b2a001', NULL, 'Omelette de espinaca', 'Desayuno salado, alto en proteínas. Lleva huevo y espinaca. Rápido (~10 min).', '1. Batir huevos. 2. Agregar espinaca. 3. Cocinar en sartén antiadherente.', 1),
('76890123-6789-9876-fabc-67890123cdef', NULL, 'Espinaca y nueces', 'Ensalada salada con grasas saludables. Espinaca, nueces y queso. ~10 min.', '1. Lavar espinaca. 2. Agregar nueces y queso. 3. Mezclar.', 2),
('87901234-7890-0987-abcd-78901234defa', NULL, 'Wrap de pollo', 'Comida práctica, salada. Incluye tortilla, pollo y vegetales. ~15 min.', '1. Cocinar pollo. 2. Rellenar tortilla. 3. Enrollar.', 1),
('98012345-8901-1098-bcde-89012345efab', NULL, 'Batido verde detox', 'Bebida ligera, refrescante. Espinaca, manzana y pepino. ~5 min.', '1. Licuar todos los ingredientes.', 1),
('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', NULL, 'Ensalada garbanzos', 'Plato fresco, salado y proteico. Incluye garbanzos, tomate y cebolla. ~15 min.', '1. Cocinar garbanzos. 2. Cortar vegetales. 3. Mezclar y aliñar.', 2),
('a2b2c3d4-e5f6-4a5b-8c9d-0e1f2a3b4c5d', NULL, 'Avena con frutas', 'Desayuno dulce, energético. Contiene avena, leche, banana y arándanos. Tiempo ~10 min.', '1. Cocinar avena con leche. 2. Agregar frutas cortadas. 3. Mezclar.', 1),
('b1c2d3e4-f5a6-4b5c-9d0e-1f2a3b4c5d6e', NULL, 'Ensalada de quinoa', 'Plato fresco, salado y rico en fibra. Incluye quinoa, pepino y tomate. ~20 min.', '1. Cocinar quinoa. 2. Cortar vegetales. 3. Mezclar con aceite de oliva.', 2),
('c2d3e4f5-a6b7-4c5d-0e1f-2a3b4c5d6e7f', NULL, 'Lentejas guisadas', 'Plato caliente, salado, alto en proteína vegetal. Lleva lentejas y verduras. ~30 min.', '1. Hervir lentejas. 2. Saltear cebolla y zanahoria. 3. Mezclar todo y cocinar.', 3),
('d3e4f5a6-b7c8-4d5e-1f2a-3b4c5d6e7f8a', NULL, 'Pollo con batata', 'Plato salado, clásico fitness. Incluye pollo y batata. ~30 min.', '1. Cortar batata. 2. Condimentar pollo. 3. Hornear ambos 25 min.', 2),
('e4f5a6b7-c8d9-4e5f-2a3b-4c5d6e7f8a9b', NULL, 'Yogur con granola', 'Snack dulce, nutritivo. Lleva yogur, granola y frutas. ~5 min.', '1. Colocar yogur en bowl. 2. Agregar granola y frutas.', 1),
('f5a6b7c8-d9e0-4f5a-3b4c-5d6e7f8a9b0c', NULL, 'Tostadas con palta', 'Desayuno salado con grasas saludables. Pan integral y palta. ~5 min.', '1. Tostar pan. 2. Pisar palta. 3. Untar y servir.', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas_ingredientes`
--

CREATE TABLE `recetas_ingredientes` (
  `ID` int(11) NOT NULL,
  `ID_RESETA` varchar(36) DEFAULT NULL,
  `ID_Ingred` int(11) DEFAULT NULL,
  `Cant_gr` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `recetas_ingredientes`
--

INSERT INTO `recetas_ingredientes` (`ID`, `ID_RESETA`, `ID_Ingred`, `Cant_gr`) VALUES
(1, '550e8400-e29b-41d4-a716-446655440000', 2, 200),
(2, '550e8400-e29b-41d4-a716-446655440000', 14, 100),
(3, '550e8400-e29b-41d4-a716-446655440000', 13, 100),
(4, '550e8400-e29b-41d4-a716-446655440000', 32, 50),
(5, '550e8400-e29b-41d4-a716-446655440000', 40, 10),
(6, '6ba7b810-9dad-11d1-80b4-00c04fd430c8', 3, 150),
(7, '6ba7b810-9dad-11d1-80b4-00c04fd430c8', 6, 150),
(8, '6ba7b810-9dad-11d1-80b4-00c04fd430c8', 1, 100),
(9, '74738ff5-5367-4358-9189-28d066b2a001', 4, 100),
(10, '74738ff5-5367-4358-9189-28d066b2a001', 11, 50),
(11, 'a2b2c3d4-e5f6-4a5b-8c9d-0e1f2a3b4c5d', 8, 50),
(12, 'a2b2c3d4-e5f6-4a5b-8c9d-0e1f2a3b4c5d', 18, 200),
(13, 'a2b2c3d4-e5f6-4a5b-8c9d-0e1f2a3b4c5d', 26, 100),
(14, 'a2b2c3d4-e5f6-4a5b-8c9d-0e1f2a3b4c5d', 28, 30),
(15, 'b1c2d3e4-f5a6-4b5c-9d0e-1f2a3b4c5d6e', 21, 100),
(16, 'b1c2d3e4-f5a6-4b5c-9d0e-1f2a3b4c5d6e', 31, 100),
(17, 'b1c2d3e4-f5a6-4b5c-9d0e-1f2a3b4c5d6e', 13, 100),
(18, 'c2d3e4f5-a6b7-4c5d-0e1f-2a3b4c5d6e7f', 19, 150),
(19, 'c2d3e4f5-a6b7-4c5d-0e1f-2a3b4c5d6e7f', 80, 50),
(20, 'c2d3e4f5-a6b7-4c5d-0e1f-2a3b4c5d6e7f', 12, 50),
(21, 'd3e4f5a6-b7c8-4d5e-1f2a-3b4c5d6e7f8a', 2, 200),
(22, 'd3e4f5a6-b7c8-4d5e-1f2a-3b4c5d6e7f8a', 9, 200),
(23, '21345678-1234-4321-abcd-1234567890ab', 18, 250),
(24, '21345678-1234-4321-abcd-1234567890ab', 52, 30),
(25, '21345678-1234-4321-abcd-1234567890ab', 26, 100),
(26, '65789012-5678-8765-efab-56789012bcde', 90, 150),
(27, '65789012-5678-8765-efab-56789012bcde', 7, 100),
(28, '10234567-0123-3210-defa-01234567abcd', 10, 200),
(29, '10234567-0123-3210-defa-01234567abcd', 4, 100),
(30, NULL, 2, 250),
(31, NULL, 90, 150),
(32, NULL, 1, 100),
(33, NULL, 12, 50),
(34, NULL, 80, 50),
(35, NULL, 40, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_diario`
--

CREATE TABLE `registro_diario` (
  `ID_REG` varchar(36) NOT NULL DEFAULT uuid(),
  `ID_USER` varchar(36) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `peso` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `ID_USER` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `clave` varchar(255) DEFAULT NULL,
  `nacimiento` date DEFAULT NULL,
  `peso` float DEFAULT NULL,
  `peso_obj` float DEFAULT NULL,
  `genero` varchar(1) DEFAULT NULL,
  `objetivo` varchar(15) DEFAULT NULL,
  `altura_cm` int(11) DEFAULT NULL,
  `act_fisica` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `comidas_consumidas`
--
ALTER TABLE `comidas_consumidas`
  ADD PRIMARY KEY (`ID_Comidas`),
  ADD KEY `ID_REG` (`ID_REG`),
  ADD KEY `ID_RECETA` (`ID_RECETA`);

--
-- Indices de la tabla `ingredientes`
--
ALTER TABLE `ingredientes`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_USER` (`ID_USER`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`ID_RECETA`),
  ADD KEY `ID_USER` (`ID_USER`);

--
-- Indices de la tabla `recetas_ingredientes`
--
ALTER TABLE `recetas_ingredientes`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_RESETA` (`ID_RESETA`),
  ADD KEY `ID_Ingred` (`ID_Ingred`);

--
-- Indices de la tabla `registro_diario`
--
ALTER TABLE `registro_diario`
  ADD PRIMARY KEY (`ID_REG`),
  ADD KEY `ID_USER` (`ID_USER`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID_USER`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `recetas_ingredientes`
--
ALTER TABLE `recetas_ingredientes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comidas_consumidas`
--
ALTER TABLE `comidas_consumidas`
  ADD CONSTRAINT `comidas_consumidas_ibfk_1` FOREIGN KEY (`ID_REG`) REFERENCES `registro_diario` (`ID_REG`),
  ADD CONSTRAINT `comidas_consumidas_ibfk_2` FOREIGN KEY (`ID_RECETA`) REFERENCES `recetas` (`ID_RECETA`);

--
-- Filtros para la tabla `ingredientes`
--
ALTER TABLE `ingredientes`
  ADD CONSTRAINT `ingredientes_ibfk_1` FOREIGN KEY (`ID_USER`) REFERENCES `users` (`ID_USER`);

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`ID_USER`) REFERENCES `users` (`ID_USER`);

--
-- Filtros para la tabla `recetas_ingredientes`
--
ALTER TABLE `recetas_ingredientes`
  ADD CONSTRAINT `recetas_ingredientes_ibfk_1` FOREIGN KEY (`ID_RESETA`) REFERENCES `recetas` (`ID_RECETA`),
  ADD CONSTRAINT `recetas_ingredientes_ibfk_2` FOREIGN KEY (`ID_Ingred`) REFERENCES `ingredientes` (`ID`);

--
-- Filtros para la tabla `registro_diario`
--
ALTER TABLE `registro_diario`
  ADD CONSTRAINT `registro_diario_ibfk_1` FOREIGN KEY (`ID_USER`) REFERENCES `users` (`ID_USER`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
