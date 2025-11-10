<?php
/**
 * Completa o mapeamento de impressao-de-livro com todas as opções faltantes
 */

$json_produto = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);

$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Keys conhecidas por padrão (baseado no que já foi mapeado)
$keys_padrao = [
    // Formato - todas usam a mesma key
    'formato_miolo_paginas' => '8507966BFD1CED08D52954CA1BFBAFAC',
    
    // Papel capa
    'papel_capa' => [
        'Cartão Triplex 250gr ' => '9DD0C964AA872B2B8F882356423C922D',
        'SEM CAPA' => '9DD0C964AA872B2B8F882356423C922D',
        'Couche Brilho 170gr ' => '9DD0C964AA872B2B8F882356423C922D',
        'Couche Fosco 150gr ' => '9DD0C964AA872B2B8F882356423C922D',
        'Couche Brilho 250g Importado' => '9DD0C964AA872B2B8F882356423C922D',
        'Couche Fosco 170gr ' => '9DD0C964AA872B2B8F882356423C922D',
    ],
    
    // Cores capa
    'cores_capa' => [
        '4 cores Frente' => 'F54EB0969F0ACEBD67F0722A3FF633F3',
        '1 Cor FxV (Preto)' => 'F54EB0969F0ACEBD67F0722A3FF633F3',
        '1 Cor Frente (Preto)' => 'F54EB0969F0ACEBD67F0722A3FF633F3',
        '5 cores Frente x 1 cor Pantone Verso' => 'F54EB0969F0ACEBD67F0722A3FF633F3',
        '4 cores Frente x 1 cor Pantone Verso' => 'F54EB0969F0ACEBD67F0722A3FF633F3',
        '4 cores Frente x 1 cor Preto Verso' => 'F54EB0969F0ACEBD67F0722A3FF633F3',
    ],
    
    // Orelha
    'orelha_capa' => [
        'SEM ORELHA' => 'FC83B57DD0039A0D73EC0FB9F63BDB59',
        'COM Orelha de 9cm' => 'FC83B57DD0039A0D73EC0FB9F63BDB59',
        'COM Orelha de 14cm' => 'FC83B57DD0039A0D73EC0FB9F63BDB59',
        'COM Orelha de 8cm' => 'FC83B57DD0039A0D73EC0FB9F63BDB59',
        'COM Orelha de 10cm' => 'FC83B57DD0039A0D73EC0FB9F63BDB59',
    ],
    
    // Acabamento capa
    'acabamento_capa' => [
        'Laminação FOSCA FRENTE (Acima de 240g)' => '9D50176D0602173B5575AC4A62173EA2',
        'Laminação BRILHO FRENTE (Acima de 240g)' => '9D50176D0602173B5575AC4A62173EA2',
        'Verniz UV TOTAL FRENTE (Acima de 240g)' => '9D50176D0602173B5575AC4A62173EA2',
        'Laminação FOSCA Frente + UV Reserva (Acima de 240g)' => '9D50176D0602173B5575AC4A62173EA2',
    ],
    
    // Papel miolo
    'papel_miolo' => [
        'Offset 75gr' => '2913797D83A57041C2A87BED6F1FEDA9',
        'Offset 70gr' => '2913797D83A57041C2A87BED6F1FEDA9',
        'Couche Fosco 115gr Importado (Digital - Abaixo de 300pçs)' => '2913797D83A57041C2A87BED6F1FEDA9',
        'Couche brilho 115gr Importado (Digital - Abaixo de 300pçs)' => '2913797D83A57041C2A87BED6F1FEDA9',
        'Couche Fosco 115gr (Offset - Acima de 300pçs)' => '2913797D83A57041C2A87BED6F1FEDA9',
        'Offset 90gr' => '2913797D83A57041C2A87BED6F1FEDA9',
        'Offset 63gr' => '2913797D83A57041C2A87BED6F1FEDA9',
    ],
    
    // Cores miolo
    'cores_miolo' => [
        '4 cores frente e verso' => 'E90F9B0C705E3F28CE0D3B51613AE230',
        '1 cor frente e verso PRETO' => 'E90F9B0C705E3F28CE0D3B51613AE230',
    ],
    
    // Miolo sangrado
    'miolo_sangrado' => [
        'NÃO' => 'CFAB249F3402BE020FEFFD84CB991DAA',
        'SIM' => 'CFAB249F3402BE020FEFFD84CB991DAA',
    ],
    
    // Quantidade páginas miolo - TODAS usam a mesma key
    'quantidade_paginas_miolo' => 'FCDF130D17B1F0C1FB2503C6F33559D7',
    
    // Acabamento miolo
    'acabamento_miolo' => [
        'Dobrado' => 'AFF7AA292FE40E02A7B255713E731899',
    ],
    
    // Acabamento livro
    'acabamento_livro' => [
        'Colado PUR' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Capa Dura Papelão 18  (1,8mm) + Costura - acima de 1.000un' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Costurado' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Capa Dura Papelão 15 (2,2mm) + Cola PUR' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Espiral Plastico' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Capa Dura Papelão 15 (2,2mm) + Costura - acima de 1.000 um.' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Grampeado - 2 grampos' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Capa Dura Papelão 18 (1,8mm) + Cola PUR' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Capa Dura Papelão 18 (1,8mm) + Espiral' => '3E9AFD1A94DA1802222717C0AAAC0093',
        'Capa Dura Papelão 15 (2,2mm) + Espiral' => '3E9AFD1A94DA1802222717C0AAAC0093',
    ],
    
    // Guardas
    'guardas_livro' => [
        'SEM GUARDAS' => '2211AA823438ACBE3BBCE2EF334AC4EA',
        'Couche 170g + Laminação Fosca 1 lado - Com Impressão 4x4 Escala' => '2211AA823438ACBE3BBCE2EF334AC4EA',
        'offset 180g - sem impressão' => '2211AA823438ACBE3BBCE2EF334AC4EA',
        'offset 180g - Com Impressão 4x4 Escala' => '2211AA823438ACBE3BBCE2EF334AC4EA',
        'Vergê Madrepérola180g (Creme) - sem impressão' => '2211AA823438ACBE3BBCE2EF334AC4EA',
        'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala' => '2211AA823438ACBE3BBCE2EF334AC4EA',
    ],
    
    // Extras
    'extras' => [
        'Nenhum' => '07316319702E082CF6DA43BF4A1C130A',
        'Shrink Coletivo c/ 30 peças' => '07316319702E082CF6DA43BF4A1C130A',
        'Shrink Coletivo c/ 20 peças' => '07316319702E082CF6DA43BF4A1C130A',
        'Shrink Coletivo c/ 10 peças' => '07316319702E082CF6DA43BF4A1C130A',
        'Shrink Coletivo c/ 5 peças' => '07316319702E082CF6DA43BF4A1C130A',
        'Shrink Individual' => '07316319702E082CF6DA43BF4A1C130A',
        'Shrink Coletivo c/ 50 peças' => '07316319702E082CF6DA43BF4A1C130A',
    ],
    
    // Frete
    'frete' => [
        'Incluso' => '9F0D19D9628523760A8B7FF3464C9E9E',
        'Cliente Retira' => '9F0D19D9628523760A8B7FF3464C9E9E',
    ],
    
    // Verificação arquivo
    'verificacao_arquivo' => [
        'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)' => 'A1EA4ABCE9F3330525CAD39BE77D01F7',
        'Capa+ Miolo: Prova de Cor Impressa no Papel - Xerox Igen' => 'A1EA4ABCE9F3330525CAD39BE77D01F7',
        'Prova de Cor Impressa Xerox Igen + Protótipo no Papel com Acabamentos (exceto UV)' => 'A1EA4ABCE9F3330525CAD39BE77D01F7',
        'Digital On-Line - via Web-Approval ou PDF' => 'A1EA4ABCE9F3330525CAD39BE77D01F7',
        'Capa: Prova de Cor Digital | Miolo: Aprovação Virtual' => 'A1EA4ABCE9F3330525CAD39BE77D01F7',
        'Capa: Prova de Cor Digital | Miolo: Prova em Baixa (Plotter)' => 'A1EA4ABCE9F3330525CAD39BE77D01F7',
    ],
    
    // Prazo entrega
    'prazo_entrega' => [
        'Padrão: 10 dias úteis de Produção + tempo de FRETE*' => '8C654A289F9D4F2A56C753120083C2ED',
    ],
];

