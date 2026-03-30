<?php
/**
 * Migrador Profesional de SQLite a MySQL para Laravel
 * Este script lee directamente de la base de datos y escribe en MySQL.
 */

// --- CONFIGURACIÓN ---
$sqlitePath = __DIR__ . '/database/database.sqlite';

// Cargar configuración desde .env si es posible, o configurar manualmente aquí:
$mysqlHost = '127.0.0.1';
$mysqlDb   = 'laravel'; // CAMBIA ESTO POR TU BASE DE DATOS
$mysqlUser = 'root';    // CAMBIA ESTO
$mysqlPass = '';        // CAMBIA ESTO

// Intentar leer del .env para mayor comodidad
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $mysqlHost = $env['DB_HOST'] ?? $mysqlHost;
    $mysqlDb   = $env['DB_DATABASE'] ?? $mysqlDb;
    $mysqlUser = $env['DB_USERNAME'] ?? $mysqlUser;
    $mysqlPass = $env['DB_PASSWORD'] ?? $mysqlPass;
}

try {
    echo "Conectando a bases de datos...\n";
    $sqlite = new PDO("sqlite:$sqlitePath");
    $mysql = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Desactivando revisiones de claves foráneas...\n";
    $mysql->exec("SET FOREIGN_KEY_CHECKS=0");

    // Obtener todas las tablas de SQLite (excepto las de sistema)
    $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "Procesando tabla: $table... ";

        // Obtener datos de la tabla
        $rows = $sqlite->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $count = count($rows);

        if ($count === 0) {
            echo "Vacia, saltando.\n";
            continue;
        }

        // Limpiar tabla en MySQL (opcional, pero recomendado para evitar duplicados)
        $mysql->exec("DELETE FROM `$table` WHERE 1");

        // Preparar la inserción
        $columns = array_keys($rows[0]);
        $colNames = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO `$table` (`$colNames`) VALUES ($placeholders)";
        $stmt = $mysql->prepare($sql);

        $mysql->beginTransaction();
        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }
        $mysql->commit();

        echo "OK ($count registros).\n";
    }

    $mysql->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "\n¡MIGRACIÓN COMPLETADA CON ÉXITO!\n";

} catch (Exception $e) {
    if (isset($mysql) && $mysql->inTransaction()) {
        $mysql->rollBack();
    }
    echo "\nERROR: " . $e->getMessage() . "\n";
}
