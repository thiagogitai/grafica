#!/usr/bin/env php
<?php

/**
 * Verifica se o arquivo resources/data/products/{slug}-field-keys.json contém todas as chaves
 * que aparecem em um payload oficial da Eskenazi (pricingParameters -> Options).
 *
 * Uso:
 *   php scripts/verify-field-keys.php impressao-de-revista oficial-payload.json
 *
 * O script lista valores ausentes e extras para facilitar o alinhamento com o catálogo oficial.
 */

declare(strict_types=1);

if ($argc < 3) {
    echo "Uso: php scripts/verify-field-keys.php <slug> <payload-json>\n";
    echo "Exemplo: php scripts/verify-field-keys.php impressao-de-revista official-payload.json\n";
    exit(1);
}

$slug = $argv[1];
$payloadFile = $argv[2];
$root = realpath(__DIR__ . '/..') ?: getcwd();
$fieldKeysPath = "{$root}/resources/data/products/{$slug}-field-keys.json";

if (!is_file($payloadFile)) {
    fwrite(STDERR, "Arquivo de payload não encontrado: {$payloadFile}\n");
    exit(2);
}

if (!is_file($fieldKeysPath)) {
    fwrite(STDERR, "Arquivo de field-keys não existe para {$slug}: {$fieldKeysPath}\n");
    exit(3);
}

$payloadJson = json_decode(file_get_contents($payloadFile), true);
if (!is_array($payloadJson)) {
    fwrite(STDERR, "Payload inválido ou não é JSON: {$payloadFile}\n");
    exit(4);
}

$options = $payloadJson['pricingParameters']['Options'] ?? [];
$payloadKeys = array_unique(array_map(function ($option) {
    return (string) ($option['Key'] ?? '');
}, $options));

$fieldDefinition = json_decode(file_get_contents($fieldKeysPath), true);
$fields = $fieldDefinition['fields'] ?? [];
$fieldKeys = array_unique(array_map(function ($field) {
    return strtoupper(trim((string) ($field['key'] ?? '')));
}, $fields));

$payloadKeysUpper = array_map('strtoupper', $payloadKeys);

$missing = array_diff($payloadKeysUpper, $fieldKeys);
$extra = array_diff($fieldKeys, $payloadKeysUpper);

echo "Verificação para slug: {$slug}\n";
echo "Payload oficial: {$payloadFile}\n";
echo "Total de keys no payload: " . count($payloadKeysUpper) . "\n";
echo "Total de keys no field-keys: " . count($fieldKeys) . "\n";

if (empty($missing) && empty($extra)) {
    echo "✅ Todas as chaves do payload oficial estão presentes no field-keys.\n";
    exit(0);
}

if (!empty($missing)) {
    echo "\nChaves presentes no payload oficial mas faltando no field-keys:\n";
    foreach ($missing as $key) {
        echo "  - {$key}\n";
    }
    echo "\n";
}

if (!empty($extra)) {
    echo "Chaves extras no field-keys (não aparecem no payload oficial):\n";
    foreach ($extra as $key) {
        echo "  - {$key}\n";
    }
    echo "\n";
}

exit(0);
