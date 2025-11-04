<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProductPriceController extends Controller
{
    /**
     * Mapeamento de slugs de produtos para scripts de scraping
     */
    private const PRODUCT_SCRAPERS = [
        'impressao-de-livro' => 'scrape_tempo_real.py',
        'impressao-de-panfleto' => 'scrape_panfleto.py',
        'impressao-de-apostila' => 'scrape_apostila.py',
        'impressao-online-de-livretos-personalizados' => 'scrape_livreto.py',
        'impressao-de-revista' => 'scrape_revista.py',
        'impressao-de-tabloide' => 'scrape_tabloide.py',
        'impressao-de-jornal-de-bairro' => 'scrape_jornal.py',
        'impressao-de-guia-de-bairro' => 'scrape_guia.py',
    ];

    /**
     * Valida preço de um produto em tempo real
     */
    public function validatePrice(Request $request)
    {
        \Log::error("DEBUG: validatePrice chamado");
        
        // Aceitar tanto JSON quanto form data
        if ($request->isJson()) {
            $opcoes = $request->json()->all();
        } else {
            $opcoes = $request->all();
        }
        
        \Log::error("DEBUG: Opções recebidas: " . json_encode($opcoes));
        
        $quantidade = (int) ($opcoes['quantity'] ?? 1);
        $productSlug = $opcoes['product_slug'] ?? $request->input('product_slug');
        
        \Log::error("DEBUG: productSlug = {$productSlug}, quantidade = {$quantidade}");
        
        // Remover product_slug das opções
        unset($opcoes['product_slug']);

        // Garantir quantidade mínima de 50
        if ($quantidade < 50) {
            $quantidade = 50;
            $opcoes['quantity'] = $quantidade;
        }

        if ($quantidade <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Quantidade inválida',
                'validated' => false
            ], 400);
        }

        // Verificar se produto tem scraper
        if (!isset(self::PRODUCT_SCRAPERS[$productSlug])) {
            return response()->json([
                'success' => false,
                'error' => 'Produto não suporta validação de preço',
                'validated' => false
            ], 400);
        }

        // Gerar chave de cache
        $cacheKey = 'product_price_' . $productSlug . '_' . md5(json_encode($opcoes));

        // Tentar obter do cache
        $preco = Cache::get($cacheKey);
        
        \Log::error("DEBUG: Cache check - preco = " . ($preco !== null ? $preco : 'null'));

        if ($preco === null) {
            \Log::error("DEBUG: Iniciando scraping para produto: {$productSlug}");
            try {
                // Fazer scraping
                \Log::error("DEBUG: Dentro do try - Iniciando validação de preço para produto: {$productSlug}");
                \Log::info("Quantidade: {$quantidade}");
                \Log::info("Opções recebidas: " . json_encode($opcoes));
                $preco = $this->scrapePrecoTempoReal($opcoes, $quantidade, $productSlug);
                \Log::info("Preço retornado: " . ($preco !== null ? $preco : 'null'));

                if ($preco !== null && $preco > 0) {
                    // Armazenar no cache por 5 minutos
                    Cache::put($cacheKey, $preco, now()->addMinutes(5));
                    \Log::info("Preço validado com sucesso: R$ {$preco} para produto {$productSlug}");
                } else {
                    \Log::error("Validação de preço falhou para produto: {$productSlug}");
                    \Log::error("Opções: " . json_encode($opcoes));
                    \Log::error("Quantidade: {$quantidade}");
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'Não foi possível validar o preço. Tente novamente.',
                        'validated' => false
                    ], 500);
                }
            } catch (\Exception $e) {
                \Log::error("Exceção ao validar preço: " . $e->getMessage());
                \Log::error("Trace: " . $e->getTraceAsString());
                \Log::error("Arquivo: " . $e->getFile() . " Linha: " . $e->getLine());
                
                return response()->json([
                    'success' => false,
                    'error' => 'Erro interno ao validar preço. Tente novamente.',
                    'validated' => false,
                    'debug' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'price' => $preco,
            'validated' => true,
            'quantity' => $quantidade
        ]);
    }

    /**
     * Executa scraping em tempo real usando Python
     */
    private function scrapePrecoTempoReal(array $opcoes, int $quantidade, string $productSlug): ?float
    {
        $scriptName = self::PRODUCT_SCRAPERS[$productSlug] ?? 'scrape_tempo_real.py';
        $scriptPath = base_path('scrapper/' . $scriptName);

        if (!file_exists($scriptPath)) {
            \Log::error("Script de scraping não encontrado: {$scriptPath}");
            return null;
        }

        // Preparar dados para o script Python
        $dados = json_encode([
            'opcoes' => $opcoes,
            'quantidade' => $quantidade
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Executar o script Python
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
            $command = ['python3', $scriptPath, $dados];
        }
        
        try {
            $commandStr = implode(' ', array_map('escapeshellarg', $command));
            \Log::error("DEBUG: Executando comando: {$commandStr}");
            
            // Verificar se proc_open está disponível
            if (!function_exists('proc_open')) {
                \Log::error("DEBUG: proc_open não disponível, tentando alternativas");
                // Garantir que PATH inclui /usr/local/bin onde está o chromedriver
                // Usar export PATH para garantir que seja aplicado
                $fullCommand = "cd " . escapeshellarg(base_path()) . " && export PATH=/usr/local/bin:/usr/bin:/bin:\$PATH && {$commandStr} 2>&1";
                \Log::error("DEBUG: Comando completo: {$fullCommand}");
                
                // Tentar shell_exec primeiro
                if (function_exists('shell_exec')) {
                    \Log::error("DEBUG: Usando shell_exec()");
                    // Capturar tanto stdout quanto stderr (2>&1 já está no comando)
                    $output = \shell_exec($fullCommand);
                    $output = trim($output ?? '');
                    $outputLen = strlen($output);
                    \Log::error("DEBUG: shell_exec() retornou, tamanho: {$outputLen}");
                    
                    // Sempre logar o output, mesmo se vazio
                    if ($outputLen > 0) {
                        \Log::error("DEBUG: Output completo (primeiros 2000 chars): " . substr($output, 0, 2000));
                        if ($outputLen > 2000) {
                            \Log::error("DEBUG: Output (últimos 500 chars): " . substr($output, -500));
                        }
                    } else {
                        \Log::error("DEBUG: Output está VAZIO - script pode não ter executado");
                        // Tentar executar novamente com mais verbosidade
                        $testCommand = $fullCommand . " ; echo 'EXIT_CODE:' $?";
                        $testOutput = \shell_exec($testCommand);
                        \Log::error("DEBUG: Teste de execução retornou: " . substr($testOutput ?? 'VAZIO', 0, 1000));
                        return null;
                    }
                } elseif (function_exists('exec')) {
                    \Log::error("DEBUG: Usando exec()");
                    $output = '';
                    $returnVar = 0;
                    \exec($fullCommand, $outputLines, $returnVar);
                    $output = implode("\n", $outputLines);
                    \Log::error("DEBUG: exec() retornou, exit code: {$returnVar}");
                    
                    if ($returnVar !== 0) {
                        \Log::error("Erro ao executar script Python ({$scriptName}) via exec()");
                        \Log::error("Exit code: {$returnVar}");
                        \Log::error("Output: {$output}");
                        return null;
                    }
                } else {
                    \Log::error("Erro: Nenhuma função de execução disponível (proc_open, shell_exec, exec)");
                    return null;
                }
            } else {
                // Usar Process normalmente
                \Log::error("DEBUG: Usando Process (proc_open disponível)");
                $process = new Process($command, base_path());
                $process->setTimeout(120); // 2 minutos para scraping
                $process->setEnv([
                    'PATH' => '/usr/local/bin:/usr/bin:/bin:' . (getenv('PATH') ?: ''),
                    'DISPLAY' => ':99',
                    'HOME' => getenv('HOME') ?: '/tmp'
                ]);
                $process->run();

                // Sempre logar output, mesmo se bem-sucedido
                $errorOutput = $process->getErrorOutput();
                $output = $process->getOutput();
                $exitCode = $process->getExitCode();
                $isSuccessful = $process->isSuccessful();
                
                \Log::error("DEBUG: Process executado - Success: " . ($isSuccessful ? 'true' : 'false') . ", Exit code: {$exitCode}");
                $stderrLen = strlen($errorOutput);
                $stdoutLen = strlen($output);
                \Log::error("DEBUG: stderr tamanho: {$stderrLen}, stdout tamanho: {$stdoutLen}");
                // Log completo do stderr para debug
                \Log::error("DEBUG: stderr completo (primeiros 5000 chars): " . substr($errorOutput, 0, 5000));
                if ($stderrLen > 5000) {
                    \Log::error("DEBUG: stderr (meio, 2000 chars): " . substr($errorOutput, 2000, 2000));
                    \Log::error("DEBUG: stderr (últimos 1000 chars): " . substr($errorOutput, -1000));
                }
                // Se houver erro de ChromeDriver, logar traceback completo
                if (strpos($errorOutput, 'SessionNotCreatedException') !== false || strpos($errorOutput, 'Traceback') !== false) {
                    \Log::error("DEBUG: ERRO ChromeDriver detectado - stderr COMPLETO: " . $errorOutput);
                }
                \Log::error("DEBUG: stdout completo (primeiros 5000 chars): " . substr($output, 0, 5000));
                
                if (!$isSuccessful) {
                    \Log::error("Erro ao executar script Python ({$scriptName})");
                    \Log::error("Exit code: {$exitCode}");
                    \Log::error("Error output completo: {$errorOutput}");
                    \Log::error("Standard output completo: {$output}");
                    \Log::error("Comando executado: " . implode(' ', $command));
                    return null;
                }

                $output = trim($output);
            }
            
            $output = trim($output);
            
            if (empty($output)) {
                \Log::error("Script Python retornou vazio: {$scriptName}");
                return null;
            }

            \Log::info("Output do script Python: " . substr($output, 0, 500));
            
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error("Erro ao decodificar JSON do scraper ({$scriptName}): " . json_last_error_msg());
                \Log::error("Output completo do script: {$output}");
                \Log::error("Tamanho do output: " . strlen($output));
                return null;
            }

            if (!isset($result['price']) || $result['price'] <= 0) {
                $errorMsg = $result['error'] ?? 'Preço não encontrado';
                $traceback = $result['traceback'] ?? null;
                \Log::error("Script não retornou preço válido ({$scriptName}): {$errorMsg}");
                if ($traceback) {
                    \Log::error("Traceback do script: {$traceback}");
                }
                \Log::error("Resultado completo: " . json_encode($result));
                return null;
            }

            return (float) $result['price'];
            
        } catch (\Exception $e) {
            \Log::error("Exceção ao executar script Python ({$scriptName}): " . $e->getMessage());
            \Log::error("Trace: " . $e->getTraceAsString());
            return null;
        }
    }
}

