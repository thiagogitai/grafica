<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LivroPriceController extends Controller
{
    /**
     * Calcula preço em tempo real fazendo scraping do site Eskenazi
     */
    public function calcularPreco(Request $request)
    {
        $opcoes = $request->all();
        $quantidade = $request->input('quantity', 50);
        
        // Criar chave de cache baseada nas opções
        $cacheKey = 'livro_price_' . md5(json_encode($opcoes) . $quantidade);
        
        // Verificar cache (5 minutos)
        $precoCacheado = Cache::get($cacheKey);
        if ($precoCacheado !== null) {
            return response()->json([
                'success' => true,
                'price' => $precoCacheado,
                'cached' => true
            ]);
        }
        
        // Executar scraping em background usando Python
        try {
            $preco = $this->scrapePrecoTempoReal($opcoes, $quantidade);
            
            if ($preco !== null) {
                // Cachear resultado
                Cache::put($cacheKey, $preco, now()->addMinutes(5));
                
                return response()->json([
                    'success' => true,
                    'price' => $preco,
                    'cached' => false
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Não foi possível obter o preço'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Erro ao calcular preço livro: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Erro ao calcular preço: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Executa scraping em tempo real usando Python
     */
    private function scrapePrecoTempoReal(array $opcoes, int $quantidade): ?float
    {
        // Usar versão que funciona (scrape_tempo_real.py)
        $scriptPath = base_path('scrapper/scrape_tempo_real.py');
        
        if (!file_exists($scriptPath)) {
            Log::error("Script Python não encontrado: {$scriptPath}");
            return null;
        }
        
        // Preparar dados para o script Python
        $dados = json_encode([
            'opcoes' => $opcoes,
            'quantidade' => $quantidade
        ]);
        
        // Executar script Python usando Process para melhor controle
        try {
            $process = \Symfony\Component\Process\Process::fromShellCommandline(
                'python ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($dados),
                base_path()
            );
            $process->setTimeout(60);
            $process->run();
            
            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                Log::error("Erro ao executar script Python: {$errorOutput}");
                Log::error("Output: " . $process->getOutput());
                return null;
            }
            
            $output = trim($process->getOutput());
            if (empty($output)) {
                Log::error("Script Python retornou vazio");
                return null;
            }
            
            $resultado = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Erro ao decodificar JSON: " . json_last_error_msg());
                Log::error("Output do script: " . $output);
                return null;
            }
            
            if ($resultado && isset($resultado['price'])) {
                return (float) $resultado['price'];
            }
            
            Log::error("Script não retornou preço válido. Resultado: " . json_encode($resultado));
            return null;
            
        } catch (\Exception $e) {
            Log::error("Exceção ao executar script Python: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());
            return null;
        }
    }
}

