<?php
/**
 * Script para baixar mapeamento_keys_todos_produtos.json via HTTP
 * Execute: php baixar_keys_http.php
 */

$url_vps = 'https://seusite.com.br/download-keys-mapping'; // Altere para a URL do seu VPS

echo "📥 Baixando mapeamento_keys_todos_produtos.json do VPS...\n";
echo "   URL: {$url_vps}\n\n";

// Usar file_get_contents ou curl
if (function_exists('curl_init')) {
    $ch = curl_init($url_vps);
    $fp = fopen('mapeamento_keys_todos_produtos.json', 'w');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    fclose($fp);
    
    if ($httpCode === 200 && $result) {
        echo "✅ Arquivo baixado com sucesso!\n";
        $size = filesize('mapeamento_keys_todos_produtos.json');
        echo "   Tamanho: " . number_format($size / 1024, 2) . " KB\n";
    } else {
        echo "❌ Erro ao baixar arquivo (HTTP {$httpCode})\n";
        if (file_exists('mapeamento_keys_todos_produtos.json')) {
            unlink('mapeamento_keys_todos_produtos.json');
        }
        exit(1);
    }
} else {
    $content = @file_get_contents($url_vps);
    if ($content === false) {
        echo "❌ Erro ao baixar arquivo\n";
        exit(1);
    }
    
    file_put_contents('mapeamento_keys_todos_produtos.json', $content);
    echo "✅ Arquivo baixado com sucesso!\n";
    $size = filesize('mapeamento_keys_todos_produtos.json');
    echo "   Tamanho: " . number_format($size / 1024, 2) . " KB\n";
}

echo "\n✅ Pronto! Agora você pode executar: php testar_precos_livro_local.php\n";

