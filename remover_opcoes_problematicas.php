<?php

/**
 * Remove opções problemáticas do template
 */

$templatePath = 'resources/data/products/impressao-de-livro.json';
$template = json_decode(file_get_contents($templatePath), true);

if (!$template) {
    die("Erro ao carregar template\n");
}

echo "Removendo opcoes problematicas...\n\n";

$removidas = 0;

foreach ($template['options'] as $optIdx => &$opt) {
    if ($opt['name'] === 'quantity') {
        continue;
    }
    
    $choices = &$opt['choices'];
    $choicesOriginais = count($choices);
    
    // Filtrar choices problemáticas
    $choices = array_filter($choices, function($choice) use (&$removidas, $opt) {
        $value = $choice['value'] ?? '';
        $remover = false;
        $motivo = '';
        
        // 1. Miolo acima de 800 páginas
        if ($opt['name'] === 'quantidade_paginas_miolo' && preg_match('/Miolo (\d+) páginas/', $value, $matches)) {
            $paginas = (int) $matches[1];
            if ($paginas > 800) {
                $remover = true;
                $motivo = "Miolo acima de 800 paginas ({$paginas})";
            }
        }
        
        // 2. Capa Dura Papelão
        if (strpos($value, 'Capa Dura Papelão') !== false) {
            $remover = true;
            $motivo = "Capa Dura Papelao";
        }
        
        // 3. Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala
        if (strpos($value, 'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala') !== false) {
            $remover = true;
            $motivo = "Vergê Madrepérola com impressao 4x4";
        }
        
        // 4. Shrink Coletivo (todas)
        if (strpos($value, 'Shrink Coletivo') !== false) {
            $remover = true;
            $motivo = "Shrink Coletivo";
        }
        
        if ($remover) {
            $removidas++;
            echo "  Removendo: {$opt['name']} -> '{$value}' ({$motivo})\n";
        }
        
        return !$remover;
    });
    
    // Reindexar array
    $choices = array_values($choices);
    
    if (count($choices) < $choicesOriginais) {
        echo "  Campo '{$opt['name']}': {$choicesOriginais} -> " . count($choices) . " opcoes\n";
    }
}

echo "\nTotal de opcoes removidas: {$removidas}\n\n";

// Salvar template atualizado
file_put_contents($templatePath, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "Template atualizado salvo em: {$templatePath}\n";
echo "Backup criado em: {$templatePath}.backup." . date('Y-m-d_His') . "\n";

// Criar backup
copy($templatePath, $templatePath . '.backup.' . date('Y-m-d_His'));

echo "\nVerificando template atualizado...\n";

// Contar opções totais
$totalOpcoes = 0;
foreach ($template['options'] as $opt) {
    if ($opt['name'] !== 'quantity') {
        $totalOpcoes += count($opt['choices']);
    }
}

echo "Total de opcoes no template: {$totalOpcoes}\n";
echo "Campos: " . count($template['options']) . "\n";

