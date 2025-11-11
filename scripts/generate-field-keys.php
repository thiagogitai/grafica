#!/usr/bin/env php
<?php

/**
 * Gera/atualiza o arquivo resources/data/products/{slug}-field-keys.json
 * com as chaves presentes em um payload oficial da Eskenazi.
 *
 * O script usa o valor de cada option para criar um nome amigável e mantém os campos
 * já existentes intactos. Valores duplicados são ignorados e o arquivo final
 * fica ordenado pelo índice (posição no payload).
 *
 * Uso:
 *   php scripts/generate-field-keys.php impressao-de-revista payload.json
 *
 * Parâmetros adicionais:
 *   --write : persiste a atualização no disco (por padrão apenas mostra o diff).
 */

declare(strict_types=1);

function slugify(string $value): string
{
    $sanitized = preg_replace('/[^a-z0-9]+/i', '_', trim($value));
    $sanitized = preg_replace('/__+/', '_', $sanitized);
    $sanitized = trim($sanitized, '_');
    $sanitized = strtolower($sanitized);
    return $sanitized !== '' ? $sanitized : 'field';
}

if ($argc < 3) {
    echo "Uso: php scripts/generate-field-keys.php <slug> <payload-json> [--write]\n";
    exit(1);
}

$slug = $argv[1];
$payloadFile = $argv[2];
$write = in_array('--write', $argv, true);
$root = realpath(__DIR__ . '/..') ?: getcwd();
$fieldKeysPath = "{$root}/resources/data/products/{$slug}-field-keys.json";
$payloadJson = json_decode(file_get_contents($payloadFile), true);

if (!$payloadJson) {
    fwrite(STDERR, "Payload inválido: {$payloadFile}\n");
    exit(2);
}

$options = $payloadJson['pricingParameters']['Options'] ?? [];
$quantityKey = array_key_first($payloadJson['pricingParameters'] ?? []) ?: 'Q1';

$fieldDefinition = [];
if (is_file($fieldKeysPath)) {
    $existing = json_decode(file_get_contents($fieldKeysPath), true);
    if (is_array($existing)) {
        $fieldDefinition = $existing;
    }
}

$fields = [];
$existingNames = [];
foreach ($fieldDefinition['fields'] ?? [] as $item) {
    $key = strtoupper(trim((string) ($item['key'] ?? '')));
    if ($key === '') {
        continue;
    }
    $fields[$key] = $item;
    if (!empty($item['name'])) {
        $existingNames[] = $item['name'];
    }
}

$updated = false;
$index = 1;
foreach ($options as $option) {
    $key = strtoupper(trim((string) ($option['Key'] ?? '')));
    $value = (string) ($option['Value'] ?? '');
    if ($key === '') {
        continue;
    }

    $entry = $fields[$key] ?? null;
    if ($entry === null) {
        $nameCandidate = slugify($value ?: "opcao_{$index}");
        $name = $nameCandidate;
        $suffix = 1;
        while (in_array($name, $existingNames, true)) {
            $name = "{$nameCandidate}_{$suffix}";
            $suffix++;
        }
        $existingNames[] = $name;

        $fields[$key] = [
            'label' => $value ?: "Opção {$index}",
            'index' => $index,
            'key' => $key,
            'name' => $name,
        ];
        $updated = true;
    } else {
        $fields[$key]['index'] = $index;
        if (empty($fields[$key]['name'])) {
            $fields[$key]['name'] = slugify($value);
        }
        $updated = $updated || false;
    }

    $index++;
}

if (!isset($fieldDefinition['quantity_key'])) {
    $fieldDefinition['quantity_key'] = $quantityKey;
}

$fieldDefinition['fields'] = array_values($fields);
$fieldDefinition['fields'] = array_values(array_filter($fieldDefinition['fields'], static fn ($item) => !empty($item['key'])));
usort($fieldDefinition['fields'], static fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

$out = json_encode($fieldDefinition, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($out === false) {
    fwrite(STDERR, "Erro ao gerar JSON final\n");
    exit(5);
}

if ($write) {
    file_put_contents($fieldKeysPath, $out . "\n");
    echo "Arquivo atualizado: {$fieldKeysPath}\n";
} else {
    echo "Preview de atualização (modo dry run):\n";
    echo $out . "\n";
}

if (!$write && !$updated) {
    echo "Nenhuma alteração detectada no payload.\n";
}