$adicionadas = 0;

// Processar todas as opções do JSON
foreach ($json_produto['options'] as $option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        $campo = $option['name'];
        
        foreach ($option['choices'] as $choice) {
            $valor = $choice['value'] ?? $choice['label'] ?? '';
            if (!$valor) continue;
            
            // Verificar se já está mapeada
            $ja_mapeada = false;
            foreach ($keys_livro as $key_texto => $key_id) {
                if (strcasecmp(trim($key_texto), trim($valor)) === 0) {
                    $ja_mapeada = true;
                    break;
                }
            }
            
            if (!$ja_mapeada) {
                // Tentar encontrar a key baseada no campo
                $key_encontrada = null;
                
                if (isset($keys_padrao[$campo])) {
                    if (is_string($keys_padrao[$campo])) {
                        // Key única para todo o campo (ex: quantidade_paginas_miolo)
                        $key_encontrada = $keys_padrao[$campo];
                    } elseif (is_array($keys_padrao[$campo])) {
                        // Procurar no array
                        foreach ($keys_padrao[$campo] as $texto => $key) {
                            if (strcasecmp(trim($texto), trim($valor)) === 0) {
                                $key_encontrada = $key;
                                break;
                            }
                        }
                        // Se não encontrou, usar a primeira key do array (mesma key para todas)
                        if (!$key_encontrada && !empty($keys_padrao[$campo])) {
                            $key_encontrada = reset($keys_padrao[$campo]);
                        }
                    }
                }
                
                if ($key_encontrada) {
                    $keys_livro[$valor] = $key_encontrada;
                    $adicionadas++;
                    echo "✅ Adicionada: {$valor} => {$key_encontrada}\n";
                } else {
                    echo "⚠️  Não encontrada key para: {$campo} => {$valor}\n";
                }
            }
        }
    }
}

// Salvar
$mapeamento['mapeamento_por_produto']['impressao-de-livro'] = $keys_livro;
file_put_contents('mapeamento_keys_todos_produtos.json', json_encode($mapeamento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n✅ Total de keys adicionadas: {$adicionadas}\n";
echo "📊 Total de keys agora: " . count($keys_livro) . "\n";

