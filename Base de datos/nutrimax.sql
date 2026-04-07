-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-04-2026 a las 22:10:37
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(100, 'Té', 1, 0, 0, 0, NULL),
(101, 'Cacao en polvo', 228, 20, 58, 14, NULL),
(102, 'Merluza', 89, 19, 0, 1.5, NULL),
(103, 'Camarones', 99, 24, 0.2, 0.3, NULL),
(104, 'Espárragos', 20, 2.2, 3.9, 0.1, NULL),
(105, 'Mantequilla', 717, 0.9, 0.1, 81, NULL),
(106, 'Harina de trigo', 364, 10, 76, 1, NULL),
(107, 'Albahaca', 23, 3.2, 2.7, 0.6, NULL),
(108, 'Curry en polvo', 325, 14.3, 55.8, 14, NULL),
(109, 'Jugo de limón', 22, 0.4, 6.9, 0.2, NULL),
(110, 'Cilantro', 23, 2.1, 3.7, 0.5, NULL),
(111, 'Pimiento rojo', 26, 1, 6, 0.3, NULL),
(112, 'Salsa de tomate', 29, 1.3, 6, 0.2, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `ID_RECETA` varchar(36) NOT NULL DEFAULT uuid(),
  `ID_USER` varchar(36) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `dieta` varchar(15) NOT NULL,
  `descrip` varchar(250) DEFAULT NULL,
  `instr` varchar(500) DEFAULT NULL,
  `porciones` float DEFAULT NULL,
  `emoji` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`ID_RECETA`, `ID_USER`, `name`, `dieta`, `descrip`, `instr`, `porciones`, `emoji`) VALUES
