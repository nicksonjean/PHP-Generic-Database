<?php

/**
 * Script para converter arquivos CSV para JSON e XML
 */

/**
 * Remove BOM e caracteres especiais de uma string
 */
function cleanString($str) {
    // Remove BOM UTF-8 (0xEF 0xBB 0xBF)
    $str = preg_replace('/^\xEF\xBB\xBF/', '', $str);
    // Remove outros BOMs comuns
    $str = preg_replace('/^\xFE\xFF|\xFF\xFE|\x00\x00\xFE\xFF|\xFF\xFE\x00\x00/', '', $str);
    // Remove caracteres de controle invisíveis
    $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
    return trim($str);
}

/**
 * Sanitiza um nome para ser válido como nome de elemento XML
 */
function sanitizeXmlName($name) {
    // Primeiro limpar a string
    $name = cleanString($name);
    
    // Caso especial: "id" deve permanecer como "id"
    if (strtolower($name) === 'id') {
        return 'id';
    }
    
    // Não pode começar com número (exceto se for "id")
    $name = preg_replace('/^([0-9])/', '_$1', $name);
    
    // Substitui caracteres inválidos por _
    $name = preg_replace('/[^a-zA-Z0-9_\-:.]/', '_', $name);
    
    // Se ficar vazio, usar um nome padrão
    if (empty($name)) {
        $name = 'field';
    }
    
    return $name;
}

$csvDir = __DIR__ . '/../resources/database/csv';
$jsonDir = __DIR__ . '/../resources/database/json';
$xmlDir = __DIR__ . '/../resources/database/xml';

// Criar pastas se não existirem
if (!is_dir($jsonDir)) {
    mkdir($jsonDir, 0755, true);
    echo "Pasta JSON criada: $jsonDir\n";
}

if (!is_dir($xmlDir)) {
    mkdir($xmlDir, 0755, true);
    echo "Pasta XML criada: $xmlDir\n";
}

// Obter todos os arquivos CSV
$csvFiles = glob($csvDir . '/*.csv');

if (empty($csvFiles)) {
    echo "Nenhum arquivo CSV encontrado em $csvDir\n";
    exit(1);
}

echo "Encontrados " . count($csvFiles) . " arquivo(s) CSV\n\n";

foreach ($csvFiles as $csvFile) {
    $filename = basename($csvFile);
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    
    echo "Processando: $filename\n";
    
    // Ler o arquivo CSV
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        echo "  Erro ao abrir arquivo: $csvFile\n";
        continue;
    }
    
    // Ler cabeçalho
    $headers = fgetcsv($handle, 0, ';');
    if ($headers === false) {
        echo "  Erro ao ler cabeçalho do arquivo: $csvFile\n";
        fclose($handle);
        continue;
    }
    
    // Limpar cabeçalhos removendo BOM e caracteres especiais
    $headers = array_map('cleanString', $headers);
    
    // Ler dados com nomes limpos (para JSON)
    $data = [];
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (count($row) === count($headers)) {
            $data[] = array_combine($headers, $row);
        }
    }
    fclose($handle);
    
    // Converter para JSON (com nomes originais)
    $jsonFile = $jsonDir . '/' . $baseName . '.json';
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($jsonFile, $jsonContent) !== false) {
        echo "  ✓ JSON salvo: " . basename($jsonFile) . "\n";
    } else {
        echo "  ✗ Erro ao salvar JSON: $jsonFile\n";
    }
    
    // Converter para XML (sanitizando nomes)
    $xmlFile = $xmlDir . '/' . $baseName . '.xml';
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    $root = $xml->createElement('root');
    $xml->appendChild($root);
    
    // Criar mapeamento de cabeçalhos sanitizados
    $sanitizedHeaders = [];
    $headerCounts = [];
    foreach ($headers as $header) {
        $sanitized = sanitizeXmlName($header);
        if (isset($headerCounts[$sanitized])) {
            $headerCounts[$sanitized]++;
            $sanitized = $sanitized . '_' . $headerCounts[$sanitized];
        } else {
            $headerCounts[$sanitized] = 0;
        }
        $sanitizedHeaders[] = $sanitized;
    }
    $headerMap = array_combine($headers, $sanitizedHeaders);
    
    foreach ($data as $row) {
        $item = $xml->createElement('item');
        foreach ($row as $key => $value) {
            $sanitizedKey = $headerMap[$key] ?? sanitizeXmlName($key);
            $element = $xml->createElement($sanitizedKey);
            $element->appendChild($xml->createTextNode($value ?? ''));
            $item->appendChild($element);
        }
        $root->appendChild($item);
    }
    
    if ($xml->save($xmlFile) !== false) {
        echo "  ✓ XML salvo: " . basename($xmlFile) . "\n";
    } else {
        echo "  ✗ Erro ao salvar XML: $xmlFile\n";
    }
    
    echo "\n";
}

echo "Conversão concluída!\n";
