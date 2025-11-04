<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

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
        // Detectar comando correto baseado no sistema operacional
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: tentar usar wrapper batch se disponível (Python 3.13)
            if (file_exists(base_path('scrapper/scrape_tempo_real_wrapper.bat'))) {
                $wrapperPath = base_path('scrapper/scrape_tempo_real_wrapper.bat');
                $command = ['cmd', '/c', $wrapperPath, $dados];
            } else {
                $command = ['python', $scriptPath, $dados];
            }
        } else {
            // Linux/Unix: usar python3 (que está no PATH)
            // Tentar python3 primeiro, se falhar o Process vai mostrar o erro
            $command = ['python3', $scriptPath, $dados];
        }
        
        try {
            $commandStr = implode(' ', array_map('escapeshellarg', $command));
            Log::error("DEBUG: Executando comando: {$commandStr}");
            
            // Verificar se proc_open está disponível
            if (!function_exists('proc_open')) {
                Log::error("DEBUG: proc_open não disponível, tentando alternativas");
                $fullCommand = "cd " . escapeshellarg(base_path()) . " && {$commandStr} 2>&1";
                
                // Tentar shell_exec primeiro
                if (function_exists('shell_exec')) {
                    Log::error("DEBUG: Usando shell_exec()");
                    $output = \shell_exec($fullCommand);
                    $output = trim($output ?? '');
                    
                    if (empty($output)) {
                        Log::error("Erro: shell_exec() retornou vazio");
                        return null;
                    }
                } elseif (function_exists('exec')) {
                    Log::error("DEBUG: Usando exec()");
                    $output = '';
                    $returnVar = 0;
                    \exec($fullCommand, $outputLines, $returnVar);
                    $output = implode("\n", $outputLines);
                    
                    if ($returnVar !== 0) {
                        Log::error("Erro ao executar script Python via exec()");
                        Log::error("Exit code: {$returnVar}");
                        Log::error("Output: {$output}");
                        return null;
                    }
                } else {
                    Log::error("Erro: Nenhuma função de execução disponível");
                    return null;
                }
            } else {
                // Usar Process normalmente
                $process = new Process($command, base_path());
                $process->setTimeout(120); // 2 minutos para scraping
                $process->run();

                if (!$process->isSuccessful()) {
                    $errorOutput = $process->getErrorOutput();
                    Log::error("Erro ao executar script Python: {$errorOutput}");
                    Log::error("Output: " . $process->getOutput());
                    return null;
                }

                $output = trim($process->getOutput());
            }
            
            $output = trim($output);
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

