<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatrixPricingService
{
    /**
     * Obtém preço via API do site matriz
     * 
     * @param string $productSlug Slug do produto (ex: 'impressao-de-revista')
     * @param array $opcoes Opções do produto (chave => valor)
     * @param int $quantidade Quantidade
     * @return float|null Preço ou null se não encontrado
     */
    public function obterPrecoViaAPI(string $productSlug, array $opcoes, int $quantidade): ?float
    {
        try {
            // URL da API de pricing
            $url = "https://www.lojagraficaeskenazi.com.br/product/{$productSlug}/pricing";
            
            // Construir Options com Keys
            // TODO: Implementar mapeamento de valores para Keys
            // Por enquanto, vamos precisar fazer scraping para obter as Keys primeiro
            
            $pricingParameters = [
                'Q1' => (string) $quantidade,
                'Options' => $this->mapearOpcoesParaKeys($opcoes, $productSlug)
            ];
            
            $payload = [
                'pricingParameters' => $pricingParameters
            ];
            
            \Log::info("DEBUG: Chamando API de pricing", [
                'url' => $url,
                'payload' => $payload
            ]);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer' => "https://www.lojagraficaeskenazi.com.br/product/{$productSlug}",
                'Origin' => 'https://www.lojagraficaeskenazi.com.br'
            ])->timeout(10)->post($url, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                \Log::info("DEBUG: Resposta da API", [
                    'data' => $data
                ]);
                
                // Verificar se há erro
                if (!empty($data['ErrorMessage'])) {
                    \Log::warning("API retornou erro: " . $data['ErrorMessage']);
                    return null;
                }
                
                // Retornar preço (Cost é string, converter para float)
                if (isset($data['Cost'])) {
                    $preco = (float) str_replace(',', '.', $data['Cost']);
                    return $preco;
                }
            } else {
                \Log::error("Erro ao chamar API de pricing", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error("Exceção ao chamar API de pricing: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Mapeia opções (valores) para Keys (hashes) usadas na API
     * 
     * @param array $opcoes Opções do produto
     * @param string $productSlug Slug do produto
     * @return array Array de ['Key' => hash, 'Value' => valor]
     */
    private function mapearOpcoesParaKeys(array $opcoes, string $productSlug): array
    {
        // TODO: Implementar cache de mapeamento
        // Por enquanto, precisamos fazer scraping para obter as Keys
        
        // Carregar mapeamento do arquivo (se existir)
        // Primeiro tentar arquivo específico do produto
        $arquivoMapeamento = storage_path("app/mapeamento_keys_{$productSlug}.json");
        
        // Se não existir, tentar arquivo genérico
        if (!file_exists($arquivoMapeamento)) {
            $arquivoMapeamento = base_path("mapeamento_keys_opcoes.json");
        }
        
        if (file_exists($arquivoMapeamento)) {
            $mapeamento = json_decode(file_get_contents($arquivoMapeamento), true);
            $keysMap = $mapeamento['keys_reais'] ?? [];
            
            // Se não encontrou em keys_reais, tentar em mapeamento_selects
            if (empty($keysMap) && isset($mapeamento['mapeamento_selects'])) {
                // Tentar extrair keys do mapeamento de selects
                foreach ($mapeamento['mapeamento_selects'] as $selectData) {
                    foreach ($selectData['options'] ?? [] as $opt) {
                        if (!empty($opt['key']) && !empty($opt['text'])) {
                            $keysMap[trim($opt['text'])] = $opt['key'];
                        }
                    }
                }
            }
            
            if (!empty($keysMap)) {
                $result = [];
                foreach ($opcoes as $campo => $valor) {
                    if ($campo === 'quantity') {
                        continue;
                    }
                    
                    $valorStr = trim((string) $valor);
                    
                    // Match exato primeiro
                    if (isset($keysMap[$valorStr])) {
                        $result[] = [
                            'Key' => $keysMap[$valorStr],
                            'Value' => $valorStr
                        ];
                        continue;
                    }
                    
                    // Match parcial (case-insensitive)
                    $encontrado = false;
                    foreach ($keysMap as $texto => $key) {
                        $textoTrim = trim($texto);
                        $valorTrim = trim($valorStr);
                        
                        // Match exato (case-insensitive)
                        if (strcasecmp($textoTrim, $valorTrim) === 0) {
                            $result[] = [
                                'Key' => $key,
                                'Value' => $textoTrim
                            ];
                            $encontrado = true;
                            break;
                        }
                        
                        // Match parcial
                        if (stripos($textoTrim, $valorTrim) !== false || stripos($valorTrim, $textoTrim) !== false) {
                            $result[] = [
                                'Key' => $key,
                                'Value' => $textoTrim
                            ];
                            $encontrado = true;
                            break;
                        }
                    }
                    
                    if (!$encontrado) {
                        \Log::warning("Key não encontrada para opção: {$campo} = {$valorStr}");
                    }
                }
                
                return $result;
            }
        }
        
        // Se não tem mapeamento, retornar vazio (vai precisar fazer scraping)
        \Log::warning("Mapeamento de Keys não encontrado para {$productSlug}. Execute o script mapear_keys_opcoes.py primeiro.");
        return [];
    }
}

