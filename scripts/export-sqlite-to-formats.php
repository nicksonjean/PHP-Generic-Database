<?php

/**
 * Script de exemplo para exportar banco de dados SQLite para diferentes formatos
 * 
 * Uso (Windows):
 * php .\scripts\export-sqlite-to-formats.php
 * 
 * Uso (Linux/Mac):
 * php scripts/export-sqlite-to-formats.php
 * 
 * Uso (Docker):
 * docker-compose exec php-8.0-apache php scripts/export-sqlite-to-formats.php
 * ou
 * docker exec -it php-8.0-apache php /var/www/html/scripts/export-sqlite-to-formats.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GenericDatabase\Helpers\Exporters\Export;

// Configurações
$databasePath = __DIR__ . '/../resources/database/sqlite/data/DB.SQLITE';
$csvOutputPath = __DIR__ . '/../resources/database/csv';
$jsonOutputPath = __DIR__ . '/../resources/database/json';
$xmlOutputPath = __DIR__ . '/../resources/database/xml';
$yamlOutputPath = __DIR__ . '/../resources/database/yaml';
$iniOutputPath = __DIR__ . '/../resources/database/ini';
$neonOutputPath = __DIR__ . '/../resources/database/neon';

try {
    echo "Iniciando exportação do banco de dados SQLite...\n\n";

    // Exportar para todos os formatos usando método fluente
    // Note: fromSQLite agora requer outputPath, mas podemos usar um path base
    $baseOutputPath = __DIR__ . '/../resources/database';
    $export = Export::fromSQLite($databasePath, $baseOutputPath)
        ->toCSV($csvOutputPath)
        ->toXML($xmlOutputPath)
        ->toJSON($jsonOutputPath)
        ->toYAML($yamlOutputPath)
        ->toINI($iniOutputPath)
        ->toNEON($neonOutputPath);

    echo "Exportação concluída com sucesso!\n\n";

    // Mostrar arquivos exportados
    echo "Arquivos exportados:\n";
    foreach ($export->getExportedPaths() as $format => $files) {
        echo "\n{$format}:\n";
        foreach ($files as $file) {
            echo "  - " . basename($file) . "\n";
        }
    }

} catch (Exception $e) {
    echo "Erro durante a exportação: " . $e->getMessage() . "\n";
    exit(1);
}