('0d1e2f3a-4b5c-6d7e-8f9a-0b1c2d3e4f5a', NULL, 'Omelette de Queso', '', 'Desayuno o cena exprés, bajo en carbohidratos. ~10 min.', '1. Batir huevos. 2. Volcar en sartén caliente. 3. Agregar queso y jamón, doblar.', 1, '🍳'),
('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', NULL, 'Tarta de Atún', '', 'Masa integral rellena de atún y huevo. ~40 min.', '1. Forrar molde con masa. 2. Mezclar atún, cebolla y huevo. 3. Hornear 30 min.', 4, '🥧'),
('2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e', NULL, 'Batido de Frutos', '', 'Smoothie fresco de frutos rojos. ~5 min.', '1. Licuar leche, frutillas y arándanos. 2. Servir frío.', 1, '🍓'),
('3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f', NULL, 'Hamburguesa Fit', '', 'Hamburguesa casera con pan integral y vegetales. ~20 min.', '1. Armar medallón de carne y cocinar. 2. Tostar pan. 3. Armar con tomate y lechuga.', 1, '🍔'),
('4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a', NULL, 'Tostadas Francesas', '', 'Desayuno dulce y proteico. ~15 min.', '1. Batir huevo y leche. 2. Remojar pan. 3. Dorar en sartén y servir con miel.', 1, '🍞'),
('5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b', NULL, 'Ensalada Caprese', '', 'Clásica ensalada italiana, fresca y ligera. ~10 min.', '1. Cortar tomate y queso en rodajas. 2. Alternar con hojas de albahaca. 3. Aliñar con oliva.', 2, '🥗'),
('6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c', NULL, 'Pollo al Curry', '', 'Plato especiado con arroz blanco. ~30 min.', '1. Saltear pollo. 2. Agregar crema y curry, cocinar a fuego lento. 3. Servir con arroz.', 2, '🍛'),
('7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d', NULL, 'Ceviche de Merluza', '', 'Pescado marinado en limón. Fresco y sin cocción. ~20 min.', '1. Cortar pescado en cubos. 2. Macerar en limón 15 min. 3. Agregar cebolla y cilantro.', 2, '🐟'),
('8b9c0d1e-2f3a-4b5c-6d7e-8f9a0b1c2d3e', NULL, 'Fajitas de Pollo', '', 'Clásico mexicano adaptado y saludable. ~20 min.', '1. Cortar pollo y pimientos en tiras. 2. Saltear todo. 3. Armar fajitas en tortillas.', 2, '🌯'),
('9c0d1e2f-3a4b-5c6d-7e8f-9a0b1c2d3e4f', NULL, 'Pudding de Chía', '', 'Postre o merienda que se prepara solo en la heladera.', '1. Mezclar chía y leche. 2. Refrigerar 4 horas. 3. Servir con mango picado.', 1, '🍮'),
('a0c3f4d5-6e7f-8a9b-0c1d-2e3f4a5b6c7d', NULL, 'Muffins de Huevo', '', 'Desayuno o snack proteico al horno. ~25 min.', '1. Batir huevos. 2. Picar espinaca. 3. Mezclar con queso, poner en moldes y hornear 20 min.', 2, '🧁'),
('a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6', NULL, 'Lasaña Berenjena', '', 'Lasaña low-carb usando láminas de berenjena. ~45 min.', '1. Cortar berenjena en láminas. 2. Intercalar con carne, salsa y queso en fuente. 3. Hornear.', 4, '🍆'),
('a1c9b5d2-8e2f-46a7-bd4c-9e1f5a2b8d3c', NULL, 'Frutas con yogur', '', 'Postre dulce y saludable. Frutas y yogur. ~5 min.', '1. Cortar frutas. 2. Mezclar con yogur.', 1, '🍦'),
('a3c5b9d1-8e2f-46a7-bd4c-9e1f5a2b8d3c', NULL, 'Pasta con pollo', '', 'Plato completo, salado. Incluye pasta integral y pollo. ~25 min.', '1. Cocinar pasta. 2. Cocinar pollo. 3. Mezclar y servir.', 2, '🍝'),
('a7d9f2c1-3e5b-48a6-bd4e-9c1f7a2b5d8e', NULL, 'Pollo con Arroz Fit', '', 'Salteado completo de pollo con vegetales y arroz blanco.', '1. Hervir el arroz. 2. Saltear el pollo. 3. Agregar zanahoria y brócoli. 4. Mezclar.', 2, '🍗'),
('a8c1b9d5-8e2f-46a7-bd4c-9e1f5a2b8d3c', NULL, 'Ensalada garbanzos', '', 'Plato fresco, salado y proteico. ~15 min.', '1. Cocinar garbanzos. 2. Cortar vegetales. 3. Mezclar y aliñar.', 2, '🥗'),
('a9c5b1d8-8e2f-46a7-bd4c-9e1f5a2b8d3c', NULL, 'Tostadas con palta', '', 'Desayuno salado con grasas saludables. ~5 min.', '1. Tostar pan. 2. Pisar palta. 3. Untar y servir.', 1, '🥑'),
('b1d4a5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e', NULL, 'Sopa de Calabaza', '', 'Clásica sopa de zapallo, cremosa y reconfortante. ~30 min.', '1. Hervir zapallo y cebolla. 2. Licuar todo. 3. Calentar agregando un toque de crema.', 2, '🥣'),
('b1d8f5a2-9c3e-46b7-a4d1-2e8c5f9b3a7d', NULL, 'Espinaca y nueces', '', 'Ensalada salada con grasas saludables. ~10 min.', '1. Lavar espinaca. 2. Agregar nueces y queso. 3. Mezclar.', 2, '🥜'),
('b2c3d4e5-f6a7-b8c9-d0e1-f2a3b4c5d6e7', NULL, 'Helado de Banana', '', 'Helado cremoso de un solo ingrediente base. ~5 min.', '1. Congelar banana en rodajas. 2. Licuar con yogur y mantequilla de maní hasta cremar.', 2, '🍨'),
('b2d5f8a1-7c9e-43a6-9b1d-4e8c2f5a7b3d', NULL, 'Yogur con chía', '', 'Snack dulce, rico en omega 3. Yogur, chía y frutas. ~5 min.', '1. Mezclar yogur con chía. 2. Agregar frutas.', 1, '🥣'),
('b4f1c9d2-8e3a-4a7b-9c6d-2f5e8b1a4c3f', NULL, 'Brownies Chocolate', '', 'Clásicos brownies húmedos y llenos de sabor a cacao. ~45 min.', '1. Derretir chocolate con mantequilla. 2. Mezclar harina. 3. Hornear a 180°C por 25 min.', 4, '🍫'),
('b5d1f8a2-9c3e-46b7-a4d1-2e8c5f9b3a7d', NULL, 'Panqueques de avena', '', 'Desayuno dulce y fit. Avena, huevo y leche. ~15 min.', '1. Mezclar ingredientes. 2. Cocinar en sartén.', 2, '🥞'),
('b8d1f5a9-9c3e-46b7-a4d1-2e8c5f9b3a7d', NULL, 'Lentejas guisadas', '', 'Plato caliente, alto en proteína vegetal. ~30 min.', '1. Hervir lentejas. 2. Saltear vegetales. 3. Mezclar y cocinar.', 3, '🍲'),
('b9d8f1a5-9c3e-46b7-a4d1-2e8c5f9b3a7d', NULL, 'Wok de camarones', '', 'Comida asiática fit. Camarones, fideos de arroz y vegetales.', '1. Hervir fideos. 2. Saltear camarones y vegetales. 3. Integrar.', 2, '🍤'),
('c1e9d5a4-4b9f-48c3-8a1e-7d6f9b2c5a4d', NULL, 'Batido de chocolate', '', 'Shake alto en proteína. Leche, whey, cacao y banana.', '1. Poner todo en licuadora. 2. Licuar hasta espumar.', 1, '🍫'),
('c2d5f8e1-3e5b-48a6-bd4e-9c1f7a2b5d8e', NULL, 'Crema Champiñones', '', 'Sopa cremosa y reconfortante de champiñones frescos. ~30 min.', '1. Picar cebolla y champiñones. 2. Sofreír 10 min. 3. Licuar con crema y calentar sin hervir.', 2, '🍲'),
('c2e5b6f7-8a9b-0c1d-2e3f-4a5b6c7d8e9f', NULL, 'Galletas de Avena', '', 'Galletas dulces sin harina, solo 3 ingredientes. ~20 min.', '1. Pisar banana y mezclar con avena. 2. Agregar chips de chocolate. 3. Formar galletas y hornear 15 min.', 3, '🍪'),
('c3e5d2a1-4b9f-48c3-8a1e-7d6f9b2c5a4d', NULL, 'Bowl arroz y salmón', '', 'Plato completo, balanceado. Arroz, salmón y brócoli.', '1. Cocinar arroz. 2. Plancha el salmón. 3. Hervir brócoli.', 2, '🍣'),
('c5e1a8d4-9b2f-47c3-8a6e-1d5f9b2c4a7d', NULL, 'Tortilla de papa fit', '', 'Plato salado, clásico adaptado. Papa y huevo. ~20 min.', '1. Cocinar papa. 2. Mezclar con huevo. 3. Cocinar en sartén.', 2, '🍳'),
('c7e2d5a1-4b9f-48c3-8a1e-7d6f9b2c5a4d', NULL, 'Ensalada atún', '', 'Baja en calorías, salada. Atún, lechuga y tomate. ~10 min.', '1. Mezclar todos los ingredientes. 2. Aliñar.', 1, '🐟'),
('c9e4d1a5-4b9f-48c3-8a1e-7d6f9b2c5a4d', NULL, 'Avena con frutas', '', 'Desayuno dulce, energético. Avena, leche y frutas.', '1. Cocinar avena con leche. 2. Agregar frutas. 3. Mezclar.', 1, '🥣'),
('d1a9c4b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', NULL, 'Pollo con batata', '', 'Plato salado, clásico fitness. Pollo y batata. ~30 min.', '1. Cortar batata. 2. Condimentar pollo. 3. Hornear ambos 25 min.', 2, '🍠'),
('d3f6c7a8-9b0c-1d2e-3f4a-5b6c7d8e9f0a', NULL, 'Risotto de Quinoa', '', 'Alternativa rica en proteínas al clásico risotto. ~25 min.', '1. Saltear cebolla y champiñones. 2. Agregar quinoa y agua de a poco. 3. Finalizar con queso.', 2, '🥘'),
('d4a7c1b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', NULL, 'Ensalada de pollo', '', 'Ensalada salada, fresca y rica en proteínas.', '1. Cocinar pechuga. 2. Cortar en cubos. 3. Mezclar con vegetales.', 2, '🥗'),
('d7a4c9b1-2e9f-45d3-8b6a-1c5e9f2d4a7b', NULL, 'Wrap de pollo', '', 'Comida práctica, salada. Incluye tortilla, pollo y vegetales. ~15 min.', '1. Cocinar pollo. 2. Rellenar tortilla. 3. Enrollar.', 1, '🌯'),
('d8a1c9b4-2e9f-45d3-8b6a-1c5e9f2d4a7b', NULL, 'Porridge de manzana', '', 'Desayuno reconfortante. Avena caliente con manzana y miel.', '1. Cocinar avena con leche. 2. Añadir manzana en cubos y miel.', 1, '🍎'),
('d9a1c4b7-2e8f-45d3-8b6a-1c5e9f2d4a7b', NULL, 'Salteado de tofu', '', 'Plato vegano, salado. Incluye tofu, brócoli y zanahoria. ~20 min.', '1. Cortar tofu. 2. Saltear verduras. 3. Agregar tofu y cocinar.', 2, '🍱'),
('e1f4a8d2-9b3c-47e5-a6d1-5b2c8f9a3d7e', NULL, 'Pollo con quinoa', '', 'Plato completo, salado. Pollo, quinoa y vegetales. ~25 min.', '1. Cocinar quinoa. 2. Cocinar pollo. 3. Mezclar con verduras.', 2, '🥗'),
('e2f8a1d5-9b3c-47e5-a6d1-5b2c8f9a3d7e', NULL, 'Batido verde detox', '', 'Bebida ligera, refrescante. Espinaca, manzana y pepino. ~5 min.', '1. Licuar todos los ingredientes.', 1, '🥦'),
('e4b29c1d-8f3a-4a7b-9c6d-2f5e8b1a4c3d', NULL, 'Hummus con vegetales', '', 'Snack salado, cremoso. Garbanzos y aceite de oliva. ~10 min.', '1. Procesar garbanzos. 2. Servir con zanahoria en bastones.', 2, '🥕'),
('e5f1a8d2-9b3c-47e5-a6d1-5b2c8f9a3d7e', NULL, 'Yogur con granola', '', 'Snack dulce, nutritivo. Yogur, granola y frutas.', '1. Colocar yogur en bowl. 2. Agregar granola y frutas.', 1, '🥣'),
('e8a1d2b3-4c5d-6e7f-8a9b-0c1d2e3f4a5b', NULL, 'Pizza de Avena', '', 'Base de avena crujiente, ideal para cenas ligeras. ~20 min.', '1. Licuar avena y huevo. 2. Cocinar base en sartén. 3. Agregar salsa y queso, tapar hasta derretir.', 1, '🍕'),
('e8f2a5d1-9b3c-47e5-a6d1-5b2c8f9a3d7e', NULL, 'Arroz con atún', '', 'Plato simple, salado y proteico. Arroz y atún. ~15 min.', '1. Cocinar arroz. 2. Mezclar con atún y aceite.', 1, '🍚'),
('f1a5b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', NULL, 'Ensalada de quinoa', '', 'Plato fresco, salado y rico en fibra. Quinoa y vegetales.', '1. Cocinar quinoa. 2. Cortar vegetales. 3. Mezclar.', 2, '🥗'),
('f2a8b3c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', NULL, 'Bowl de frutas', '', 'Preparación dulce, refrescante. Incluye frutas variadas. ~5 min.', '1. Cortar frutas. 2. Mezclar en bowl.', 1, '🍓'),
('f5a1b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', NULL, 'Merluza y espárragos', '', 'Plato keto/low carb. Pescado magro y espárragos salteados.', '1. Sellar espárragos con oliva. 2. Cocinar merluza a la plancha.', 2, '🐟'),
('f8b3c1a2-5d7e-49b6-a4c2-8e1f5d9b3a7c', NULL, 'Smoothie de banana', '', 'Bebida dulce, ideal post-entreno.', '1. Colocar ingredientes en licuadora. 2. Licuar.', 1, '🥤'),
('f9a2b8c3-1d5e-47a6-9c4b-8e1f2d5a7b3c', NULL, 'Omelette de espinaca', '', 'Desayuno salado, alto en proteínas. Huevo y espinaca.', '1. Batir huevos. 2. Agregar espinaca. 3. Cocinar en sartén.', 1, '🥬'),
('f9b2e3c4-5d6e-7f8a-9b0c-1d2e3f4a5b6c', NULL, 'Tacos de Lechuga', '', 'Opción low-carb fresca. Relleno de carne magra. ~15 min.', '1. Saltear carne con cebolla y tomate. 2. Lavar hojas de lechuga. 3. Rellenar las hojas como tacos.', 2, '🌮');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas_ingredientes`
--

CREATE TABLE `recetas_ingredientes` (
  `ID` int(11) NOT NULL,
  `ID_RESETA` varchar(36) DEFAULT NULL,
  `ID_Ingred` int(11) DEFAULT NULL,
  `Cant_gr` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recetas_ingredientes`
