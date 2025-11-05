<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiPricingProxyController extends Controller
{
    /**
     * Proxy que converte opções normais para formato da API e retorna preço
     * Faz scraping uma vez se necessário para descobrir Keys
     */
    public function obterPreco(Request $request)
    {
        $productSlug = $request->input('product_slug');
        $opcoes = $request->except(['product_slug', 'force_validation', '_force']);
        $quantidade = (int) ($opcoes['quantity'] ?? 50);
        
        if (!$productSlug) {
            return response()->json([
                'success' => false,
                'error' => 'product_slug é obrigatório'
            ], 400);
        }
        
        // Remover quantity das opções (vai ser enviado como Q1)
        unset($opcoes['quantity']);
        
        try {
            // Tentar obter Keys do cache primeiro (24 horas)
            $cacheKey = "api_keys_{$productSlug}";
            $keysMap = Cache::get($cacheKey);
            
            // Limitar frequência de requisições (rate limiting)
            $rateLimitKey = "api_rate_limit_{$productSlug}";
            $lastRequest = Cache::get($rateLimitKey);
            
            // Mínimo de 2-4 segundos entre requisições para o mesmo produto (mais discreto)
            $minDelay = 2;
            $maxDelay = 4;
            
            if ($lastRequest) {
                $elapsed = time() - $lastRequest;
                if ($elapsed < $minDelay) {
                    $waitTime = $minDelay + rand(0, $maxDelay - $minDelay) - $elapsed;
                    if ($waitTime > 0) {
                        \Log::info("Rate limit: aguardando {$waitTime}s antes de nova requisição");
                        sleep($waitTime);
                    }
                }
            }
            
            Cache::put($rateLimitKey, time(), now()->addMinute());
            
            if (!$keysMap) {
                // Tentar carregar de arquivo de mapeamento completo (todos os produtos)
                $arquivoMapeamentoCompleto = base_path('mapeamento_keys_todos_produtos.json');
                
                if (file_exists($arquivoMapeamentoCompleto)) {
                    $mapeamentoCompleto = json_decode(file_get_contents($arquivoMapeamentoCompleto), true);
                    
                    // Tentar pegar keys específicas do produto
                    if (isset($mapeamentoCompleto['mapeamento_por_produto'][$productSlug])) {
                        $keysMap = $mapeamentoCompleto['mapeamento_por_produto'][$productSlug];
                    } elseif (isset($mapeamentoCompleto['keys_reais'])) {
                        // Fallback: usar keys unificadas
                        $keysMap = $mapeamentoCompleto['keys_reais'];
                    }
                    
                    if ($keysMap) {
                        // Cachear por 24 horas
                        Cache::put($cacheKey, $keysMap, now()->addHours(24));
                        \Log::info("Keys carregadas do arquivo completo e cacheadas: " . count($keysMap));
                    }
                }
                
                // Se ainda não tem, tentar arquivo antigo (por produto individual)
                if (!$keysMap) {
                    $arquivoMapeamento = base_path('mapeamento_keys_opcoes.json');
                    
                    if (file_exists($arquivoMapeamento)) {
                        $mapeamento = json_decode(file_get_contents($arquivoMapeamento), true);
                        $keysMap = $mapeamento['keys_reais'] ?? [];
                        
                        if ($keysMap) {
                            // Cachear por 24 horas
                            Cache::put($cacheKey, $keysMap, now()->addHours(24));
                            \Log::info("Keys carregadas do arquivo individual e cacheadas: " . count($keysMap));
                        }
                    }
                }
                
                // Se ainda não tem, fazer scraping uma vez para descobrir Keys
                if (!$keysMap) {
                    \Log::info("Cache de Keys não encontrado. Fazendo scraping para descobrir Keys...");
                    $keysMap = $this->descobrirKeysViaScraping($productSlug, $opcoes);
                    
                    if ($keysMap) {
                        // Cachear por 24 horas
                        Cache::put($cacheKey, $keysMap, now()->addHours(24));
                        \Log::info("Keys descobertas via scraping e cacheadas: " . count($keysMap));
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => 'Não foi possível descobrir as Keys das opções. Execute: python3 mapear_keys_todos_produtos.py'
                        ], 500);
                    }
                }
            }
            
            // Mapear opções para Keys NA ORDEM CORRETA DOS SELECTS
            $options = [];
            
            // Para impressao-de-livro e impressao-de-revista, usar ordem específica dos selects
            if ($productSlug === 'impressao-de-livro') {
                $ordemSelects = [
                    0 => 'formato_miolo_paginas',
                    1 => 'papel_capa',
                    2 => 'cores_capa',
                    3 => 'orelha_capa',
                    4 => 'acabamento_capa',
                    5 => 'papel_miolo',
                    6 => 'cores_miolo',
                    7 => 'miolo_sangrado',
                    8 => 'quantidade_paginas_miolo',
                    10 => 'acabamento_miolo',  // "Dobrado" na requisição real
                    11 => 'acabamento_livro',  // "Costurado" na requisição real
                    12 => 'guardas_livro',  // "offset 180g - sem impressão" na requisição real
                    13 => 'extras',  // "Shrink Individual" na requisição real
                    14 => 'frete',
                    15 => 'verificacao_arquivo',
                    16 => 'prazo_entrega',  // Última opção
                ];
                
                // Nota: Na requisição real capturada (última que funcionou):
                // - Posição 0: "118x175mm" (formato_miolo_paginas)
                // - Posição 1: "Couche Brilho 150gr " (papel_capa) - TEM ESPAÇO NO FINAL!
                // - Posição 2: "4 cores FxV" (cores_capa)
                // - Posição 3: "COM Orelha de 8cm" (orelha_capa)
                // - Posição 4: "Laminação FOSCA Frente + UV Reserva..." (acabamento_capa)
                // - Posição 5: "Pólen Natural 80g" (papel_miolo)
                // - Posição 6: "1 cor frente e verso PRETO" (cores_miolo)
                // - Posição 7: "SIM" (miolo_sangrado)
                // - Posição 8: "Miolo 12 páginas" (quantidade_paginas_miolo)
                // - Posição 9: (pulada)
                // - Posição 10: "Dobrado" (acabamento_miolo)
                // - Posição 11: "Costurado" (acabamento_livro)
                // - Posição 12: "offset 180g - sem impressão" (guardas_livro)
                // - Posição 13: "Shrink Individual" (extras)
                // - Posição 14: "Cliente Retira" (frete)
                // - Posição 15: "Digital On-Line..." (verificacao_arquivo)
                // - Posição 16: "Padrão: 10 dias..." (prazo_entrega)
                
                // Processar na ordem dos selects
                foreach ($ordemSelects as $selectIdx => $campo) {
                    if (!isset($opcoes[$campo]) || $campo === 'quantity') {
                        continue;
                    }
                    
                    $valorStr = trim((string) $opcoes[$campo]);
                    
                    // Tentar match exato
                    if (isset($keysMap[$valorStr])) {
                        $options[] = [
                            'Key' => $keysMap[$valorStr],
                            'Value' => $valorStr
                        ];
                        continue;
                    }
                    
                    // Match case-insensitive
                    $encontrado = false;
                    foreach ($keysMap as $texto => $key) {
                        if (strcasecmp(trim($texto), $valorStr) === 0) {
                            $options[] = [
                                'Key' => $key,
                                'Value' => trim($texto)
                            ];
                            $encontrado = true;
                            break;
                        }
                    }
                    
                    // Match parcial se não encontrou
                    if (!$encontrado) {
                        foreach ($keysMap as $texto => $key) {
                            if (stripos($texto, $valorStr) !== false || stripos($valorStr, $texto) !== false) {
                                $options[] = [
                                    'Key' => $key,
                                    'Value' => trim($texto)
                                ];
                                $encontrado = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$encontrado) {
                        \Log::warning("Key não encontrada para: {$campo} = {$valorStr}");
                    }
                }
            } elseif ($productSlug === 'impressao-de-revista') {
                // Ordem específica para impressao-de-revista (baseado no scrape_revista.py)
                $ordemSelects = [
                    0 => 'formato',  // 2- Formato do Miolo (Páginas):
                    1 => 'papel_capa',  // 3- Papel CAPA:
                    2 => 'cores_capa',  // 4- Cores CAPA:
                    3 => 'orelha_capa',  // 5 - Orelha da CAPA:
                    4 => 'acabamento_capa',  // 6- Acabamento CAPA:
                    5 => 'papel_miolo',  // 7- Papel MIOLO:
                    6 => 'cores_miolo',  // 8- Cores MIOLO:
                    7 => 'miolo_sangrado',  // 9- MIOLO Sangrado?
                    8 => 'quantidade_paginas_miolo',  // 10- Quantidade Paginas MIOLO:
                    9 => 'acabamento_miolo',  // 11- Acabamento MIOLO:
                    10 => 'acabamento_livro',  // 12- Acabamento LIVRO:
                    11 => 'guardas_livro',  // 13- Guardas LIVRO:
                    12 => 'extras',  // 14- Extras:
                    13 => 'frete',  // 15- Frete:
                    14 => 'verificacao_arquivo',  // 16- Verificação do Arquivo:
                    15 => 'prazo_entrega',  // 17- Prazo de Entrega:
                ];
                
                // Processar na ordem dos selects
                foreach ($ordemSelects as $selectIdx => $campo) {
                    if (!isset($opcoes[$campo]) || $campo === 'quantity') {
                        continue;
                    }
                    
                    $valorStr = trim((string) $opcoes[$campo]);
                    
                    // Tentar match exato
                    if (isset($keysMap[$valorStr])) {
                        $options[] = [
                            'Key' => $keysMap[$valorStr],
                            'Value' => $valorStr
                        ];
                        continue;
                    }
                    
                    // Match case-insensitive
                    $encontrado = false;
                    foreach ($keysMap as $texto => $key) {
                        if (strcasecmp(trim($texto), $valorStr) === 0) {
                            $options[] = [
                                'Key' => $key,
                                'Value' => trim($texto)
                            ];
                            $encontrado = true;
                            break;
                        }
                    }
                    
                    // Match parcial se não encontrou
                    if (!$encontrado) {
                        foreach ($keysMap as $texto => $key) {
                            if (stripos($texto, $valorStr) !== false || stripos($valorStr, $texto) !== false) {
                                $options[] = [
                                    'Key' => $key,
                                    'Value' => trim($texto)
                                ];
                                $encontrado = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$encontrado) {
                        \Log::warning("Key não encontrada para: {$campo} = {$valorStr}");
                    }
                }
            } else {
                // Para outros produtos, manter ordem original
                foreach ($opcoes as $campo => $valor) {
                    if ($campo === 'quantity') {
                        continue;
                    }
                    
                    $valorStr = trim((string) $valor);
                    
                    // Tentar match exato
                    if (isset($keysMap[$valorStr])) {
                        $options[] = [
                            'Key' => $keysMap[$valorStr],
                            'Value' => $valorStr
                        ];
                        continue;
                    }
                    
                    // Match parcial
                    $encontrado = false;
                    foreach ($keysMap as $texto => $key) {
                        if (stripos($texto, $valorStr) !== false || stripos($valorStr, $texto) !== false) {
                            $options[] = [
                                'Key' => $key,
                                'Value' => $texto
                            ];
                            $encontrado = true;
                            break;
                        }
                    }
                    
                    if (!$encontrado) {
                        \Log::warning("Key não encontrada para: {$campo} = {$valorStr}");
                    }
                }
            }
            
            // Para impressao-de-livro e impressao-de-revista, verificar quantidade mínima de opções
            $minOptions = count($opcoes);
            if ($productSlug === 'impressao-de-livro') {
                $minOptions = 15;
            } elseif ($productSlug === 'impressao-de-revista') {
                $minOptions = 16; // 16 campos (0-15)
            }
            
            if (count($options) < $minOptions) {
                \Log::warning("Nem todas as opções foram mapeadas", [
                    'esperadas' => $minOptions,
                    'mapeadas' => count($options),
                    'opcoes_recebidas' => count($opcoes)
                ]);
                
                // Continuar mesmo assim se tiver pelo menos 10 opções (pode faltar algumas opcionais)
                if (count($options) < 10) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Nem todas as opções foram mapeadas para Keys. Cache pode estar desatualizado.'
                    ], 500);
                }
            }
            
            // Chamar API de pricing
            $url = "https://www.lojagraficaeskenazi.com.br/product/{$productSlug}/pricing";
            
            $payload = [
                'pricingParameters' => [
                    'KitParameters' => null,  // Campo obrigatório que o site envia
                    'Q1' => (string) $quantidade,
                    'Options' => $options
                ]
            ];
            
            \Log::info("Chamando API de pricing", [
                'url' => $url,
                'q1' => $quantidade,
                'options_count' => count($options)
            ]);
            
            // Headers para parecer uma requisição legítima do navegador
            $userAgents = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ];
            
            // Rotacionar User-Agent para parecer mais natural
            $userAgent = $userAgents[array_rand($userAgents)];
            
            // Adicionar pequeno delay aleatório (0.5-2s) para parecer mais humano
            usleep(rand(500000, 2000000));
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'User-Agent' => $userAgent,
                'Referer' => "https://www.lojagraficaeskenazi.com.br/product/{$productSlug}",
                'Origin' => 'https://www.lojagraficaeskenazi.com.br',
                'Connection' => 'keep-alive',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'X-Requested-With' => 'XMLHttpRequest'
            ])->timeout(10)->post($url, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['ErrorMessage'])) {
                    return response()->json([
                        'success' => false,
                        'error' => $data['ErrorMessage']
                    ], 400);
                }
                
                if (isset($data['Cost'])) {
                    $preco = (float) str_replace(',', '.', $data['Cost']);
                    
                    return response()->json([
                        'success' => true,
                        'price' => $preco,
                        'formatted' => $data['FormattedCost'] ?? "R$ " . number_format($preco, 2, ',', '.')
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'error' => 'API não retornou preço válido'
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error("Erro no proxy de pricing: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erro ao consultar preço: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Descobre Keys via scraping (uma vez)
     */
    private function descobrirKeysViaScraping(string $productSlug, array $opcoes): ?array
    {
        // Usar o script Python para descobrir Keys
        $scriptPath = base_path('mapear_keys_opcoes.py');
        
        if (!file_exists($scriptPath)) {
            \Log::error("Script mapear_keys_opcoes.py não encontrado");
            return null;
        }
        
        // Determinar Python command
        $pythonCmd = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        
        try {
            $process = new \Symfony\Component\Process\Process([
                $pythonCmd,
                $scriptPath
            ], base_path(), [
                'PATH' => '/usr/local/bin:/usr/bin:/bin',
                'HOME' => '/tmp'
            ]);
            
            $process->setTimeout(300); // 5 minutos
            $process->run();
            
            if (!$process->isSuccessful()) {
                \Log::error("Erro ao executar script de mapeamento: " . $process->getErrorOutput());
                return null;
            }
            
            // Ler arquivo gerado
            $arquivoMapeamento = base_path('mapeamento_keys_opcoes.json');
            
            if (!file_exists($arquivoMapeamento)) {
                \Log::error("Arquivo de mapeamento não foi gerado");
                return null;
            }
            
            $mapeamento = json_decode(file_get_contents($arquivoMapeamento), true);
            
            return $mapeamento['keys_reais'] ?? null;
            
        } catch (\Exception $e) {
            \Log::error("Exceção ao descobrir Keys: " . $e->getMessage());
            return null;
        }
    }
}

