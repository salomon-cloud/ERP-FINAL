-- ============================================================================
-- SISEN ERP - Esquema de referencia (MariaDB)
-- ============================================================================
-- GENERADO AUTOMATICAMENTE. No lo edites a mano: se sobrescribe.
--
--     php artisan sisen:esquema
--
-- Este archivo NUNCA se importa. El esquema de la aplicacion se crea y
-- evoluciona exclusivamente con migraciones:
--
--     php artisan migrate
--
-- Existe como CONTRATO DE BASE DE DATOS entre los seis equipos de modulo: es
-- el reflejo exacto de lo que producen las migraciones de app/Modules/*/Migrations,
-- volcado desde una base recien migrada, sin datos.
--
-- Motor    : MariaDB / MySQL 8+ (InnoDB, utf8mb4)
-- Generado : 2026-08-08
-- Fuente   : app/Modules/{Compartido,Finanzas,Inventario,RH,Ventas,Compras,CRM}/Migrations
--            database/migrations (tablas de SISEN v1 y del framework)
--
-- Convenciones aplicadas en todo el esquema:
--   * id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
--   * created_at / updated_at / deleted_at  (nombres de Laravel, como en v1)
--   * creado_por / actualizado_por          BIGINT NULL -> users(id)
--   * version_fila  INT NOT NULL DEFAULT 1  (bloqueo optimista, solo documentos)
--   * dinero        DECIMAL(18,2)           nunca punto flotante
--   * tasas y cantidades  DECIMAL(18,6)
--   * enumeraciones mediante restricciones CHECK
--   * llaves naturales unicas mediante una columna generada <col>_activo,
--     que vale NULL en las filas con borrado logico: asi un codigo puede
--     reutilizarse despues de eliminar a su dueno
--   * toda llave foranea indexada y con ON DELETE explicito
-- ============================================================================

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
DROP TABLE IF EXISTS `actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actividades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo_actividad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` bigint unsigned NOT NULL,
  `resumen` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultado` text COLLATE utf8mb4_unicode_ci,
  `programada_en` timestamp NULL DEFAULT NULL,
  `completada_en` timestamp NULL DEFAULT NULL,
  `asignada_a` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `actividades_creado_por_foreign` (`creado_por`),
  KEY `actividades_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_actividades_entidad` (`entidad_tipo`,`entidad_id`,`created_at`),
  KEY `idx_actividades_asignada` (`asignada_a`,`programada_en`),
  CONSTRAINT `actividades_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actividades_asignada_a_foreign` FOREIGN KEY (`asignada_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actividades_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_actividades_tipo` CHECK ((`tipo_actividad` in (_utf8mb4'llamada',_utf8mb4'correo',_utf8mb4'reunion',_utf8mb4'nota',_utf8mb4'tarea')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adjuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adjuntos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modelo_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo_id` bigint unsigned NOT NULL,
  `disco` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `ruta` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamano_bytes` bigint unsigned DEFAULT NULL,
  `subido_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adjuntos_subido_por_foreign` (`subido_por`),
  KEY `idx_adjuntos_modelo` (`modelo_tipo`,`modelo_id`),
  CONSTRAINT `adjuntos_subido_por_foreign` FOREIGN KEY (`subido_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ajuste_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ajuste_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ajuste_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `almacen_id` bigint unsigned NOT NULL,
  `ubicacion_id` bigint unsigned DEFAULT NULL,
  `diferencia` decimal(18,6) NOT NULL,
  `costo_unitario` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ajuste_lineas_ajuste_id_foreign` (`ajuste_id`),
  KEY `ajuste_lineas_producto_id_foreign` (`producto_id`),
  KEY `ajuste_lineas_almacen_id_foreign` (`almacen_id`),
  KEY `ajuste_lineas_ubicacion_id_foreign` (`ubicacion_id`),
  CONSTRAINT `ajuste_lineas_ajuste_id_foreign` FOREIGN KEY (`ajuste_id`) REFERENCES `ajustes_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ajuste_lineas_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ajuste_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ajuste_lineas_ubicacion_id_foreign` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_ajuste_linea_diferencia` CHECK ((`diferencia` <> 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ajustes_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ajustes_inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_ajuste` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `aprobado_por` bigint unsigned DEFAULT NULL,
  `aprobado_en` timestamp NULL DEFAULT NULL,
  `aplicado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ajustes_numero` (`numero_ajuste`),
  KEY `ajustes_inventario_organizacion_id_foreign` (`organizacion_id`),
  KEY `ajustes_inventario_aprobado_por_foreign` (`aprobado_por`),
  KEY `ajustes_inventario_creado_por_foreign` (`creado_por`),
  KEY `ajustes_inventario_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_ajustes_estado` (`estado`),
  CONSTRAINT `ajustes_inventario_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ajustes_inventario_aprobado_por_foreign` FOREIGN KEY (`aprobado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ajustes_inventario_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ajustes_inventario_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_ajustes_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'aplicado',_utf8mb4'cancelado')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `almacenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `almacenes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_almacenes_codigo` (`codigo_activo`),
  KEY `almacenes_organizacion_id_foreign` (`organizacion_id`),
  KEY `almacenes_creado_por_foreign` (`creado_por`),
  KEY `almacenes_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `almacenes_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `almacenes_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `almacenes_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asistencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `horas_trabajadas` decimal(8,2) NOT NULL DEFAULT '0.00',
  `estado` enum('presente','falta','retardo','permiso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'presente',
  `notas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verificado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencias_empleado_fecha` (`empleado_id`,`fecha`),
  KEY `asistencias_verificado_por_foreign` (`verificado_por`),
  KEY `asistencias_creado_por_foreign` (`creado_por`),
  KEY `asistencias_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_asistencias_fecha` (`fecha`),
  KEY `idx_asistencias_estado` (`estado`),
  CONSTRAINT `asistencias_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asistencias_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asistencias_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencias_verificado_por_foreign` FOREIGN KEY (`verificado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_asistencias_horario` CHECK (((`hora_salida` is null) or (`hora_entrada` is null) or (`hora_salida` >= `hora_entrada`))),
  CONSTRAINT `chk_asistencias_horas` CHECK ((`horas_trabajadas` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bitacora_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` bigint unsigned NOT NULL,
  `valores_anteriores` json DEFAULT NULL,
  `valores_nuevos` json DEFAULT NULL,
  `direccion_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agente_usuario` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bitacora_auditoria_user_id_foreign` (`user_id`),
  KEY `idx_bitacora_entidad` (`entidad_tipo`,`entidad_id`,`created_at`),
  KEY `idx_bitacora_modulo` (`modulo`,`created_at`),
  CONSTRAINT `bitacora_auditoria_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalogo_cuentas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalogo_cuentas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `padre_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_cuenta` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `naturaleza` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_encabezado` tinyint(1) NOT NULL DEFAULT '0',
  `permite_movimientos` tinyint(1) NOT NULL DEFAULT '1',
  `es_efectivo` tinyint(1) NOT NULL DEFAULT '0',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuentas_codigo` (`codigo_activo`),
  KEY `catalogo_cuentas_organizacion_id_foreign` (`organizacion_id`),
  KEY `catalogo_cuentas_padre_id_foreign` (`padre_id`),
  KEY `catalogo_cuentas_creado_por_foreign` (`creado_por`),
  KEY `catalogo_cuentas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_cuentas_tipo` (`tipo_cuenta`),
  CONSTRAINT `catalogo_cuentas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `catalogo_cuentas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `catalogo_cuentas_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `catalogo_cuentas_padre_id_foreign` FOREIGN KEY (`padre_id`) REFERENCES `catalogo_cuentas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cuentas_encabezado` CHECK (((`es_encabezado` = false) or (`permite_movimientos` = false))),
  CONSTRAINT `chk_cuentas_naturaleza` CHECK ((`naturaleza` in (_utf8mb4'deudora',_utf8mb4'acreedora'))),
  CONSTRAINT `chk_cuentas_tipo` CHECK ((`tipo_cuenta` in (_utf8mb4'activo',_utf8mb4'pasivo',_utf8mb4'capital',_utf8mb4'ingreso',_utf8mb4'egreso',_utf8mb4'activo_contra',_utf8mb4'pasivo_contra',_utf8mb4'capital_contra',_utf8mb4'ingreso_contra',_utf8mb4'egreso_contra')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalogos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalogos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grupo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catalogos_grupo_codigo` (`grupo`,`codigo`),
  KEY `catalogos_creado_por_foreign` (`creado_por`),
  KEY `catalogos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_catalogos_grupo_activo` (`grupo`,`activo`,`orden`),
  CONSTRAINT `catalogos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `catalogos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_producto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `padre_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_producto_codigo` (`codigo_activo`),
  KEY `categorias_producto_padre_id_foreign` (`padre_id`),
  KEY `categorias_producto_creado_por_foreign` (`creado_por`),
  KEY `categorias_producto_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `categorias_producto_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categorias_producto_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categorias_producto_padre_id_foreign` FOREIGN KEY (`padre_id`) REFERENCES `categorias_producto` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centros_costo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centros_costo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `padre_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_centros_costo_codigo` (`codigo_activo`),
  KEY `centros_costo_padre_id_foreign` (`padre_id`),
  KEY `centros_costo_creado_por_foreign` (`creado_por`),
  KEY `centros_costo_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `centros_costo_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `centros_costo_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `centros_costo_padre_id_foreign` FOREIGN KEY (`padre_id`) REFERENCES `centros_costo` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `limite_credito` decimal(18,2) NOT NULL DEFAULT '0.00',
  `condicion_pago_id` bigint unsigned DEFAULT NULL,
  `lista_precio_id` bigint unsigned DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clientes_codigo` (`codigo_activo`),
  KEY `clientes_organizacion_id_foreign` (`organizacion_id`),
  KEY `clientes_condicion_pago_id_foreign` (`condicion_pago_id`),
  KEY `clientes_lista_precio_id_foreign` (`lista_precio_id`),
  KEY `clientes_creado_por_foreign` (`creado_por`),
  KEY `clientes_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_clientes_estado` (`estado`),
  KEY `idx_clientes_rfc` (`rfc`),
  CONSTRAINT `clientes_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clientes_condicion_pago_id_foreign` FOREIGN KEY (`condicion_pago_id`) REFERENCES `catalogos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clientes_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clientes_lista_precio_id_foreign` FOREIGN KEY (`lista_precio_id`) REFERENCES `listas_precios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clientes_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_clientes_estado` CHECK ((`estado` in (_utf8mb4'activo',_utf8mb4'inactivo'))),
  CONSTRAINT `chk_clientes_limite_credito` CHECK ((`limite_credito` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cobros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cobros` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_cobro` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` bigint unsigned NOT NULL,
  `factura_id` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(18,2) NOT NULL,
  `forma_pago` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuenta_bancaria_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cobros_numero` (`numero_cobro`),
  KEY `cobros_organizacion_id_foreign` (`organizacion_id`),
  KEY `cobros_cuenta_bancaria_id_foreign` (`cuenta_bancaria_id`),
  KEY `cobros_creado_por_foreign` (`creado_por`),
  KEY `cobros_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_cobros_cliente` (`cliente_id`,`fecha`),
  KEY `idx_cobros_factura` (`factura_id`),
  CONSTRAINT `cobros_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cobros_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cobros_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cobros_cuenta_bancaria_id_foreign` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cobros_factura_id_foreign` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cobros_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cobros_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'aplicado',_utf8mb4'cancelado'))),
  CONSTRAINT `chk_cobros_forma_pago` CHECK ((`forma_pago` in (_utf8mb4'efectivo',_utf8mb4'transferencia',_utf8mb4'cheque',_utf8mb4'tarjeta',_utf8mb4'liga_pago'))),
  CONSTRAINT `chk_cobros_monto` CHECK ((`monto` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `codigos_barras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `codigos_barras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigos_barras_codigo` (`codigo`),
  KEY `idx_codigos_barras_producto` (`producto_id`),
  CONSTRAINT `codigos_barras_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comentarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `comentable_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `padre_id` bigint unsigned DEFAULT NULL,
  `cuerpo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comentarios_user_id_foreign` (`user_id`),
  KEY `comentarios_padre_id_foreign` (`padre_id`),
  KEY `idx_comentarios_entidad` (`comentable_tipo`,`comentable_id`,`created_at`),
  CONSTRAINT `comentarios_padre_id_foreign` FOREIGN KEY (`padre_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conciliacion_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conciliacion_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conciliacion_id` bigint unsigned NOT NULL,
  `movimiento_bancario_id` bigint unsigned NOT NULL,
  `poliza_id` bigint unsigned DEFAULT NULL,
  `conciliada_por` bigint unsigned DEFAULT NULL,
  `conciliada_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conciliacion_linea` (`conciliacion_id`,`movimiento_bancario_id`),
  KEY `conciliacion_lineas_movimiento_bancario_id_foreign` (`movimiento_bancario_id`),
  KEY `conciliacion_lineas_poliza_id_foreign` (`poliza_id`),
  KEY `conciliacion_lineas_conciliada_por_foreign` (`conciliada_por`),
  CONSTRAINT `conciliacion_lineas_conciliacion_id_foreign` FOREIGN KEY (`conciliacion_id`) REFERENCES `conciliaciones_bancarias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conciliacion_lineas_conciliada_por_foreign` FOREIGN KEY (`conciliada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conciliacion_lineas_movimiento_bancario_id_foreign` FOREIGN KEY (`movimiento_bancario_id`) REFERENCES `movimientos_bancarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `conciliacion_lineas_poliza_id_foreign` FOREIGN KEY (`poliza_id`) REFERENCES `polizas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conciliaciones_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conciliaciones_bancarias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_bancaria_id` bigint unsigned NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  `saldo_inicial` decimal(18,2) NOT NULL DEFAULT '0.00',
  `saldo_final` decimal(18,2) NOT NULL DEFAULT '0.00',
  `conciliada_por` bigint unsigned DEFAULT NULL,
  `conciliada_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conciliaciones_bancarias_cuenta_bancaria_id_foreign` (`cuenta_bancaria_id`),
  KEY `conciliaciones_bancarias_conciliada_por_foreign` (`conciliada_por`),
  KEY `conciliaciones_bancarias_creado_por_foreign` (`creado_por`),
  KEY `conciliaciones_bancarias_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `conciliaciones_bancarias_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conciliaciones_bancarias_conciliada_por_foreign` FOREIGN KEY (`conciliada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conciliaciones_bancarias_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conciliaciones_bancarias_cuenta_bancaria_id_foreign` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_conciliaciones_estado` CHECK ((`estado` in (_utf8mb4'abierta',_utf8mb4'conciliada',_utf8mb4'cerrada'))),
  CONSTRAINT `chk_conciliaciones_fechas` CHECK ((`fecha_fin` >= `fecha_inicio`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grupo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `es_json` tinyint(1) NOT NULL DEFAULT '0',
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configuraciones_grupo_clave` (`grupo`,`clave`),
  KEY `configuraciones_creado_por_foreign` (`creado_por`),
  KEY `configuraciones_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `configuraciones_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `configuraciones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned DEFAULT NULL,
  `cliente_id` bigint unsigned DEFAULT NULL,
  `prospecto_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `puesto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contactos_prospecto_id_foreign` (`prospecto_id`),
  KEY `contactos_creado_por_foreign` (`creado_por`),
  KEY `contactos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_contactos_empresa` (`empresa_id`),
  KEY `idx_contactos_cliente` (`cliente_id`),
  CONSTRAINT `contactos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contactos_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contactos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contactos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contactos_prospecto_id_foreign` FOREIGN KEY (`prospecto_id`) REFERENCES `prospectos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conteo_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conteo_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conteo_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad_esperada` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `cantidad_contada` decimal(18,6) DEFAULT NULL,
  `diferencia` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conteo_linea` (`conteo_id`,`producto_id`),
  KEY `conteo_lineas_producto_id_foreign` (`producto_id`),
  CONSTRAINT `conteo_lineas_conteo_id_foreign` FOREIGN KEY (`conteo_id`) REFERENCES `conteos_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conteo_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conteos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conteos_inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_conteo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `almacen_id` bigint unsigned NOT NULL,
  `ubicacion_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `contado_por` bigint unsigned DEFAULT NULL,
  `contado_en` timestamp NULL DEFAULT NULL,
  `cerrado_por` bigint unsigned DEFAULT NULL,
  `cerrado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conteos_numero` (`numero_conteo`),
  KEY `conteos_inventario_organizacion_id_foreign` (`organizacion_id`),
  KEY `conteos_inventario_almacen_id_foreign` (`almacen_id`),
  KEY `conteos_inventario_ubicacion_id_foreign` (`ubicacion_id`),
  KEY `conteos_inventario_contado_por_foreign` (`contado_por`),
  KEY `conteos_inventario_cerrado_por_foreign` (`cerrado_por`),
  KEY `conteos_inventario_creado_por_foreign` (`creado_por`),
  KEY `conteos_inventario_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_conteos_estado` (`estado`),
  CONSTRAINT `conteos_inventario_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conteos_inventario_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `conteos_inventario_cerrado_por_foreign` FOREIGN KEY (`cerrado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conteos_inventario_contado_por_foreign` FOREIGN KEY (`contado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conteos_inventario_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conteos_inventario_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conteos_inventario_ubicacion_id_foreign` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_conteos_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'en_proceso',_utf8mb4'contado',_utf8mb4'ajustado',_utf8mb4'cerrado')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contratos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `numero_contrato` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_contrato` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'indefinido',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `sueldo` decimal(18,2) NOT NULL DEFAULT '0.00',
  `jornada_horas` decimal(6,2) NOT NULL DEFAULT '48.00',
  `resumen_clausulas` text COLLATE utf8mb4_unicode_ci,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `firmado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contratos_creado_por_foreign` (`creado_por`),
  KEY `contratos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_contratos_empleado` (`empleado_id`,`fecha_inicio`),
  KEY `idx_contratos_estado` (`estado`),
  CONSTRAINT `contratos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contratos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contratos_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_contratos_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'vigente',_utf8mb4'vencido',_utf8mb4'terminado'))),
  CONSTRAINT `chk_contratos_fechas` CHECK (((`fecha_fin` is null) or (`fecha_fin` >= `fecha_inicio`))),
  CONSTRAINT `chk_contratos_tipo` CHECK ((`tipo_contrato` in (_utf8mb4'indefinido',_utf8mb4'temporal',_utf8mb4'practicas',_utf8mb4'servicios',_utf8mb4'medio_tiempo'))),
  CONSTRAINT `chk_contratos_valores` CHECK (((`sueldo` >= 0) and (`jornada_horas` > 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cotizacion_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cotizacion_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cotizacion_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `precio_unitario` decimal(18,2) NOT NULL,
  `porcentaje_descuento` decimal(5,2) NOT NULL DEFAULT '0.00',
  `monto_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cotizacion_lineas_producto_id_foreign` (`producto_id`),
  KEY `cotizacion_lineas_impuesto_id_foreign` (`impuesto_id`),
  KEY `idx_cotizacion_lineas_cotizacion` (`cotizacion_id`),
  CONSTRAINT `cotizacion_lineas_cotizacion_id_foreign` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cotizacion_lineas_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cotizacion_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cotizacion_linea_descuento` CHECK (((`monto_descuento` = 0) or (`porcentaje_descuento` = 0))),
  CONSTRAINT `chk_cotizacion_linea_valores` CHECK (((`cantidad` > 0) and (`precio_unitario` >= 0) and (`porcentaje_descuento` between 0 and 100) and (`monto_descuento` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cotizaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_cotizacion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` bigint unsigned DEFAULT NULL,
  `lista_precio_id` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `vigencia` date DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cotizaciones_numero` (`numero_cotizacion`),
  KEY `cotizaciones_organizacion_id_foreign` (`organizacion_id`),
  KEY `cotizaciones_lista_precio_id_foreign` (`lista_precio_id`),
  KEY `cotizaciones_creado_por_foreign` (`creado_por`),
  KEY `cotizaciones_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_cotizaciones_cliente` (`cliente_id`,`fecha`),
  KEY `idx_cotizaciones_estado` (`estado`),
  CONSTRAINT `cotizaciones_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cotizaciones_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cotizaciones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cotizaciones_lista_precio_id_foreign` FOREIGN KEY (`lista_precio_id`) REFERENCES `listas_precios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cotizaciones_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cotizaciones_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'enviada',_utf8mb4'aceptada',_utf8mb4'rechazada',_utf8mb4'convertida',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_cotizaciones_totales` CHECK (((`subtotal` >= 0) and (`total_descuento` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0))),
  CONSTRAINT `chk_cotizaciones_vigencia` CHECK (((`vigencia` is null) or (`vigencia` >= `fecha`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_bancarias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `banco` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_cuenta` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_cuenta` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cheques',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `saldo_inicial` decimal(18,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cuentas_bancarias_organizacion_id_foreign` (`organizacion_id`),
  KEY `cuentas_bancarias_creado_por_foreign` (`creado_por`),
  KEY `cuentas_bancarias_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `cuentas_bancarias_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cuentas_bancarias_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cuentas_bancarias_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cuentas_bancarias_tipo` CHECK ((`tipo_cuenta` in (_utf8mb4'cheques',_utf8mb4'ahorro',_utf8mb4'tarjeta_credito')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `padre_id` bigint unsigned DEFAULT NULL,
  `jefe_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `responsable` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departamentos_codigo` (`codigo_activo`),
  KEY `departamentos_organizacion_id_foreign` (`organizacion_id`),
  KEY `departamentos_padre_id_foreign` (`padre_id`),
  KEY `departamentos_creado_por_foreign` (`creado_por`),
  KEY `departamentos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_departamentos_jefe` (`jefe_id`),
  CONSTRAINT `departamentos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departamentos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departamentos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departamentos_padre_id_foreign` FOREIGN KEY (`padre_id`) REFERENCES `departamentos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_departamentos_jefe` FOREIGN KEY (`jefe_id`) REFERENCES `empleados` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devolucion_compra_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devolucion_compra_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `devolucion_id` bigint unsigned NOT NULL,
  `factura_proveedor_linea_id` bigint unsigned DEFAULT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `costo_unitario` decimal(18,2) NOT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `devolucion_compra_lineas_factura_proveedor_linea_id_foreign` (`factura_proveedor_linea_id`),
  KEY `devolucion_compra_lineas_producto_id_foreign` (`producto_id`),
  KEY `idx_devolucion_compra_lineas_devolucion` (`devolucion_id`),
  CONSTRAINT `devolucion_compra_lineas_devolucion_id_foreign` FOREIGN KEY (`devolucion_id`) REFERENCES `devoluciones_compra` (`id`) ON DELETE CASCADE,
  CONSTRAINT `devolucion_compra_lineas_factura_proveedor_linea_id_foreign` FOREIGN KEY (`factura_proveedor_linea_id`) REFERENCES `factura_proveedor_lineas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `devolucion_compra_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_devolucion_compra_linea_valores` CHECK (((`cantidad` > 0) and (`costo_unitario` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devoluciones_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devoluciones_compra` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_devolucion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factura_proveedor_id` bigint unsigned NOT NULL,
  `proveedor_id` bigint unsigned NOT NULL,
  `motivo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `fecha` date NOT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_devoluciones_compra_numero` (`numero_devolucion`),
  KEY `devoluciones_compra_organizacion_id_foreign` (`organizacion_id`),
  KEY `devoluciones_compra_proveedor_id_foreign` (`proveedor_id`),
  KEY `devoluciones_compra_creado_por_foreign` (`creado_por`),
  KEY `devoluciones_compra_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_devoluciones_compra_factura` (`factura_proveedor_id`),
  CONSTRAINT `devoluciones_compra_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `devoluciones_compra_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `devoluciones_compra_factura_proveedor_id_foreign` FOREIGN KEY (`factura_proveedor_id`) REFERENCES `facturas_proveedor` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `devoluciones_compra_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `devoluciones_compra_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_devoluciones_compra_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'aplicada',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_devoluciones_compra_motivo` CHECK ((`motivo` in (_utf8mb4'defectuoso',_utf8mb4'equivocado',_utf8mb4'excedente',_utf8mb4'otro'))),
  CONSTRAINT `chk_devoluciones_compra_totales` CHECK (((`subtotal` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documentos_empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos_empleado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `adjunto_id` bigint unsigned DEFAULT NULL,
  `tipo_documento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vigencia` date DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vigente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documentos_empleado_adjunto_id_foreign` (`adjunto_id`),
  KEY `documentos_empleado_creado_por_foreign` (`creado_por`),
  KEY `documentos_empleado_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_documentos_empleado` (`empleado_id`),
  KEY `idx_documentos_vigencia` (`vigencia`),
  CONSTRAINT `documentos_empleado_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documentos_empleado_adjunto_id_foreign` FOREIGN KEY (`adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documentos_empleado_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documentos_empleado_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_documentos_estado` CHECK ((`estado` in (_utf8mb4'vigente',_utf8mb4'vencido',_utf8mb4'pendiente'))),
  CONSTRAINT `chk_documentos_tipo` CHECK ((`tipo_documento` in (_utf8mb4'contrato',_utf8mb4'identificacion',_utf8mb4'fiscal',_utf8mb4'salud',_utf8mb4'academico',_utf8mb4'otro')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_empleado` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento_id` bigint unsigned NOT NULL,
  `puesto_id` bigint unsigned NOT NULL,
  `jefe_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curp` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nss` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banco` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuenta_bancaria` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `fecha_nacimiento` date NOT NULL,
  `fecha_contratacion` date NOT NULL,
  `fecha_baja` date DEFAULT NULL,
  `motivo_baja` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_contrato` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'indefinido',
  `sueldo_base` decimal(12,2) NOT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `frecuencia_pago` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quincenal',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fotografia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `numero_empleado_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `numero_empleado` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleados_curp_unique` (`curp`),
  UNIQUE KEY `empleados_rfc_unique` (`rfc`),
  UNIQUE KEY `empleados_correo_unique` (`correo`),
  UNIQUE KEY `uq_empleados_numero` (`numero_empleado_activo`),
  KEY `empleados_departamento_id_foreign` (`departamento_id`),
  KEY `empleados_puesto_id_foreign` (`puesto_id`),
  KEY `empleados_user_id_foreign` (`user_id`),
  KEY `empleados_organizacion_id_foreign` (`organizacion_id`),
  KEY `empleados_jefe_id_foreign` (`jefe_id`),
  KEY `empleados_creado_por_foreign` (`creado_por`),
  KEY `empleados_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_empleados_estado` (`estado`),
  CONSTRAINT `empleados_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empleados_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empleados_departamento_id_foreign` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `empleados_jefe_id_foreign` FOREIGN KEY (`jefe_id`) REFERENCES `empleados` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empleados_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empleados_puesto_id_foreign` FOREIGN KEY (`puesto_id`) REFERENCES `puestos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `empleados_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_empleados_fecha_baja` CHECK (((`fecha_baja` is null) or (`fecha_baja` >= `fecha_contratacion`))),
  CONSTRAINT `chk_empleados_frecuencia_pago` CHECK ((`frecuencia_pago` in (_utf8mb4'semanal',_utf8mb4'quincenal',_utf8mb4'mensual'))),
  CONSTRAINT `chk_empleados_genero` CHECK (((`genero` is null) or (`genero` in (_utf8mb4'masculino',_utf8mb4'femenino',_utf8mb4'otro')))),
  CONSTRAINT `chk_empleados_tipo_contrato` CHECK ((`tipo_contrato` in (_utf8mb4'indefinido',_utf8mb4'temporal',_utf8mb4'practicas',_utf8mb4'servicios',_utf8mb4'medio_tiempo')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfc` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `giro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sitio_web` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `empresas_organizacion_id_foreign` (`organizacion_id`),
  KEY `empresas_creado_por_foreign` (`creado_por`),
  KEY `empresas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_empresas_estado` (`estado`),
  KEY `idx_empresas_rfc` (`rfc`),
  CONSTRAINT `empresas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empresas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empresas_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_empresas_estado` CHECK ((`estado` in (_utf8mb4'activo',_utf8mb4'inactivo')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etiquetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etiquetables` (
  `etiqueta_id` bigint unsigned NOT NULL,
  `etiquetable_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `etiquetable_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`etiqueta_id`,`etiquetable_tipo`,`etiquetable_id`),
  KEY `idx_etiquetables_entidad` (`etiquetable_tipo`,`etiquetable_id`),
  CONSTRAINT `etiquetables_etiqueta_id_foreign` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etiquetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etiquetas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_etiquetas_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluaciones_desempeno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluaciones_desempeno` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `evaluador_id` bigint unsigned DEFAULT NULL,
  `periodo_evaluado` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL,
  `fortalezas` text COLLATE utf8mb4_unicode_ci,
  `areas_mejora` text COLLATE utf8mb4_unicode_ci,
  `objetivos` json DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `evaluado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_evaluacion_periodo` (`empleado_id`,`periodo_evaluado`),
  KEY `evaluaciones_desempeno_evaluador_id_foreign` (`evaluador_id`),
  KEY `evaluaciones_desempeno_creado_por_foreign` (`creado_por`),
  KEY `evaluaciones_desempeno_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_evaluaciones_estado` (`estado`),
  CONSTRAINT `evaluaciones_desempeno_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evaluaciones_desempeno_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evaluaciones_desempeno_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluaciones_desempeno_evaluador_id_foreign` FOREIGN KEY (`evaluador_id`) REFERENCES `empleados` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_evaluaciones_calificacion` CHECK (((`calificacion` is null) or ((`calificacion` >= 0) and (`calificacion` <= 100)))),
  CONSTRAINT `chk_evaluaciones_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'enviada',_utf8mb4'reconocida')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factura_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factura_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `factura_id` bigint unsigned NOT NULL,
  `pedido_linea_id` bigint unsigned DEFAULT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `precio_unitario` decimal(18,2) NOT NULL,
  `porcentaje_descuento` decimal(5,2) NOT NULL DEFAULT '0.00',
  `monto_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `factura_lineas_pedido_linea_id_foreign` (`pedido_linea_id`),
  KEY `factura_lineas_producto_id_foreign` (`producto_id`),
  KEY `factura_lineas_impuesto_id_foreign` (`impuesto_id`),
  KEY `idx_factura_lineas_factura` (`factura_id`),
  CONSTRAINT `factura_lineas_factura_id_foreign` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factura_lineas_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `factura_lineas_pedido_linea_id_foreign` FOREIGN KEY (`pedido_linea_id`) REFERENCES `pedido_lineas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `factura_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_factura_linea_descuento` CHECK (((`monto_descuento` = 0) or (`porcentaje_descuento` = 0))),
  CONSTRAINT `chk_factura_linea_valores` CHECK (((`cantidad` > 0) and (`precio_unitario` >= 0) and (`porcentaje_descuento` between 0 and 100) and (`monto_descuento` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factura_proveedor_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factura_proveedor_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `factura_proveedor_id` bigint unsigned NOT NULL,
  `orden_compra_linea_id` bigint unsigned DEFAULT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `costo_unitario` decimal(18,2) NOT NULL,
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `factura_proveedor_lineas_orden_compra_linea_id_foreign` (`orden_compra_linea_id`),
  KEY `factura_proveedor_lineas_producto_id_foreign` (`producto_id`),
  KEY `factura_proveedor_lineas_impuesto_id_foreign` (`impuesto_id`),
  KEY `idx_factura_proveedor_lineas_factura` (`factura_proveedor_id`),
  CONSTRAINT `factura_proveedor_lineas_factura_proveedor_id_foreign` FOREIGN KEY (`factura_proveedor_id`) REFERENCES `facturas_proveedor` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factura_proveedor_lineas_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `factura_proveedor_lineas_orden_compra_linea_id_foreign` FOREIGN KEY (`orden_compra_linea_id`) REFERENCES `orden_compra_lineas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `factura_proveedor_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_factura_proveedor_linea_valores` CHECK (((`cantidad` > 0) and (`costo_unitario` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_factura` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pedido_id` bigint unsigned DEFAULT NULL,
  `cliente_id` bigint unsigned NOT NULL,
  `periodo_fiscal_id` bigint unsigned DEFAULT NULL,
  `factura_electronica_id` bigint unsigned DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `condicion_pago_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_cobrado` decimal(18,2) NOT NULL DEFAULT '0.00',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_facturas_numero` (`numero_factura`),
  KEY `facturas_organizacion_id_foreign` (`organizacion_id`),
  KEY `facturas_pedido_id_foreign` (`pedido_id`),
  KEY `facturas_factura_electronica_id_foreign` (`factura_electronica_id`),
  KEY `facturas_condicion_pago_id_foreign` (`condicion_pago_id`),
  KEY `facturas_creado_por_foreign` (`creado_por`),
  KEY `facturas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_facturas_cliente` (`cliente_id`,`fecha_emision`),
  KEY `idx_facturas_estado` (`estado`),
  KEY `idx_facturas_periodo` (`periodo_fiscal_id`),
  CONSTRAINT `facturas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `facturas_condicion_pago_id_foreign` FOREIGN KEY (`condicion_pago_id`) REFERENCES `catalogos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_factura_electronica_id_foreign` FOREIGN KEY (`factura_electronica_id`) REFERENCES `facturas_electronicas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_periodo_fiscal_id_foreign` FOREIGN KEY (`periodo_fiscal_id`) REFERENCES `periodos_fiscales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_facturas_cobrado` CHECK ((`total_cobrado` <= `total`)),
  CONSTRAINT `chk_facturas_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'emitida',_utf8mb4'cobrada_parcial',_utf8mb4'cobrada',_utf8mb4'vencida',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_facturas_totales` CHECK (((`subtotal` >= 0) and (`total_descuento` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0) and (`total_cobrado` >= 0))),
  CONSTRAINT `chk_facturas_vencimiento` CHECK (((`fecha_vencimiento` is null) or (`fecha_vencimiento` >= `fecha_emision`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_electronicas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas_electronicas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo_tipo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo_id` bigint unsigned DEFAULT NULL,
  `uuid` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta_xml` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta_pdf` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'generada',
  `respuesta_timbrado` json DEFAULT NULL,
  `cancelada_en` timestamp NULL DEFAULT NULL,
  `uuid_cancelacion` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_facturas_electronicas_folio` (`serie`,`folio`),
  KEY `facturas_electronicas_organizacion_id_foreign` (`organizacion_id`),
  KEY `facturas_electronicas_creado_por_foreign` (`creado_por`),
  KEY `facturas_electronicas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_facturas_electronicas_entidad` (`modelo_tipo`,`modelo_id`),
  KEY `idx_facturas_electronicas_estado` (`estado`),
  CONSTRAINT `facturas_electronicas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_electronicas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_electronicas_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cfdi_estado` CHECK ((`estado` in (_utf8mb4'generada',_utf8mb4'timbrada',_utf8mb4'cancelada',_utf8mb4'rechazada'))),
  CONSTRAINT `chk_cfdi_tipo` CHECK ((`tipo_documento` in (_utf8mb4'ingreso',_utf8mb4'egreso',_utf8mb4'traslado',_utf8mb4'nomina',_utf8mb4'pago')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas_proveedor` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_factura` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proveedor_id` bigint unsigned NOT NULL,
  `orden_compra_id` bigint unsigned DEFAULT NULL,
  `recepcion_id` bigint unsigned DEFAULT NULL,
  `periodo_fiscal_id` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_pagado` decimal(18,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_facturas_proveedor_numero` (`numero_factura`),
  KEY `facturas_proveedor_organizacion_id_foreign` (`organizacion_id`),
  KEY `facturas_proveedor_orden_compra_id_foreign` (`orden_compra_id`),
  KEY `facturas_proveedor_recepcion_id_foreign` (`recepcion_id`),
  KEY `facturas_proveedor_periodo_fiscal_id_foreign` (`periodo_fiscal_id`),
  KEY `facturas_proveedor_creado_por_foreign` (`creado_por`),
  KEY `facturas_proveedor_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_facturas_proveedor_proveedor` (`proveedor_id`,`fecha`),
  KEY `idx_facturas_proveedor_estado` (`estado`),
  CONSTRAINT `facturas_proveedor_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_proveedor_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_proveedor_orden_compra_id_foreign` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_proveedor_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_proveedor_periodo_fiscal_id_foreign` FOREIGN KEY (`periodo_fiscal_id`) REFERENCES `periodos_fiscales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `facturas_proveedor_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `facturas_proveedor_recepcion_id_foreign` FOREIGN KEY (`recepcion_id`) REFERENCES `recepciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_facturas_proveedor_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'contabilizada',_utf8mb4'pagada_parcial',_utf8mb4'pagada',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_facturas_proveedor_pagado` CHECK ((`total_pagado` <= `total`)),
  CONSTRAINT `chk_facturas_proveedor_totales` CHECK (((`subtotal` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0) and (`total_pagado` >= 0))),
  CONSTRAINT `chk_facturas_proveedor_vencimiento` CHECK (((`fecha_vencimiento` is null) or (`fecha_vencimiento` >= `fecha`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `favoritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favoritos` (
  `user_id` bigint unsigned NOT NULL,
  `favorito_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `favorito_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`favorito_tipo`,`favorito_id`),
  KEY `idx_favoritos_entidad` (`favorito_tipo`,`favorito_id`),
  CONSTRAINT `favoritos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `impuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `impuestos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tasa` decimal(18,6) NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_impuestos_codigo` (`codigo_activo`),
  KEY `impuestos_creado_por_foreign` (`creado_por`),
  KEY `impuestos_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `impuestos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `impuestos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_impuestos_tasa` CHECK ((`tasa` >= 0)),
  CONSTRAINT `chk_impuestos_tipo` CHECK ((`tipo` in (_utf8mb4'trasladado',_utf8mb4'retenido',_utf8mb4'otro')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lista_precio_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lista_precio_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lista_precio_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad_minima` decimal(18,6) NOT NULL DEFAULT '1.000000',
  `precio` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lista_precio_item` (`lista_precio_id`,`producto_id`,`cantidad_minima`),
  KEY `idx_lista_precio_items_producto` (`producto_id`),
  CONSTRAINT `lista_precio_items_lista_precio_id_foreign` FOREIGN KEY (`lista_precio_id`) REFERENCES `listas_precios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lista_precio_items_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_lista_precio_item_valores` CHECK (((`cantidad_minima` > 0) and (`precio` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listas_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listas_precios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `es_predeterminada` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_listas_precios_codigo` (`codigo_activo`),
  KEY `listas_precios_organizacion_id_foreign` (`organizacion_id`),
  KEY `listas_precios_creado_por_foreign` (`creado_por`),
  KEY `listas_precios_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `listas_precios_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `listas_precios_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `listas_precios_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `numero_lote` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lotes_producto_numero` (`producto_id`,`numero_lote`),
  KEY `lotes_creado_por_foreign` (`creado_por`),
  KEY `lotes_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_lotes_caducidad` (`fecha_caducidad`),
  CONSTRAINT `lotes_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lotes_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lotes_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lotes_importacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotes_importacion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plantilla` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cargado',
  `filas_totales` int NOT NULL DEFAULT '0',
  `filas_insertadas` int NOT NULL DEFAULT '0',
  `filas_actualizadas` int NOT NULL DEFAULT '0',
  `filas_omitidas` int NOT NULL DEFAULT '0',
  `resumen_errores` json DEFAULT NULL,
  `importado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lotes_importacion_importado_por_foreign` (`importado_por`),
  CONSTRAINT `lotes_importacion_importado_por_foreign` FOREIGN KEY (`importado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_lotes_importacion_estado` CHECK ((`estado` in (_utf8mb4'cargado',_utf8mb4'validando',_utf8mb4'importado',_utf8mb4'fallido',_utf8mb4'cancelado')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_bancarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_bancarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_bancaria_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `concepto` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(18,2) NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sin_conciliar',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_bancarios_creado_por_foreign` (`creado_por`),
  KEY `movimientos_bancarios_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_mov_bancarios_cuenta_fecha` (`cuenta_bancaria_id`,`fecha`),
  KEY `idx_mov_bancarios_estado` (`estado`),
  CONSTRAINT `movimientos_bancarios_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_bancarios_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_bancarios_cuenta_bancaria_id_foreign` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_mov_bancarios_estado` CHECK ((`estado` in (_utf8mb4'sin_conciliar',_utf8mb4'conciliado',_utf8mb4'pendiente',_utf8mb4'cancelado'))),
  CONSTRAINT `chk_mov_bancarios_monto` CHECK ((`monto` <> 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `almacen_id` bigint unsigned NOT NULL,
  `ubicacion_id` bigint unsigned DEFAULT NULL,
  `tipo_movimiento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `costo_unitario` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `origen_tipo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen_id` bigint unsigned DEFAULT NULL,
  `lote_id` bigint unsigned DEFAULT NULL,
  `numero_serie_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aplicado',
  `aplicado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aplicado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_inventario_organizacion_id_foreign` (`organizacion_id`),
  KEY `movimientos_inventario_ubicacion_id_foreign` (`ubicacion_id`),
  KEY `movimientos_inventario_lote_id_foreign` (`lote_id`),
  KEY `movimientos_inventario_numero_serie_id_foreign` (`numero_serie_id`),
  KEY `movimientos_inventario_aplicado_por_foreign` (`aplicado_por`),
  KEY `idx_mov_inv_producto` (`producto_id`,`aplicado_en`),
  KEY `idx_mov_inv_almacen` (`almacen_id`,`ubicacion_id`),
  KEY `idx_mov_inv_tipo` (`tipo_movimiento`),
  KEY `idx_mov_inv_origen` (`origen_tipo`,`origen_id`),
  CONSTRAINT `movimientos_inventario_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `movimientos_inventario_aplicado_por_foreign` FOREIGN KEY (`aplicado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_lote_id_foreign` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_numero_serie_id_foreign` FOREIGN KEY (`numero_serie_id`) REFERENCES `numeros_serie` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `movimientos_inventario_ubicacion_id_foreign` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_mov_inv_cantidad` CHECK ((`cantidad` <> 0)),
  CONSTRAINT `chk_mov_inv_costo` CHECK ((`costo_unitario` >= 0)),
  CONSTRAINT `chk_mov_inv_estado` CHECK ((`estado` in (_utf8mb4'aplicado',_utf8mb4'cancelado'))),
  CONSTRAINT `chk_mov_inv_tipo` CHECK ((`tipo_movimiento` in (_utf8mb4'compra',_utf8mb4'venta',_utf8mb4'traspaso_entrada',_utf8mb4'traspaso_salida',_utf8mb4'ajuste_entrada',_utf8mb4'ajuste_salida',_utf8mb4'devolucion_entrada',_utf8mb4'devolucion_salida',_utf8mb4'conteo',_utf8mb4'inicial',_utf8mb4'apartado',_utf8mb4'liberacion_apartado')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_corridas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_corridas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_corrida` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo_id` bigint unsigned NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `total_empleados` int NOT NULL DEFAULT '0',
  `total_percepciones` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_deducciones` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_neto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `poliza_id` bigint unsigned DEFAULT NULL,
  `generada_en` timestamp NULL DEFAULT NULL,
  `procesada_por` bigint unsigned DEFAULT NULL,
  `aprobada_por` bigint unsigned DEFAULT NULL,
  `aplicada_en` timestamp NULL DEFAULT NULL,
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nomina_corridas_numero` (`numero_corrida`),
  KEY `nomina_corridas_organizacion_id_foreign` (`organizacion_id`),
  KEY `nomina_corridas_periodo_id_foreign` (`periodo_id`),
  KEY `nomina_corridas_poliza_id_foreign` (`poliza_id`),
  KEY `nomina_corridas_procesada_por_foreign` (`procesada_por`),
  KEY `nomina_corridas_aprobada_por_foreign` (`aprobada_por`),
  KEY `nomina_corridas_creado_por_foreign` (`creado_por`),
  KEY `nomina_corridas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_nomina_corridas_estado` (`estado`),
  CONSTRAINT `nomina_corridas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_corridas_aprobada_por_foreign` FOREIGN KEY (`aprobada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_corridas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_corridas_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_corridas_periodo_id_foreign` FOREIGN KEY (`periodo_id`) REFERENCES `nomina_periodos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `nomina_corridas_poliza_id_foreign` FOREIGN KEY (`poliza_id`) REFERENCES `polizas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_corridas_procesada_por_foreign` FOREIGN KEY (`procesada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_corrida_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'procesada',_utf8mb4'aplicada',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_nomina_corrida_totales` CHECK (((`total_empleados` >= 0) and (`total_percepciones` >= 0) and (`total_deducciones` >= 0) and (`total_neto` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_periodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_periodos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `codigo_periodo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `fecha_pago` date NOT NULL,
  `frecuencia` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quincenal',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierto',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nomina_periodos_codigo` (`codigo_periodo`),
  KEY `nomina_periodos_organizacion_id_foreign` (`organizacion_id`),
  KEY `nomina_periodos_creado_por_foreign` (`creado_por`),
  KEY `nomina_periodos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_nomina_periodos_estado` (`estado`),
  CONSTRAINT `nomina_periodos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_periodos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nomina_periodos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_periodo_estado` CHECK ((`estado` in (_utf8mb4'abierto',_utf8mb4'procesado',_utf8mb4'cerrado'))),
  CONSTRAINT `chk_nomina_periodo_fechas` CHECK ((`fecha_fin` >= `fecha_inicio`)),
  CONSTRAINT `chk_nomina_periodo_frecuencia` CHECK ((`frecuencia` in (_utf8mb4'semanal',_utf8mb4'quincenal',_utf8mb4'mensual')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nominas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nominas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `corrida_id` bigint unsigned DEFAULT NULL,
  `periodo_pago` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_pago` date NOT NULL,
  `sueldo_base` decimal(12,2) NOT NULL,
  `bonos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `horas_extra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `horas_extra_cantidad` decimal(8,2) NOT NULL DEFAULT '0.00',
  `deducciones` decimal(12,2) NOT NULL DEFAULT '0.00',
  `dias_ausencia` decimal(6,2) NOT NULL DEFAULT '0.00',
  `isr` decimal(12,2) NOT NULL DEFAULT '0.00',
  `imss` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_pagar` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','pagada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `pagada_en` timestamp NULL DEFAULT NULL,
  `notas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nominas_empleado_id_foreign` (`empleado_id`),
  KEY `nominas_corrida_id_foreign` (`corrida_id`),
  KEY `nominas_creado_por_foreign` (`creado_por`),
  KEY `nominas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_nominas_estado` (`estado`),
  CONSTRAINT `nominas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nominas_corrida_id_foreign` FOREIGN KEY (`corrida_id`) REFERENCES `nomina_corridas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nominas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nominas_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_nominas_montos` CHECK (((`sueldo_base` >= 0) and (`bonos` >= 0) and (`horas_extra` >= 0) and (`horas_extra_cantidad` >= 0) and (`deducciones` >= 0) and (`isr` >= 0) and (`imss` >= 0) and (`dias_ausencia` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nota_credito_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nota_credito_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nota_credito_id` bigint unsigned NOT NULL,
  `factura_linea_id` bigint unsigned DEFAULT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `precio_unitario` decimal(18,2) NOT NULL,
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `nota_credito_lineas_factura_linea_id_foreign` (`factura_linea_id`),
  KEY `nota_credito_lineas_producto_id_foreign` (`producto_id`),
  KEY `nota_credito_lineas_impuesto_id_foreign` (`impuesto_id`),
  KEY `idx_nota_credito_lineas_nota` (`nota_credito_id`),
  CONSTRAINT `nota_credito_lineas_factura_linea_id_foreign` FOREIGN KEY (`factura_linea_id`) REFERENCES `factura_lineas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_credito_lineas_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_credito_lineas_nota_credito_id_foreign` FOREIGN KEY (`nota_credito_id`) REFERENCES `notas_credito` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nota_credito_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_nota_credito_linea_valores` CHECK (((`cantidad` > 0) and (`precio_unitario` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notas_credito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas_credito` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_nota` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factura_id` bigint unsigned NOT NULL,
  `cliente_id` bigint unsigned NOT NULL,
  `motivo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `fecha_emision` date NOT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notas_credito_numero` (`numero_nota`),
  KEY `notas_credito_organizacion_id_foreign` (`organizacion_id`),
  KEY `notas_credito_cliente_id_foreign` (`cliente_id`),
  KEY `notas_credito_creado_por_foreign` (`creado_por`),
  KEY `notas_credito_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_notas_credito_factura` (`factura_id`),
  CONSTRAINT `notas_credito_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notas_credito_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `notas_credito_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notas_credito_factura_id_foreign` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `notas_credito_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_notas_credito_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'emitida',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_notas_credito_motivo` CHECK ((`motivo` in (_utf8mb4'devolucion',_utf8mb4'descuento',_utf8mb4'error',_utf8mb4'otro'))),
  CONSTRAINT `chk_notas_credito_totales` CHECK (((`subtotal` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notas_crm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas_crm` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entidad_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` bigint unsigned NOT NULL,
  `autor_id` bigint unsigned NOT NULL,
  `cuerpo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fijada` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notas_crm_autor_id_foreign` (`autor_id`),
  KEY `idx_notas_crm_entidad` (`entidad_tipo`,`entidad_id`,`created_at`),
  CONSTRAINT `notas_crm_autor_id_foreign` FOREIGN KEY (`autor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cuerpo` text COLLATE utf8mb4_unicode_ci,
  `datos` json DEFAULT NULL,
  `leida_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notificaciones_sin_leer` (`user_id`,`leida_en`),
  KEY `idx_notificaciones_fecha` (`created_at`),
  CONSTRAINT `notificaciones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `numeros_serie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `numeros_serie` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `numero_serie` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_series_producto_numero` (`producto_id`,`numero_serie`),
  KEY `numeros_serie_creado_por_foreign` (`creado_por`),
  KEY `numeros_serie_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `numeros_serie_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `numeros_serie_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `numeros_serie_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_series_estado` CHECK ((`estado` in (_utf8mb4'en_stock',_utf8mb4'vendido',_utf8mb4'garantia',_utf8mb4'devuelto',_utf8mb4'baja')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oportunidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `prospecto_id` bigint unsigned DEFAULT NULL,
  `cliente_id` bigint unsigned DEFAULT NULL,
  `contacto_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `etapa` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prospeccion',
  `monto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `probabilidad` decimal(5,2) NOT NULL DEFAULT '0.00',
  `fecha_cierre_estimada` date DEFAULT NULL,
  `asignado_a` bigint unsigned DEFAULT NULL,
  `pedido_id` bigint unsigned DEFAULT NULL,
  `ganada_en` timestamp NULL DEFAULT NULL,
  `perdida_en` timestamp NULL DEFAULT NULL,
  `motivo_perdida` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oportunidades_organizacion_id_foreign` (`organizacion_id`),
  KEY `oportunidades_prospecto_id_foreign` (`prospecto_id`),
  KEY `oportunidades_contacto_id_foreign` (`contacto_id`),
  KEY `oportunidades_pedido_id_foreign` (`pedido_id`),
  KEY `oportunidades_creado_por_foreign` (`creado_por`),
  KEY `oportunidades_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_oportunidades_etapa` (`etapa`),
  KEY `idx_oportunidades_asignado` (`asignado_a`),
  KEY `idx_oportunidades_cliente` (`cliente_id`),
  KEY `idx_oportunidades_cierre` (`fecha_cierre_estimada`),
  CONSTRAINT `oportunidades_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_asignado_a_foreign` FOREIGN KEY (`asignado_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_contacto_id_foreign` FOREIGN KEY (`contacto_id`) REFERENCES `contactos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oportunidades_prospecto_id_foreign` FOREIGN KEY (`prospecto_id`) REFERENCES `prospectos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_oportunidades_etapa` CHECK ((`etapa` in (_utf8mb4'prospeccion',_utf8mb4'calificacion',_utf8mb4'propuesta',_utf8mb4'negociacion',_utf8mb4'ganada',_utf8mb4'perdida'))),
  CONSTRAINT `chk_oportunidades_ganada` CHECK (((`etapa` <> _utf8mb4'ganada') or (`ganada_en` is not null))),
  CONSTRAINT `chk_oportunidades_perdida` CHECK (((`etapa` <> _utf8mb4'perdida') or (`perdida_en` is not null))),
  CONSTRAINT `chk_oportunidades_valores` CHECK (((`monto` >= 0) and (`probabilidad` between 0 and 100)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orden_compra_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orden_compra_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `orden_compra_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `almacen_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `cantidad_recibida` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `costo_unitario` decimal(18,2) NOT NULL,
  `porcentaje_descuento` decimal(5,2) NOT NULL DEFAULT '0.00',
  `monto_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `orden_compra_lineas_almacen_id_foreign` (`almacen_id`),
  KEY `orden_compra_lineas_impuesto_id_foreign` (`impuesto_id`),
  KEY `idx_orden_compra_lineas_orden` (`orden_compra_id`),
  KEY `idx_orden_compra_lineas_producto` (`producto_id`),
  CONSTRAINT `orden_compra_lineas_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orden_compra_lineas_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orden_compra_lineas_orden_compra_id_foreign` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orden_compra_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_orden_compra_linea_descuento` CHECK (((`monto_descuento` = 0) or (`porcentaje_descuento` = 0))),
  CONSTRAINT `chk_orden_compra_linea_recibida` CHECK ((`cantidad_recibida` <= `cantidad`)),
  CONSTRAINT `chk_orden_compra_linea_valores` CHECK (((`cantidad` > 0) and (`cantidad_recibida` >= 0) and (`costo_unitario` >= 0) and (`porcentaje_descuento` between 0 and 100) and (`monto_descuento` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ordenes_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_compra` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_orden` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proveedor_id` bigint unsigned NOT NULL,
  `requisicion_id` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ordenes_compra_numero` (`numero_orden`),
  KEY `ordenes_compra_organizacion_id_foreign` (`organizacion_id`),
  KEY `ordenes_compra_requisicion_id_foreign` (`requisicion_id`),
  KEY `ordenes_compra_creado_por_foreign` (`creado_por`),
  KEY `ordenes_compra_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_ordenes_compra_proveedor` (`proveedor_id`,`fecha`),
  KEY `idx_ordenes_compra_estado` (`estado`),
  CONSTRAINT `ordenes_compra_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ordenes_compra_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ordenes_compra_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ordenes_compra_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordenes_compra_requisicion_id_foreign` FOREIGN KEY (`requisicion_id`) REFERENCES `requisiciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_ordenes_compra_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'enviada',_utf8mb4'confirmada',_utf8mb4'recibida',_utf8mb4'recibida_parcial',_utf8mb4'facturada',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_ordenes_compra_fechas` CHECK (((`fecha_entrega` is null) or (`fecha_entrega` >= `fecha`))),
  CONSTRAINT `chk_ordenes_compra_totales` CHECK (((`subtotal` >= 0) and (`total_descuento` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfc` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `mes_inicio_ejercicio` smallint NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(20) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_organizaciones_codigo` (`codigo_activo`),
  KEY `organizaciones_creado_por_foreign` (`creado_por`),
  KEY `organizaciones_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `organizaciones_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `organizaciones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_organizaciones_mes_inicio` CHECK ((`mes_inicio_ejercicio` between 1 and 12))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_pago` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proveedor_id` bigint unsigned NOT NULL,
  `factura_proveedor_id` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(18,2) NOT NULL,
  `forma_pago` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'transferencia',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuenta_bancaria_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pagos_numero` (`numero_pago`),
  KEY `pagos_organizacion_id_foreign` (`organizacion_id`),
  KEY `pagos_cuenta_bancaria_id_foreign` (`cuenta_bancaria_id`),
  KEY `pagos_creado_por_foreign` (`creado_por`),
  KEY `pagos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_pagos_proveedor` (`proveedor_id`,`fecha`),
  KEY `idx_pagos_factura` (`factura_proveedor_id`),
  CONSTRAINT `pagos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pagos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pagos_cuenta_bancaria_id_foreign` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pagos_factura_proveedor_id_foreign` FOREIGN KEY (`factura_proveedor_id`) REFERENCES `facturas_proveedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pagos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pagos_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_pagos_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'aplicado',_utf8mb4'cancelado'))),
  CONSTRAINT `chk_pagos_forma` CHECK ((`forma_pago` in (_utf8mb4'efectivo',_utf8mb4'transferencia',_utf8mb4'cheque'))),
  CONSTRAINT `chk_pagos_monto` CHECK ((`monto` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pedido_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `almacen_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `cantidad_surtida` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `precio_unitario` decimal(18,2) NOT NULL,
  `porcentaje_descuento` decimal(5,2) NOT NULL DEFAULT '0.00',
  `monto_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `tasa_impuesto` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `monto_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pedido_lineas_almacen_id_foreign` (`almacen_id`),
  KEY `pedido_lineas_impuesto_id_foreign` (`impuesto_id`),
  KEY `idx_pedido_lineas_pedido` (`pedido_id`),
  KEY `idx_pedido_lineas_producto` (`producto_id`),
  CONSTRAINT `pedido_lineas_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedido_lineas_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedido_lineas_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_pedido_linea_descuento` CHECK (((`monto_descuento` = 0) or (`porcentaje_descuento` = 0))),
  CONSTRAINT `chk_pedido_linea_surtido` CHECK ((`cantidad_surtida` <= `cantidad`)),
  CONSTRAINT `chk_pedido_linea_valores` CHECK (((`cantidad` > 0) and (`cantidad_surtida` >= 0) and (`precio_unitario` >= 0) and (`porcentaje_descuento` between 0 and 100) and (`monto_descuento` >= 0) and (`monto_impuesto` >= 0) and (`subtotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_pedido` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cotizacion_id` bigint unsigned DEFAULT NULL,
  `cliente_id` bigint unsigned NOT NULL,
  `lista_precio_id` bigint unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_descuento` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_impuesto` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedidos_numero` (`numero_pedido`),
  KEY `pedidos_organizacion_id_foreign` (`organizacion_id`),
  KEY `pedidos_cotizacion_id_foreign` (`cotizacion_id`),
  KEY `pedidos_lista_precio_id_foreign` (`lista_precio_id`),
  KEY `pedidos_creado_por_foreign` (`creado_por`),
  KEY `pedidos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_pedidos_cliente` (`cliente_id`,`fecha`),
  KEY `idx_pedidos_estado` (`estado`),
  CONSTRAINT `pedidos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pedidos_cotizacion_id_foreign` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_lista_precio_id_foreign` FOREIGN KEY (`lista_precio_id`) REFERENCES `listas_precios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_pedidos_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'confirmado',_utf8mb4'surtido',_utf8mb4'facturado_parcial',_utf8mb4'facturado',_utf8mb4'cancelado'))),
  CONSTRAINT `chk_pedidos_fechas` CHECK (((`fecha_entrega` is null) or (`fecha_entrega` >= `fecha`))),
  CONSTRAINT `chk_pedidos_totales` CHECK (((`subtotal` >= 0) and (`total_descuento` >= 0) and (`total_impuesto` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `periodos_fiscales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periodos_fiscales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ejercicio` smallint NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierto',
  `es_periodo_cierre` tinyint(1) NOT NULL DEFAULT '0',
  `cerrado_por` bigint unsigned DEFAULT NULL,
  `cerrado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_periodos_ejercicio_nombre` (`ejercicio`,`nombre`),
  KEY `periodos_fiscales_organizacion_id_foreign` (`organizacion_id`),
  KEY `periodos_fiscales_cerrado_por_foreign` (`cerrado_por`),
  KEY `periodos_fiscales_creado_por_foreign` (`creado_por`),
  KEY `periodos_fiscales_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_periodos_estado` (`estado`),
  CONSTRAINT `periodos_fiscales_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `periodos_fiscales_cerrado_por_foreign` FOREIGN KEY (`cerrado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `periodos_fiscales_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `periodos_fiscales_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_periodos_estado` CHECK ((`estado` in (_utf8mb4'abierto',_utf8mb4'cerrado',_utf8mb4'bloqueado'))),
  CONSTRAINT `chk_periodos_fechas` CHECK ((`fecha_fin` >= `fecha_inicio`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `tipo` enum('permiso','vacaciones','incapacidad') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `dias` decimal(6,2) NOT NULL DEFAULT '0.00',
  `con_goce` tinyint(1) NOT NULL DEFAULT '1',
  `motivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `revisado_por` bigint unsigned DEFAULT NULL,
  `revisado_en` timestamp NULL DEFAULT NULL,
  `comentario_revision` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permisos_revisado_por_foreign` (`revisado_por`),
  KEY `permisos_creado_por_foreign` (`creado_por`),
  KEY `permisos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_permisos_empleado` (`empleado_id`,`fecha_inicio`),
  KEY `idx_permisos_estado` (`estado`),
  CONSTRAINT `permisos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permisos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permisos_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permisos_revisado_por_foreign` FOREIGN KEY (`revisado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_permisos_dias` CHECK ((`dias` >= 0)),
  CONSTRAINT `chk_permisos_fechas` CHECK ((`fecha_fin` >= `fecha_inicio`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `poliza_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `poliza_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `poliza_id` bigint unsigned NOT NULL,
  `cuenta_id` bigint unsigned NOT NULL,
  `periodo_fiscal_id` bigint unsigned NOT NULL,
  `centro_costo_id` bigint unsigned DEFAULT NULL,
  `concepto` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debe` decimal(18,2) NOT NULL DEFAULT '0.00',
  `haber` decimal(18,2) NOT NULL DEFAULT '0.00',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `poliza_lineas_poliza_id_foreign` (`poliza_id`),
  KEY `poliza_lineas_periodo_fiscal_id_foreign` (`periodo_fiscal_id`),
  KEY `idx_poliza_lineas_cuenta` (`cuenta_id`,`periodo_fiscal_id`),
  KEY `idx_poliza_lineas_centro` (`centro_costo_id`),
  CONSTRAINT `poliza_lineas_centro_costo_id_foreign` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `poliza_lineas_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `catalogo_cuentas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `poliza_lineas_periodo_fiscal_id_foreign` FOREIGN KEY (`periodo_fiscal_id`) REFERENCES `periodos_fiscales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `poliza_lineas_poliza_id_foreign` FOREIGN KEY (`poliza_id`) REFERENCES `polizas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_poliza_lineas_sin_negativos` CHECK (((`debe` >= 0) and (`haber` >= 0))),
  CONSTRAINT `chk_poliza_lineas_un_lado` CHECK ((((`debe` > 0) and (`haber` = 0)) or ((`debe` = 0) and (`haber` > 0))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `polizas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `polizas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_poliza` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo_fiscal_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `concepto` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen_tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origen_id` bigint unsigned DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `total_debe` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_haber` decimal(18,2) NOT NULL DEFAULT '0.00',
  `contabilizada_por` bigint unsigned DEFAULT NULL,
  `contabilizada_en` timestamp NULL DEFAULT NULL,
  `cancelada_por` bigint unsigned DEFAULT NULL,
  `motivo_cancelacion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version_fila` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_polizas_numero` (`numero_poliza`),
  KEY `polizas_organizacion_id_foreign` (`organizacion_id`),
  KEY `polizas_contabilizada_por_foreign` (`contabilizada_por`),
  KEY `polizas_cancelada_por_foreign` (`cancelada_por`),
  KEY `polizas_creado_por_foreign` (`creado_por`),
  KEY `polizas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_polizas_periodo` (`periodo_fiscal_id`,`fecha`),
  KEY `idx_polizas_estado` (`estado`),
  KEY `idx_polizas_origen` (`origen_tipo`,`origen_id`),
  CONSTRAINT `polizas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `polizas_cancelada_por_foreign` FOREIGN KEY (`cancelada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `polizas_contabilizada_por_foreign` FOREIGN KEY (`contabilizada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `polizas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `polizas_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `polizas_periodo_fiscal_id_foreign` FOREIGN KEY (`periodo_fiscal_id`) REFERENCES `periodos_fiscales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_polizas_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'contabilizada',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_polizas_origen` CHECK ((`origen_tipo` in (_utf8mb4'manual',_utf8mb4'factura',_utf8mb4'nota_credito',_utf8mb4'cobro',_utf8mb4'factura_proveedor',_utf8mb4'devolucion_compra',_utf8mb4'nomina',_utf8mb4'cierre',_utf8mb4'conciliacion',_utf8mb4'ajuste'))),
  CONSTRAINT `chk_polizas_totales` CHECK (((`total_debe` >= 0) and (`total_haber` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuesto_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuesto_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `presupuesto_id` bigint unsigned NOT NULL,
  `cuenta_id` bigint unsigned NOT NULL,
  `mes` smallint NOT NULL,
  `monto_proyectado` decimal(18,2) NOT NULL DEFAULT '0.00',
  `monto_real` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_presupuesto_linea_cuenta` (`presupuesto_id`,`cuenta_id`,`mes`),
  KEY `idx_presupuesto_lineas_cuenta` (`cuenta_id`),
  CONSTRAINT `presupuesto_lineas_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `catalogo_cuentas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `presupuesto_lineas_presupuesto_id_foreign` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_presupuesto_linea_mes` CHECK ((`mes` between 1 and 12)),
  CONSTRAINT `chk_presupuesto_linea_montos` CHECK (((`monto_proyectado` >= 0) and (`monto_real` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuestos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `periodo_fiscal_id` bigint unsigned NOT NULL,
  `centro_costo_id` bigint unsigned DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `monto_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `aprobado_por` bigint unsigned DEFAULT NULL,
  `aprobado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `presupuestos_organizacion_id_foreign` (`organizacion_id`),
  KEY `presupuestos_periodo_fiscal_id_foreign` (`periodo_fiscal_id`),
  KEY `presupuestos_centro_costo_id_foreign` (`centro_costo_id`),
  KEY `presupuestos_aprobado_por_foreign` (`aprobado_por`),
  KEY `presupuestos_creado_por_foreign` (`creado_por`),
  KEY `presupuestos_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `presupuestos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presupuestos_aprobado_por_foreign` FOREIGN KEY (`aprobado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presupuestos_centro_costo_id_foreign` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presupuestos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presupuestos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presupuestos_periodo_fiscal_id_foreign` FOREIGN KEY (`periodo_fiscal_id`) REFERENCES `periodos_fiscales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_presupuestos_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'aprobado',_utf8mb4'cerrado'))),
  CONSTRAINT `chk_presupuestos_total` CHECK ((`monto_total` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `privilegios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `privilegios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(100) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_privilegios_codigo` (`codigo_activo`),
  KEY `privilegios_creado_por_foreign` (`creado_por`),
  KEY `privilegios_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_privilegios_modulo` (`modulo`),
  CONSTRAINT `privilegios_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `privilegios_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `categoria_id` bigint unsigned DEFAULT NULL,
  `unidad_id` bigint unsigned DEFAULT NULL,
  `impuesto_id` bigint unsigned DEFAULT NULL,
  `costo` decimal(18,2) NOT NULL DEFAULT '0.00',
  `precio_venta` decimal(18,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `stock_maximo` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `es_vendible` tinyint(1) NOT NULL DEFAULT '1',
  `es_comprable` tinyint(1) NOT NULL DEFAULT '1',
  `es_inventariable` tinyint(1) NOT NULL DEFAULT '1',
  `rastrea_serie` tinyint(1) NOT NULL DEFAULT '0',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `sku_activo` varchar(50) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `sku` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_productos_sku` (`sku_activo`),
  KEY `productos_organizacion_id_foreign` (`organizacion_id`),
  KEY `productos_unidad_id_foreign` (`unidad_id`),
  KEY `productos_impuesto_id_foreign` (`impuesto_id`),
  KEY `productos_creado_por_foreign` (`creado_por`),
  KEY `productos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_productos_categoria` (`categoria_id`),
  KEY `idx_productos_estado` (`estado`),
  CONSTRAINT `productos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_producto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_impuesto_id_foreign` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_unidad_id_foreign` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_medida` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_productos_estado` CHECK ((`estado` in (_utf8mb4'activo',_utf8mb4'inactivo'))),
  CONSTRAINT `chk_productos_precios` CHECK (((`costo` >= 0) and (`precio_venta` >= 0))),
  CONSTRAINT `chk_productos_stock` CHECK (((`stock_minimo` >= 0) and (`stock_maximo` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospectos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospectos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `origen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `empresa_nombre` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nuevo',
  `asignado_a` bigint unsigned DEFAULT NULL,
  `valor_estimado` decimal(18,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `cliente_convertido_id` bigint unsigned DEFAULT NULL,
  `convertido_en` timestamp NULL DEFAULT NULL,
  `motivo_perdida` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prospectos_organizacion_id_foreign` (`organizacion_id`),
  KEY `prospectos_cliente_convertido_id_foreign` (`cliente_convertido_id`),
  KEY `prospectos_creado_por_foreign` (`creado_por`),
  KEY `prospectos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_prospectos_estado` (`estado`),
  KEY `idx_prospectos_asignado` (`asignado_a`),
  CONSTRAINT `prospectos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospectos_asignado_a_foreign` FOREIGN KEY (`asignado_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospectos_cliente_convertido_id_foreign` FOREIGN KEY (`cliente_convertido_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospectos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospectos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_prospectos_estado` CHECK ((`estado` in (_utf8mb4'nuevo',_utf8mb4'contactado',_utf8mb4'calificado',_utf8mb4'convertido',_utf8mb4'perdido'))),
  CONSTRAINT `chk_prospectos_origen` CHECK ((`origen` in (_utf8mb4'web',_utf8mb4'referido',_utf8mb4'llamada',_utf8mb4'evento',_utf8mb4'feria',_utf8mb4'otro'))),
  CONSTRAINT `chk_prospectos_valor` CHECK ((`valor_estimado` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `condicion_pago_id` bigint unsigned DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proveedores_codigo` (`codigo_activo`),
  KEY `proveedores_organizacion_id_foreign` (`organizacion_id`),
  KEY `proveedores_condicion_pago_id_foreign` (`condicion_pago_id`),
  KEY `proveedores_creado_por_foreign` (`creado_por`),
  KEY `proveedores_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_proveedores_estado` (`estado`),
  CONSTRAINT `proveedores_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proveedores_condicion_pago_id_foreign` FOREIGN KEY (`condicion_pago_id`) REFERENCES `catalogos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proveedores_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proveedores_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_proveedores_estado` CHECK ((`estado` in (_utf8mb4'activo',_utf8mb4'inactivo')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `puestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `puestos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `departamento_id` bigint unsigned NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `sueldo_minimo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sueldo_maximo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(30) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_puestos_codigo` (`codigo_activo`),
  KEY `puestos_departamento_id_foreign` (`departamento_id`),
  KEY `puestos_creado_por_foreign` (`creado_por`),
  KEY `puestos_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `puestos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puestos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puestos_departamento_id_foreign` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_puestos_rango_sueldo` CHECK (((`sueldo_maximo` = 0) or (`sueldo_maximo` >= `sueldo_minimo`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recepcion_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recepcion_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recepcion_id` bigint unsigned NOT NULL,
  `orden_compra_linea_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `ubicacion_id` bigint unsigned DEFAULT NULL,
  `cantidad_recibida` decimal(18,6) NOT NULL,
  `costo_unitario` decimal(18,2) NOT NULL,
  `lote_id` bigint unsigned DEFAULT NULL,
  `numero_serie_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recepcion_lineas_orden_compra_linea_id_foreign` (`orden_compra_linea_id`),
  KEY `recepcion_lineas_producto_id_foreign` (`producto_id`),
  KEY `recepcion_lineas_ubicacion_id_foreign` (`ubicacion_id`),
  KEY `recepcion_lineas_lote_id_foreign` (`lote_id`),
  KEY `recepcion_lineas_numero_serie_id_foreign` (`numero_serie_id`),
  KEY `idx_recepcion_lineas_recepcion` (`recepcion_id`),
  CONSTRAINT `recepcion_lineas_lote_id_foreign` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recepcion_lineas_numero_serie_id_foreign` FOREIGN KEY (`numero_serie_id`) REFERENCES `numeros_serie` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recepcion_lineas_orden_compra_linea_id_foreign` FOREIGN KEY (`orden_compra_linea_id`) REFERENCES `orden_compra_lineas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `recepcion_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `recepcion_lineas_recepcion_id_foreign` FOREIGN KEY (`recepcion_id`) REFERENCES `recepciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recepcion_lineas_ubicacion_id_foreign` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_recepcion_linea_valores` CHECK (((`cantidad_recibida` > 0) and (`costo_unitario` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recepciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recepciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_recepcion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden_compra_id` bigint unsigned NOT NULL,
  `almacen_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `recibido_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recepciones_numero` (`numero_recepcion`),
  KEY `recepciones_organizacion_id_foreign` (`organizacion_id`),
  KEY `recepciones_almacen_id_foreign` (`almacen_id`),
  KEY `recepciones_recibido_por_foreign` (`recibido_por`),
  KEY `recepciones_creado_por_foreign` (`creado_por`),
  KEY `recepciones_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_recepciones_orden` (`orden_compra_id`),
  CONSTRAINT `recepciones_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recepciones_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `recepciones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recepciones_orden_compra_id_foreign` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `recepciones_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recepciones_recibido_por_foreign` FOREIGN KEY (`recibido_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_recepciones_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'aplicada',_utf8mb4'cancelada')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reglas_reorden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reglas_reorden` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `almacen_id` bigint unsigned NOT NULL,
  `cantidad_minima` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `cantidad_maxima` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `cantidad_reorden` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `dias_entrega` smallint NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regla_reorden` (`producto_id`,`almacen_id`),
  KEY `reglas_reorden_creado_por_foreign` (`creado_por`),
  KEY `reglas_reorden_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_reglas_reorden_almacen` (`almacen_id`),
  CONSTRAINT `reglas_reorden_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reglas_reorden_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reglas_reorden_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reglas_reorden_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_reorden_cantidades` CHECK (((`cantidad_minima` >= 0) and (`cantidad_maxima` >= 0) and (`cantidad_reorden` >= 0) and (`dias_entrega` >= 0))),
  CONSTRAINT `chk_reorden_min_max` CHECK (((`cantidad_maxima` = 0) or (`cantidad_minima` <= `cantidad_maxima`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reportes_guardados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes_guardados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_reporte` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `configuracion` json DEFAULT NULL,
  `compartido` tinyint(1) NOT NULL DEFAULT '0',
  `creado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reportes_guardados_dueno` (`creado_por`,`modulo`),
  CONSTRAINT `reportes_guardados_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requisicion_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `requisicion_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `requisicion_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `cantidad_solicitada` decimal(18,6) NOT NULL,
  `proveedor_sugerido_id` bigint unsigned DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisicion_lineas_producto_id_foreign` (`producto_id`),
  KEY `requisicion_lineas_proveedor_sugerido_id_foreign` (`proveedor_sugerido_id`),
  KEY `idx_requisicion_lineas_requisicion` (`requisicion_id`),
  CONSTRAINT `requisicion_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `requisicion_lineas_proveedor_sugerido_id_foreign` FOREIGN KEY (`proveedor_sugerido_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisicion_lineas_requisicion_id_foreign` FOREIGN KEY (`requisicion_id`) REFERENCES `requisiciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_requisicion_linea_cantidad` CHECK ((`cantidad_solicitada` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requisiciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `requisiciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_requisicion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departamento_id` bigint unsigned DEFAULT NULL,
  `solicitante_id` bigint unsigned NOT NULL,
  `fecha_requerida` date DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_requisiciones_numero` (`numero_requisicion`),
  KEY `requisiciones_organizacion_id_foreign` (`organizacion_id`),
  KEY `requisiciones_departamento_id_foreign` (`departamento_id`),
  KEY `requisiciones_solicitante_id_foreign` (`solicitante_id`),
  KEY `requisiciones_creado_por_foreign` (`creado_por`),
  KEY `requisiciones_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_requisiciones_estado` (`estado`),
  CONSTRAINT `requisiciones_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisiciones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisiciones_departamento_id_foreign` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisiciones_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisiciones_solicitante_id_foreign` FOREIGN KEY (`solicitante_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_requisiciones_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'enviada',_utf8mb4'aprobada',_utf8mb4'rechazada',_utf8mb4'convertida',_utf8mb4'cerrada')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rol_privilegios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol_privilegios` (
  `rol_id` bigint unsigned NOT NULL,
  `privilegio_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rol_id`,`privilegio_id`),
  KEY `idx_rol_privilegios_privilegio` (`privilegio_id`),
  CONSTRAINT `rol_privilegios_privilegio_id_foreign` FOREIGN KEY (`privilegio_id`) REFERENCES `privilegios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rol_privilegios_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `es_sistema` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `codigo_activo` varchar(50) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `codigo` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_codigo` (`codigo_activo`),
  KEY `roles_creado_por_foreign` (`creado_por`),
  KEY `roles_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `roles_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roles_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `secuencias_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `secuencias_documento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefijo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sufijo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `numero_actual` bigint unsigned NOT NULL DEFAULT '0',
  `relleno` smallint NOT NULL DEFAULT '6',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_secuencias_modulo_prefijo` (`modulo`,`prefijo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tareas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `entidad_tipo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` bigint unsigned DEFAULT NULL,
  `asignada_a` bigint unsigned DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `prioridad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `completada_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tareas_creado_por_foreign` (`creado_por`),
  KEY `tareas_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_tareas_asignada` (`asignada_a`,`estado`,`fecha_limite`),
  KEY `idx_tareas_entidad` (`entidad_tipo`,`entidad_id`),
  CONSTRAINT `tareas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tareas_asignada_a_foreign` FOREIGN KEY (`asignada_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tareas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_tareas_completada` CHECK (((`estado` <> _utf8mb4'completada') or (`completada_en` is not null))),
  CONSTRAINT `chk_tareas_estado` CHECK ((`estado` in (_utf8mb4'pendiente',_utf8mb4'en_proceso',_utf8mb4'completada',_utf8mb4'cancelada'))),
  CONSTRAINT `chk_tareas_prioridad` CHECK ((`prioridad` in (_utf8mb4'baja',_utf8mb4'media',_utf8mb4'alta')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_cambio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_cambio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `moneda_origen` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moneda_destino` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `tasa` decimal(18,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipos_cambio_par_fecha` (`moneda_origen`,`moneda_destino`,`fecha`),
  CONSTRAINT `chk_tipos_cambio_tasa` CHECK ((`tasa` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `traspaso_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traspaso_lineas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `traspaso_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad` decimal(18,6) NOT NULL,
  `costo_unitario` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `lote_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `traspaso_lineas_traspaso_id_foreign` (`traspaso_id`),
  KEY `traspaso_lineas_producto_id_foreign` (`producto_id`),
  KEY `traspaso_lineas_lote_id_foreign` (`lote_id`),
  CONSTRAINT `traspaso_lineas_lote_id_foreign` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `traspaso_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `traspaso_lineas_traspaso_id_foreign` FOREIGN KEY (`traspaso_id`) REFERENCES `traspasos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_traspaso_linea_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `traspasos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traspasos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `numero_traspaso` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `almacen_origen_id` bigint unsigned NOT NULL,
  `almacen_destino_id` bigint unsigned NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `solicitado_por` bigint unsigned DEFAULT NULL,
  `aprobado_por` bigint unsigned DEFAULT NULL,
  `aprobado_en` timestamp NULL DEFAULT NULL,
  `enviado_en` timestamp NULL DEFAULT NULL,
  `recibido_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_traspasos_numero` (`numero_traspaso`),
  KEY `traspasos_organizacion_id_foreign` (`organizacion_id`),
  KEY `traspasos_almacen_origen_id_foreign` (`almacen_origen_id`),
  KEY `traspasos_almacen_destino_id_foreign` (`almacen_destino_id`),
  KEY `traspasos_solicitado_por_foreign` (`solicitado_por`),
  KEY `traspasos_aprobado_por_foreign` (`aprobado_por`),
  KEY `traspasos_creado_por_foreign` (`creado_por`),
  KEY `traspasos_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_traspasos_estado` (`estado`),
  CONSTRAINT `traspasos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `traspasos_almacen_destino_id_foreign` FOREIGN KEY (`almacen_destino_id`) REFERENCES `almacenes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `traspasos_almacen_origen_id_foreign` FOREIGN KEY (`almacen_origen_id`) REFERENCES `almacenes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `traspasos_aprobado_por_foreign` FOREIGN KEY (`aprobado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `traspasos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `traspasos_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `traspasos_solicitado_por_foreign` FOREIGN KEY (`solicitado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_traspasos_almacenes` CHECK ((`almacen_origen_id` <> `almacen_destino_id`)),
  CONSTRAINT `chk_traspasos_estado` CHECK ((`estado` in (_utf8mb4'borrador',_utf8mb4'en_transito',_utf8mb4'recibido',_utf8mb4'cancelado')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ubicaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `almacen_id` bigint unsigned NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_surtible` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ubicaciones_almacen_codigo` (`almacen_id`,`codigo`),
  KEY `ubicaciones_creado_por_foreign` (`creado_por`),
  KEY `ubicaciones_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `ubicaciones_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ubicaciones_almacen_id_foreign` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ubicaciones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unidades_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades_medida` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor_base` decimal(18,6) NOT NULL DEFAULT '1.000000',
  `es_base` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unidades_codigo` (`codigo`),
  KEY `unidades_medida_creado_por_foreign` (`creado_por`),
  KEY `unidades_medida_actualizado_por_foreign` (`actualizado_por`),
  CONSTRAINT `unidades_medida_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unidades_medida_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_unidades_factor` CHECK ((`factor_base` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint unsigned DEFAULT NULL,
  `empleado_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Administrador','Recursos Humanos','Contador','Empleado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Empleado',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT '0',
  `password_cambiado_en` timestamp NULL DEFAULT NULL,
  `ultimo_acceso_en` timestamp NULL DEFAULT NULL,
  `intentos_fallidos` int NOT NULL DEFAULT '0',
  `bloqueado_en` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `email_activo` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`deleted_at` is null) then `email` end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email_activo` (`email_activo`),
  KEY `users_empleado_id_index` (`empleado_id`),
  KEY `users_organizacion_id_foreign` (`organizacion_id`),
  KEY `users_creado_por_foreign` (`creado_por`),
  KEY `users_actualizado_por_foreign` (`actualizado_por`),
  KEY `idx_users_estado` (`estado`),
  CONSTRAINT `fk_users_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_organizacion_id_foreign` FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_roles` (
  `user_id` bigint unsigned NOT NULL,
  `rol_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`rol_id`),
  KEY `idx_usuario_roles_rol` (`rol_id`),
  CONSTRAINT `usuario_roles_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v_balance_general`;
/*!50001 DROP VIEW IF EXISTS `v_balance_general`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_balance_general` AS SELECT 
 1 AS `periodo_fiscal_id`,
 1 AS `periodo`,
 1 AS `ejercicio`,
 1 AS `fecha_fin`,
 1 AS `cuenta_id`,
 1 AS `cuenta_codigo`,
 1 AS `cuenta_nombre`,
 1 AS `tipo_cuenta`,
 1 AS `seccion`,
 1 AS `movimiento_periodo`,
 1 AS `saldo_final`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_balanza_comprobacion`;
/*!50001 DROP VIEW IF EXISTS `v_balanza_comprobacion`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_balanza_comprobacion` AS SELECT 
 1 AS `periodo_fiscal_id`,
 1 AS `periodo`,
 1 AS `ejercicio`,
 1 AS `fecha_inicio`,
 1 AS `fecha_fin`,
 1 AS `cuenta_id`,
 1 AS `cuenta_codigo`,
 1 AS `cuenta_nombre`,
 1 AS `tipo_cuenta`,
 1 AS `naturaleza`,
 1 AS `total_debe`,
 1 AS `total_haber`,
 1 AS `saldo`,
 1 AS `saldo_natural`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_embudo_por_responsable`;
/*!50001 DROP VIEW IF EXISTS `v_embudo_por_responsable`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_embudo_por_responsable` AS SELECT 
 1 AS `responsable_id`,
 1 AS `responsable_nombre`,
 1 AS `etapa`,
 1 AS `oportunidades`,
 1 AS `monto_total`,
 1 AS `monto_ponderado`,
 1 AS `monto_ganado`,
 1 AS `monto_perdido`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_embudo_ventas`;
/*!50001 DROP VIEW IF EXISTS `v_embudo_ventas`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_embudo_ventas` AS SELECT 
 1 AS `etapa`,
 1 AS `oportunidades`,
 1 AS `monto_total`,
 1 AS `monto_ponderado`,
 1 AS `proximo_cierre`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_estado_resultados`;
/*!50001 DROP VIEW IF EXISTS `v_estado_resultados`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_estado_resultados` AS SELECT 
 1 AS `periodo_fiscal_id`,
 1 AS `periodo`,
 1 AS `ejercicio`,
 1 AS `centro_costo_id`,
 1 AS `centro_costo_codigo`,
 1 AS `cuenta_id`,
 1 AS `cuenta_codigo`,
 1 AS `cuenta_nombre`,
 1 AS `tipo_cuenta`,
 1 AS `seccion`,
 1 AS `monto`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_existencias`;
/*!50001 DROP VIEW IF EXISTS `v_existencias`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_existencias` AS SELECT 
 1 AS `producto_id`,
 1 AS `sku`,
 1 AS `producto_nombre`,
 1 AS `categoria_id`,
 1 AS `almacen_id`,
 1 AS `almacen_codigo`,
 1 AS `almacen_nombre`,
 1 AS `ubicacion_id`,
 1 AS `ubicacion_codigo`,
 1 AS `existencia`,
 1 AS `valor_inventario`,
 1 AS `ultimo_movimiento_en`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_flujo_efectivo`;
/*!50001 DROP VIEW IF EXISTS `v_flujo_efectivo`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_flujo_efectivo` AS SELECT 
 1 AS `periodo_fiscal_id`,
 1 AS `periodo`,
 1 AS `ejercicio`,
 1 AS `fecha`,
 1 AS `numero_poliza`,
 1 AS `origen_tipo`,
 1 AS `cuenta_id`,
 1 AS `cuenta_codigo`,
 1 AS `cuenta_nombre`,
 1 AS `tipo_flujo`,
 1 AS `entrada`,
 1 AS `salida`,
 1 AS `flujo_neto`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_historial_cliente`;
/*!50001 DROP VIEW IF EXISTS `v_historial_cliente`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_historial_cliente` AS SELECT 
 1 AS `cliente_id`,
 1 AS `cliente_codigo`,
 1 AS `cliente_nombre`,
 1 AS `estado`,
 1 AS `cotizaciones`,
 1 AS `pedidos`,
 1 AS `facturas`,
 1 AS `monto_facturado`,
 1 AS `monto_cobrado`,
 1 AS `saldo_pendiente`,
 1 AS `monto_acreditado`,
 1 AS `ultima_factura`,
 1 AS `ultimo_cobro`,
 1 AS `oportunidades_abiertas`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_libro_mayor`;
/*!50001 DROP VIEW IF EXISTS `v_libro_mayor`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_libro_mayor` AS SELECT 
 1 AS `linea_id`,
 1 AS `poliza_id`,
 1 AS `numero_poliza`,
 1 AS `fecha`,
 1 AS `concepto_poliza`,
 1 AS `origen_tipo`,
 1 AS `origen_id`,
 1 AS `periodo_fiscal_id`,
 1 AS `periodo`,
 1 AS `ejercicio`,
 1 AS `cuenta_id`,
 1 AS `cuenta_codigo`,
 1 AS `cuenta_nombre`,
 1 AS `tipo_cuenta`,
 1 AS `naturaleza`,
 1 AS `centro_costo_id`,
 1 AS `centro_costo_codigo`,
 1 AS `centro_costo_nombre`,
 1 AS `concepto_linea`,
 1 AS `debe`,
 1 AS `haber`,
 1 AS `movimiento`,
 1 AS `saldo_corrido`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_presupuesto_vs_real`;
/*!50001 DROP VIEW IF EXISTS `v_presupuesto_vs_real`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_presupuesto_vs_real` AS SELECT 
 1 AS `presupuesto_id`,
 1 AS `presupuesto_nombre`,
 1 AS `periodo_fiscal_id`,
 1 AS `centro_costo_id`,
 1 AS `cuenta_id`,
 1 AS `cuenta_codigo`,
 1 AS `cuenta_nombre`,
 1 AS `mes`,
 1 AS `monto_proyectado`,
 1 AS `monto_real`,
 1 AS `variacion`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_reporte_impuestos`;
/*!50001 DROP VIEW IF EXISTS `v_reporte_impuestos`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_reporte_impuestos` AS SELECT 
 1 AS `naturaleza`,
 1 AS `periodo_fiscal_id`,
 1 AS `fecha_documento`,
 1 AS `impuesto_id`,
 1 AS `impuesto_codigo`,
 1 AS `impuesto_nombre`,
 1 AS `impuesto_tasa`,
 1 AS `base_gravable`,
 1 AS `monto_impuesto`,
 1 AS `documento_id`,
 1 AS `documento_numero`*/;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `v_balance_general`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_balance_general` AS select `b`.`periodo_fiscal_id` AS `periodo_fiscal_id`,`b`.`periodo` AS `periodo`,`b`.`ejercicio` AS `ejercicio`,`b`.`fecha_fin` AS `fecha_fin`,`b`.`cuenta_id` AS `cuenta_id`,`b`.`cuenta_codigo` AS `cuenta_codigo`,`b`.`cuenta_nombre` AS `cuenta_nombre`,`b`.`tipo_cuenta` AS `tipo_cuenta`,(case when (`b`.`tipo_cuenta` in ('activo','activo_contra')) then 'activo' when (`b`.`tipo_cuenta` in ('pasivo','pasivo_contra')) then 'pasivo' else 'capital' end) AS `seccion`,`b`.`saldo_natural` AS `movimiento_periodo`,sum(`b`.`saldo_natural`) OVER (PARTITION BY `b`.`cuenta_id` ORDER BY `b`.`fecha_fin`,`b`.`periodo_fiscal_id` )  AS `saldo_final` from `v_balanza_comprobacion` `b` where (`b`.`tipo_cuenta` in ('activo','pasivo','capital','activo_contra','pasivo_contra','capital_contra')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_balanza_comprobacion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_balanza_comprobacion` AS select `pf`.`id` AS `periodo_fiscal_id`,`pf`.`nombre` AS `periodo`,`pf`.`ejercicio` AS `ejercicio`,`pf`.`fecha_inicio` AS `fecha_inicio`,`pf`.`fecha_fin` AS `fecha_fin`,`c`.`id` AS `cuenta_id`,`c`.`codigo` AS `cuenta_codigo`,`c`.`nombre` AS `cuenta_nombre`,`c`.`tipo_cuenta` AS `tipo_cuenta`,`c`.`naturaleza` AS `naturaleza`,sum(`pl`.`debe`) AS `total_debe`,sum(`pl`.`haber`) AS `total_haber`,sum((`pl`.`debe` - `pl`.`haber`)) AS `saldo`,(case when (`c`.`naturaleza` = 'acreedora') then sum((`pl`.`haber` - `pl`.`debe`)) else sum((`pl`.`debe` - `pl`.`haber`)) end) AS `saldo_natural` from (((`poliza_lineas` `pl` join `polizas` `p` on((`p`.`id` = `pl`.`poliza_id`))) join `catalogo_cuentas` `c` on((`c`.`id` = `pl`.`cuenta_id`))) join `periodos_fiscales` `pf` on((`pf`.`id` = `pl`.`periodo_fiscal_id`))) where ((`p`.`estado` = 'contabilizada') and (`p`.`deleted_at` is null)) group by `pf`.`id`,`pf`.`nombre`,`pf`.`ejercicio`,`pf`.`fecha_inicio`,`pf`.`fecha_fin`,`c`.`id`,`c`.`codigo`,`c`.`nombre`,`c`.`tipo_cuenta`,`c`.`naturaleza` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_embudo_por_responsable`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_embudo_por_responsable` AS select `o`.`asignado_a` AS `responsable_id`,`u`.`name` AS `responsable_nombre`,`o`.`etapa` AS `etapa`,count(0) AS `oportunidades`,coalesce(sum(`o`.`monto`),0) AS `monto_total`,coalesce(sum(((`o`.`monto` * `o`.`probabilidad`) / 100)),0) AS `monto_ponderado`,coalesce(sum((case when (`o`.`etapa` = 'ganada') then `o`.`monto` else 0 end)),0) AS `monto_ganado`,coalesce(sum((case when (`o`.`etapa` = 'perdida') then `o`.`monto` else 0 end)),0) AS `monto_perdido` from (`oportunidades` `o` left join `users` `u` on((`u`.`id` = `o`.`asignado_a`))) where (`o`.`deleted_at` is null) group by `o`.`asignado_a`,`u`.`name`,`o`.`etapa` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_embudo_ventas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_embudo_ventas` AS select `o`.`etapa` AS `etapa`,count(0) AS `oportunidades`,coalesce(sum(`o`.`monto`),0) AS `monto_total`,coalesce(sum(((`o`.`monto` * `o`.`probabilidad`) / 100)),0) AS `monto_ponderado`,min(`o`.`fecha_cierre_estimada`) AS `proximo_cierre` from `oportunidades` `o` where (`o`.`deleted_at` is null) group by `o`.`etapa` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_estado_resultados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_estado_resultados` AS select `pf`.`id` AS `periodo_fiscal_id`,`pf`.`nombre` AS `periodo`,`pf`.`ejercicio` AS `ejercicio`,`pl`.`centro_costo_id` AS `centro_costo_id`,`cc`.`codigo` AS `centro_costo_codigo`,`c`.`id` AS `cuenta_id`,`c`.`codigo` AS `cuenta_codigo`,`c`.`nombre` AS `cuenta_nombre`,`c`.`tipo_cuenta` AS `tipo_cuenta`,(case when (`c`.`tipo_cuenta` in ('ingreso','ingreso_contra')) then 'ingreso' else 'egreso' end) AS `seccion`,(case when (`c`.`tipo_cuenta` in ('ingreso','ingreso_contra')) then sum((`pl`.`haber` - `pl`.`debe`)) else sum((`pl`.`debe` - `pl`.`haber`)) end) AS `monto` from ((((`poliza_lineas` `pl` join `polizas` `p` on((`p`.`id` = `pl`.`poliza_id`))) join `catalogo_cuentas` `c` on((`c`.`id` = `pl`.`cuenta_id`))) join `periodos_fiscales` `pf` on((`pf`.`id` = `pl`.`periodo_fiscal_id`))) left join `centros_costo` `cc` on((`cc`.`id` = `pl`.`centro_costo_id`))) where ((`p`.`estado` = 'contabilizada') and (`p`.`deleted_at` is null) and (`c`.`tipo_cuenta` in ('ingreso','egreso','ingreso_contra','egreso_contra'))) group by `pf`.`id`,`pf`.`nombre`,`pf`.`ejercicio`,`pl`.`centro_costo_id`,`cc`.`codigo`,`c`.`id`,`c`.`codigo`,`c`.`nombre`,`c`.`tipo_cuenta` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_existencias`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_existencias` AS select `p`.`id` AS `producto_id`,`p`.`sku` AS `sku`,`p`.`nombre` AS `producto_nombre`,`p`.`categoria_id` AS `categoria_id`,`a`.`id` AS `almacen_id`,`a`.`codigo` AS `almacen_codigo`,`a`.`nombre` AS `almacen_nombre`,`m`.`ubicacion_id` AS `ubicacion_id`,`u`.`codigo` AS `ubicacion_codigo`,sum(`m`.`cantidad`) AS `existencia`,sum((`m`.`cantidad` * `m`.`costo_unitario`)) AS `valor_inventario`,max(`m`.`aplicado_en`) AS `ultimo_movimiento_en` from (((`movimientos_inventario` `m` join `productos` `p` on((`p`.`id` = `m`.`producto_id`))) join `almacenes` `a` on((`a`.`id` = `m`.`almacen_id`))) left join `ubicaciones` `u` on((`u`.`id` = `m`.`ubicacion_id`))) where ((`m`.`estado` = 'aplicado') and (`m`.`deleted_at` is null)) group by `p`.`id`,`p`.`sku`,`p`.`nombre`,`p`.`categoria_id`,`a`.`id`,`a`.`codigo`,`a`.`nombre`,`m`.`ubicacion_id`,`u`.`codigo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_flujo_efectivo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_flujo_efectivo` AS select `pf`.`id` AS `periodo_fiscal_id`,`pf`.`nombre` AS `periodo`,`pf`.`ejercicio` AS `ejercicio`,`p`.`fecha` AS `fecha`,`p`.`numero_poliza` AS `numero_poliza`,`p`.`origen_tipo` AS `origen_tipo`,`c`.`id` AS `cuenta_id`,`c`.`codigo` AS `cuenta_codigo`,`c`.`nombre` AS `cuenta_nombre`,(case when (`p`.`origen_tipo` in ('factura','nota_credito','cobro','factura_proveedor','devolucion_compra','nomina')) then 'operacion' when (`p`.`origen_tipo` in ('cierre','ajuste')) then 'financiamiento' else 'inversion' end) AS `tipo_flujo`,`pl`.`debe` AS `entrada`,`pl`.`haber` AS `salida`,(`pl`.`debe` - `pl`.`haber`) AS `flujo_neto` from (((`poliza_lineas` `pl` join `polizas` `p` on((`p`.`id` = `pl`.`poliza_id`))) join `catalogo_cuentas` `c` on((`c`.`id` = `pl`.`cuenta_id`))) join `periodos_fiscales` `pf` on((`pf`.`id` = `pl`.`periodo_fiscal_id`))) where ((`p`.`estado` = 'contabilizada') and (`p`.`deleted_at` is null) and (`c`.`es_efectivo` = true)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_historial_cliente`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_historial_cliente` AS select `c`.`id` AS `cliente_id`,`c`.`codigo` AS `cliente_codigo`,`c`.`nombre` AS `cliente_nombre`,`c`.`estado` AS `estado`,(select count(0) from `cotizaciones` `q` where ((`q`.`cliente_id` = `c`.`id`) and (`q`.`deleted_at` is null))) AS `cotizaciones`,(select count(0) from `pedidos` `pe` where ((`pe`.`cliente_id` = `c`.`id`) and (`pe`.`deleted_at` is null))) AS `pedidos`,(select count(0) from `facturas` `f` where ((`f`.`cliente_id` = `c`.`id`) and (`f`.`deleted_at` is null))) AS `facturas`,(select coalesce(sum(`f`.`total`),0) from `facturas` `f` where ((`f`.`cliente_id` = `c`.`id`) and (`f`.`deleted_at` is null) and (`f`.`estado` <> 'cancelada'))) AS `monto_facturado`,(select coalesce(sum(`f`.`total_cobrado`),0) from `facturas` `f` where ((`f`.`cliente_id` = `c`.`id`) and (`f`.`deleted_at` is null) and (`f`.`estado` <> 'cancelada'))) AS `monto_cobrado`,(select coalesce(sum((`f`.`total` - `f`.`total_cobrado`)),0) from `facturas` `f` where ((`f`.`cliente_id` = `c`.`id`) and (`f`.`deleted_at` is null) and (`f`.`estado` in ('emitida','cobrada_parcial','vencida')))) AS `saldo_pendiente`,(select coalesce(sum(`n`.`total`),0) from `notas_credito` `n` where ((`n`.`cliente_id` = `c`.`id`) and (`n`.`deleted_at` is null) and (`n`.`estado` = 'emitida'))) AS `monto_acreditado`,(select max(`f`.`fecha_emision`) from `facturas` `f` where ((`f`.`cliente_id` = `c`.`id`) and (`f`.`deleted_at` is null))) AS `ultima_factura`,(select max(`co`.`fecha`) from `cobros` `co` where ((`co`.`cliente_id` = `c`.`id`) and (`co`.`deleted_at` is null) and (`co`.`estado` = 'aplicado'))) AS `ultimo_cobro`,(select count(0) from `oportunidades` `o` where ((`o`.`cliente_id` = `c`.`id`) and (`o`.`deleted_at` is null) and (`o`.`etapa` not in ('ganada','perdida')))) AS `oportunidades_abiertas` from `clientes` `c` where (`c`.`deleted_at` is null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_libro_mayor`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_libro_mayor` AS select `pl`.`id` AS `linea_id`,`p`.`id` AS `poliza_id`,`p`.`numero_poliza` AS `numero_poliza`,`p`.`fecha` AS `fecha`,`p`.`concepto` AS `concepto_poliza`,`p`.`origen_tipo` AS `origen_tipo`,`p`.`origen_id` AS `origen_id`,`pl`.`periodo_fiscal_id` AS `periodo_fiscal_id`,`pf`.`nombre` AS `periodo`,`pf`.`ejercicio` AS `ejercicio`,`c`.`id` AS `cuenta_id`,`c`.`codigo` AS `cuenta_codigo`,`c`.`nombre` AS `cuenta_nombre`,`c`.`tipo_cuenta` AS `tipo_cuenta`,`c`.`naturaleza` AS `naturaleza`,`pl`.`centro_costo_id` AS `centro_costo_id`,`cc`.`codigo` AS `centro_costo_codigo`,`cc`.`nombre` AS `centro_costo_nombre`,`pl`.`concepto` AS `concepto_linea`,`pl`.`debe` AS `debe`,`pl`.`haber` AS `haber`,(`pl`.`debe` - `pl`.`haber`) AS `movimiento`,sum((`pl`.`debe` - `pl`.`haber`)) OVER (PARTITION BY `pl`.`cuenta_id` ORDER BY `p`.`fecha`,`p`.`id`,`pl`.`id` )  AS `saldo_corrido` from ((((`poliza_lineas` `pl` join `polizas` `p` on((`p`.`id` = `pl`.`poliza_id`))) join `catalogo_cuentas` `c` on((`c`.`id` = `pl`.`cuenta_id`))) join `periodos_fiscales` `pf` on((`pf`.`id` = `pl`.`periodo_fiscal_id`))) left join `centros_costo` `cc` on((`cc`.`id` = `pl`.`centro_costo_id`))) where ((`p`.`estado` = 'contabilizada') and (`p`.`deleted_at` is null)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_presupuesto_vs_real`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_presupuesto_vs_real` AS select `pr`.`id` AS `presupuesto_id`,`pr`.`nombre` AS `presupuesto_nombre`,`pr`.`periodo_fiscal_id` AS `periodo_fiscal_id`,`pr`.`centro_costo_id` AS `centro_costo_id`,`prl`.`cuenta_id` AS `cuenta_id`,`c`.`codigo` AS `cuenta_codigo`,`c`.`nombre` AS `cuenta_nombre`,`prl`.`mes` AS `mes`,`prl`.`monto_proyectado` AS `monto_proyectado`,coalesce((select sum((`pl`.`debe` - `pl`.`haber`)) from (`poliza_lineas` `pl` join `polizas` `p` on((`p`.`id` = `pl`.`poliza_id`))) where ((`pl`.`cuenta_id` = `prl`.`cuenta_id`) and (`pl`.`periodo_fiscal_id` = `pr`.`periodo_fiscal_id`) and (`p`.`estado` = 'contabilizada') and (`p`.`deleted_at` is null))),0) AS `monto_real`,(`prl`.`monto_proyectado` - coalesce((select sum((`pl`.`debe` - `pl`.`haber`)) from (`poliza_lineas` `pl` join `polizas` `p` on((`p`.`id` = `pl`.`poliza_id`))) where ((`pl`.`cuenta_id` = `prl`.`cuenta_id`) and (`pl`.`periodo_fiscal_id` = `pr`.`periodo_fiscal_id`) and (`p`.`estado` = 'contabilizada') and (`p`.`deleted_at` is null))),0)) AS `variacion` from ((`presupuesto_lineas` `prl` join `presupuestos` `pr` on((`pr`.`id` = `prl`.`presupuesto_id`))) join `catalogo_cuentas` `c` on((`c`.`id` = `prl`.`cuenta_id`))) where (`pr`.`deleted_at` is null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_reporte_impuestos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_reporte_impuestos` AS select 'trasladado' AS `naturaleza`,`f`.`periodo_fiscal_id` AS `periodo_fiscal_id`,`f`.`fecha_emision` AS `fecha_documento`,`i`.`id` AS `impuesto_id`,`i`.`codigo` AS `impuesto_codigo`,`i`.`nombre` AS `impuesto_nombre`,`i`.`tasa` AS `impuesto_tasa`,`fl`.`subtotal` AS `base_gravable`,`fl`.`monto_impuesto` AS `monto_impuesto`,`f`.`id` AS `documento_id`,`f`.`numero_factura` AS `documento_numero` from ((`factura_lineas` `fl` join `facturas` `f` on((`f`.`id` = `fl`.`factura_id`))) join `impuestos` `i` on((`i`.`id` = `fl`.`impuesto_id`))) where ((`f`.`estado` <> 'cancelada') and (`f`.`deleted_at` is null)) union all select 'acreditable' AS `naturaleza`,`fp`.`periodo_fiscal_id` AS `periodo_fiscal_id`,`fp`.`fecha` AS `fecha_documento`,`i`.`id` AS `impuesto_id`,`i`.`codigo` AS `impuesto_codigo`,`i`.`nombre` AS `impuesto_nombre`,`i`.`tasa` AS `impuesto_tasa`,`fpl`.`subtotal` AS `base_gravable`,`fpl`.`monto_impuesto` AS `monto_impuesto`,`fp`.`id` AS `documento_id`,`fp`.`numero_factura` AS `documento_numero` from ((`factura_proveedor_lineas` `fpl` join `facturas_proveedor` `fp` on((`fp`.`id` = `fpl`.`factura_proveedor_id`))) join `impuestos` `i` on((`i`.`id` = `fpl`.`impuesto_id`))) where ((`fp`.`estado` <> 'cancelada') and (`fp`.`deleted_at` is null)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

