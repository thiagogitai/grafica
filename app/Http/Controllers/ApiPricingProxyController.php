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
                // Se não tem cache, fazer scraping uma vez para descobrir Keys
                \Log::info("Cache de Keys não encontrado. Fazendo scraping para descobrir Keys...");
                $keysMap = $this->descobrirKeysViaScraping($productSlug, $opcoes);
                
                if ($keysMap) {
                    // Cachear por 24 horas
                    Cache::put($cacheKey, $keysMap, now()->addHours(24));
                    \Log::info("Keys descobertas e cacheadas: " . count($keysMap));
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Não foi possível descobrir as Keys das opções'
                    ], 500);
                }
            }
            
            // Mapear opções para Keys
            $options = [];
            foreach ($opcoes as $campo => $valor) {
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
            
            if (count($options) < count($opcoes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Nem todas as opções foram mapeadas para Keys. Cache pode estar desatualizado.'
                ], 500);
            }
            
            // Chamar API de pricing
            $url = "https://www.lojagraficaeskenazi.com.br/product/{$productSlug}/pricing";
            
            $payload = [
                'pricingParameters' => [
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