--

INSERT INTO `recetas_ingredientes` (`ID`, `ID_RESETA`, `ID_Ingred`, `Cant_gr`) VALUES
(1, 'd4a7c1b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 2, 200),
(2, 'd4a7c1b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 14, 100),
(3, 'd4a7c1b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 13, 100),
(4, 'd4a7c1b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 32, 50),
(5, 'd4a7c1b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 40, 10),
(6, 'c3e5d2a1-4b9f-48c3-8a1e-7d6f9b2c5a4d', 3, 150),
(7, 'c3e5d2a1-4b9f-48c3-8a1e-7d6f9b2c5a4d', 6, 150),
(8, 'c3e5d2a1-4b9f-48c3-8a1e-7d6f9b2c5a4d', 1, 100),
(9, 'f9a2b8c3-1d5e-47a6-9c4b-8e1f2d5a7b3c', 4, 100),
(10, 'f9a2b8c3-1d5e-47a6-9c4b-8e1f2d5a7b3c', 11, 50),
(11, 'c9e4d1a5-4b9f-48c3-8a1e-7d6f9b2c5a4d', 8, 50),
(12, 'c9e4d1a5-4b9f-48c3-8a1e-7d6f9b2c5a4d', 18, 200),
(13, 'c9e4d1a5-4b9f-48c3-8a1e-7d6f9b2c5a4d', 26, 100),
(14, 'c9e4d1a5-4b9f-48c3-8a1e-7d6f9b2c5a4d', 28, 30),
(15, 'f1a5b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 21, 100),
(16, 'f1a5b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 31, 100),
(17, 'f1a5b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 13, 100),
(18, 'b8d1f5a9-9c3e-46b7-a4d1-2e8c5f9b3a7d', 19, 150),
(19, 'b8d1f5a9-9c3e-46b7-a4d1-2e8c5f9b3a7d', 80, 50),
(20, 'b8d1f5a9-9c3e-46b7-a4d1-2e8c5f9b3a7d', 12, 50),
(21, 'd1a9c4b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 2, 200),
(22, 'd1a9c4b8-2e9f-45d3-8b6a-1c5e9f2d4a7b', 9, 200),
(23, 'f8b3c1a2-5d7e-49b6-a4c2-8e1f5d9b3a7c', 18, 250),
(24, 'f8b3c1a2-5d7e-49b6-a4c2-8e1f5d9b3a7c', 52, 30),
(25, 'f8b3c1a2-5d7e-49b6-a4c2-8e1f5d9b3a7c', 26, 100),
(26, 'e8f2a5d1-9b3c-47e5-a6d1-5b2c8f9a3d7e', 90, 150),
(27, 'e8f2a5d1-9b3c-47e5-a6d1-5b2c8f9a3d7e', 7, 100),
(28, 'c5e1a8d4-9b2f-47c3-8a6e-1d5f9b2c4a7d', 10, 200),
(29, 'c5e1a8d4-9b2f-47c3-8a6e-1d5f9b2c4a7d', 4, 100),
(30, 'c1e9d5a4-4b9f-48c3-8a1e-7d6f9b2c5a4d', 18, 250),
(31, 'c1e9d5a4-4b9f-48c3-8a1e-7d6f9b2c5a4d', 52, 30),
(32, 'c1e9d5a4-4b9f-48c3-8a1e-7d6f9b2c5a4d', 101, 15),
(33, 'c1e9d5a4-4b9f-48c3-8a1e-7d6f9b2c5a4d', 26, 100),
(34, 'f5a1b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 102, 200),
(35, 'f5a1b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 104, 150),
(36, 'f5a1b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 40, 10),
(37, 'f5a1b8c9-1d5e-47a6-9c4b-8e1f2d5a7b3c', 10, 100),
(38, 'b9d8f1a5-9c3e-46b7-a4d1-2e8c5f9b3a7d', 103, 150),
(39, 'b9d8f1a5-9c3e-46b7-a4d1-2e8c5f9b3a7d', 91, 100),
(40, 'b9d8f1a5-9c3e-46b7-a4d1-2e8c5f9b3a7d', 1, 100),
(41, 'b9d8f1a5-9c3e-46b7-a4d1-2e8c5f9b3a7d', 12, 50),
(42, 'd8a1c9b4-2e9f-45d3-8b6a-1c5e9f2d4a7b', 8, 60),
(43, 'd8a1c9b4-2e9f-45d3-8b6a-1c5e9f2d4a7b', 18, 200),
(44, 'd8a1c9b4-2e9f-45d3-8b6a-1c5e9f2d4a7b', 25, 100),
(45, 'd8a1c9b4-2e9f-45d3-8b6a-1c5e9f2d4a7b', 54, 15),
(46, 'b4f1c9d2-8e3a-4a7b-9c6d-2f5e8b1a4c3f', 53, 200),
(47, 'b4f1c9d2-8e3a-4a7b-9c6d-2f5e8b1a4c3f', 105, 115),
(48, 'b4f1c9d2-8e3a-4a7b-9c6d-2f5e8b1a4c3f', 106, 150),
(49, 'c2d5f8e1-3e5b-48a6-bd4e-9c1f7a2b5d8e', 76, 500),
(50, 'c2d5f8e1-3e5b-48a6-bd4e-9c1f7a2b5d8e', 95, 250),
(51, 'c2d5f8e1-3e5b-48a6-bd4e-9c1f7a2b5d8e', 80, 100),
(52, 'e8a1d2b3-4c5d-6e7f-8a9b-0c1d2e3f4a5b', 8, 50),
(53, 'e8a1d2b3-4c5d-6e7f-8a9b-0c1d2e3f4a5b', 4, 100),
(54, 'e8a1d2b3-4c5d-6e7f-8a9b-0c1d2e3f4a5b', 73, 50),
(55, 'e8a1d2b3-4c5d-6e7f-8a9b-0c1d2e3f4a5b', 13, 100),
(56, 'f9b2e3c4-5d6e-7f8a-9b0c-1d2e3f4a5b6c', 32, 100),
(57, 'f9b2e3c4-5d6e-7f8a-9b0c-1d2e3f4a5b6c', 23, 200),
(58, 'f9b2e3c4-5d6e-7f8a-9b0c-1d2e3f4a5b6c', 80, 50),
(59, 'f9b2e3c4-5d6e-7f8a-9b0c-1d2e3f4a5b6c', 13, 100),
(60, 'a0c3f4d5-6e7f-8a9b-0c1d-2e3f4a5b6c7d', 4, 200),
(61, 'a0c3f4d5-6e7f-8a9b-0c1d-2e3f4a5b6c7d', 11, 100),
(62, 'a0c3f4d5-6e7f-8a9b-0c1d-2e3f4a5b6c7d', 41, 50),
(63, 'b1d4a5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e', 78, 400),
(64, 'b1d4a5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e', 80, 100),
(65, 'b1d4a5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e', 95, 50),
(66, 'c2e5b6f7-8a9b-0c1d-2e3f-4a5b6c7d8e9f', 26, 200),
(67, 'c2e5b6f7-8a9b-0c1d-2e3f-4a5b6c7d8e9f', 8, 150),
(68, 'c2e5b6f7-8a9b-0c1d-2e3f-4a5b6c7d8e9f', 53, 50),
(69, 'd3f6c7a8-9b0c-1d2e-3f4a-5b6c7d8e9f0a', 21, 150),
(70, 'd3f6c7a8-9b0c-1d2e-3f4a-5b6c7d8e9f0a', 76, 100),
(71, 'd3f6c7a8-9b0c-1d2e-3f4a-5b6c7d8e9f0a', 80, 50),
(72, 'd3f6c7a8-9b0c-1d2e-3f4a-5b6c7d8e9f0a', 73, 50),
(73, '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', 67, 150),
(74, '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', 7, 200),
(75, '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', 4, 100),
(76, '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', 80, 50),
(77, '2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e', 18, 200),
(78, '2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e', 27, 100),
(79, '2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e', 28, 50),
(80, '3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f', 23, 150),
(81, '3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f', 38, 50),
(82, '3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f', 13, 50),
(83, '3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f', 32, 20),
(84, '4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a', 38, 50),
(85, '4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a', 4, 50),
(86, '4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a', 18, 50),
(87, '4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a', 54, 15),
(88, '5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b', 13, 150),
(89, '5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b', 73, 100),
(90, '5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b', 107, 10),
(91, '5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b', 40, 10),
(92, '6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c', 2, 200),
(93, '6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c', 90, 100),
(94, '6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c', 95, 50),
(95, '6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c', 108, 5),
(96, '7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d', 102, 200),
(97, '7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d', 80, 50),
(98, '7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d', 109, 50),
(99, '7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d', 110, 10),
(100, '8b9c0d1e-2f3a-4b5c-6d7e-8f9a0b1c2d3e', 2, 150),
(101, '8b9c0d1e-2f3a-4b5c-6d7e-8f9a0b1c2d3e', 92, 100),
(102, '8b9c0d1e-2f3a-4b5c-6d7e-8f9a0b1c2d3e', 80, 50),
(103, '8b9c0d1e-2f3a-4b5c-6d7e-8f9a0b1c2d3e', 111, 100),
(104, '9c0d1e2f-3a4b-5c6d-7e8f-9a0b1c2d3e4f', 36, 30),
(105, '9c0d1e2f-3a4b-5c6d-7e8f-9a0b1c2d3e4f', 50, 200),
(106, '9c0d1e2f-3a4b-5c6d-7e8f-9a0b1c2d3e4f', 29, 100),
(107, '0d1e2f3a-4b5c-6d7e-8f9a-0b1c2d3e4f5a', 4, 100),
(108, '0d1e2f3a-4b5c-6d7e-8f9a-0b1c2d3e4f5a', 72, 50),
(109, '0d1e2f3a-4b5c-6d7e-8f9a-0b1c2d3e4f5a', 43, 50),
(110, 'a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6', 77, 300),
(111, 'a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6', 23, 200),
(112, 'a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6', 112, 150),
(113, 'a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6', 73, 100),
(114, 'b2c3d4e5-f6a7-b8c9-d0e1-f2a3b4c5d6e7', 26, 150),
(115, 'b2c3d4e5-f6a7-b8c9-d0e1-f2a3b4c5d6e7', 17, 50),
(116, 'b2c3d4e5-f6a7-b8c9-d0e1-f2a3b4c5d6e7', 84, 30);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_diario`
--

CREATE TABLE `registro_diario` (
  `ID_REG` varchar(36) NOT NULL DEFAULT uuid(),
  `ID_USER` varchar(36) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `peso` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `dieta` varchar(15) NOT NULL,
  `objetivo` varchar(15) DEFAULT NULL,
  `altura_cm` int(11) DEFAULT NULL,
  `act_fisica` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- AUTO_INCREMENT de la tabla `comidas_consumidas`
--
ALTER TABLE `comidas_consumidas`
  MODIFY `ID_Comidas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ingredientes`
--
ALTER TABLE `ingredientes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de la tabla `recetas_ingredientes`
--
ALTER TABLE `recetas_ingredientes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

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
