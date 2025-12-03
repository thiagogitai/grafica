<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PricingService
{
    private const BASE_URL = 'https://www.lojagraficaeskenazi.com.br';
    private ?string $currentSlug = null;

    public function supports(string $slug): bool
    {
        return is_file($this->fieldKeysPath($slug));
    }

    public function quote(string $slug, array $options): array
    {
        if (!$this->supports($slug)) {
            throw new \RuntimeException("Field keys map missing for {$slug}");
        }

        $this->currentSlug = $slug;
        try {
            $payload = $this->buildPayload($slug, $options);
            
            // Obter cookies/sessão antes de fazer a requisição de preço
            $cookies = $this->obtainSessionCookies($slug);

            $lastError = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    $httpClient = Http::withHeaders($this->defaultHeaders($slug));
                    
                    // Adicionar cookies se disponíveis
                    if (!empty($cookies)) {
                        $httpClient = $httpClient->withCookies($cookies, parse_url(self::BASE_URL, PHP_URL_HOST));
                    }
                    
                    $response = $httpClient->timeout(30)
                        ->post(self::BASE_URL . "/product/{$slug}/pricing", $payload);

                    if (!$response->successful()) {
                        $lastError = new \RuntimeException(sprintf(
                            'API pricing request failed for %s (%s): %s',
                            $slug,
                            $response->status(),
                            $response->body()
                        ));
                        // Apenas tenta de novo em status >= 500
                        if ($response->status() < 500 || $attempt === 3) {
                            throw $lastError;
                        }
                    } else {
                        $data = $response->json();
                        if (!is_array($data)) {
                            throw new \RuntimeException('Unexpected pricing response format.');
                        }

                        // Verificar se há erro na resposta da API
                        $errorMessage = $data['ErrorMessage'] ?? $data['InternalErrorMessage'] ?? null;
                        if ($errorMessage && !empty(trim($errorMessage))) {
                            \Log::warning("API retornou erro", [
                                'slug' => $slug,
                                'error' => $errorMessage,
                                'payload' => $payload
                            ]);
                        }

                        $price = $this->extractPrice($data);
                        if ($price === null || $price == 0) {
                            // Se preço é zero e há erro, lançar exceção
                            if ($errorMessage && !empty(trim($errorMessage))) {
                                throw new \RuntimeException("API retornou erro: {$errorMessage}");
                            }
                            // Se preço é zero mas não há erro explícito, pode ser combinação inválida
                            if ($price == 0) {
                                throw new \RuntimeException('Pricing API returned zero cost. This combination may not be available.');
                            }
                            throw new \RuntimeException('Pricing API did not return a numeric value.');
                        }

                        return [
                            'price' => $price,
                            'payload' => $payload,
                            'response' => $data,
                        ];
                    }
                } catch (\Throwable $e) {
                    $lastError = $e;
                    if ($attempt === 3) {
                        throw $lastError;
                    }
                }

                usleep(150000); // pequeno backoff antes do retry
            }

            throw $lastError ?? new \RuntimeException('Unknown pricing error');
        } finally {
            $this->currentSlug = null;
        }
    }

    protected function buildPayload(string $slug, array $options): array
    {
        $definition = $this->loadFieldDefinition($slug);
        $quantityKey = $definition['quantity_key'] ?? 'Q1';
        
        // Para apostila, quantidade mínima é 20, não 50
        $rawQuantity = $options['quantity'] ?? $options['qty'] ?? 1;
        if ($slug === 'impressao-de-apostila') {
            $quantity = max(20, (int) $rawQuantity); // Mínimo 20 para apostila
        } else {
            $quantity = $this->normalizeQuantity($rawQuantity);
        }
        
        // Log para debug
        if ($slug === 'impressao-de-apostila' && $quantity < 20) {
            \Log::warning("Quantidade ajustada para apostila", [
                'original' => $rawQuantity,
                'ajustada' => $quantity
            ]);
        }

        $fields = $definition['fields'] ?? [];
        $preparedFields = [];
        foreach ($fields as $name => $meta) {
            $preparedFields[] = array_merge($meta, ['name' => $name]);
        }
        usort($preparedFields, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $mapPath = base_path('mapeamento_keys_todos_produtos.json');
        $textToKey = [];
        $keyToText = [];
        if (is_file($mapPath)) {
            $mapData = json_decode(file_get_contents($mapPath), true);
            $textToKey = $mapData['mapeamento_por_produto'][$slug] ?? [];
            foreach ($textToKey as $text => $key) {
                if (!isset($keyToText[$key])) {
                    $keyToText[$key] = $text;
                }
            }
        }

        $apiOptions = [];
        foreach ($preparedFields as $field) {
            $fieldName = $field['name'];
            
            // Garantir que a key existe ANTES de tentar resolver o valor
            if (empty($field['key'])) {
                \Log::warning("Key não encontrada para campo", [
                    'field' => $fieldName,
                    'field_data' => $field,
                    'slug' => $slug
                ]);
                continue; // Pular campos sem key
            }
            
            $value = $this->resolveOptionValue($fieldName, $options, $field);
            
            // Se não há valor e não há default, pular este campo (não é obrigatório)
            if ($value === null || $value === '') {
                // Log para debug
                \Log::debug("Campo sem valor ignorado", [
                    'field' => $fieldName,
                    'key' => $field['key'],
                    'options_received' => array_keys($options)
                ]);
                continue;
            }

            // Se o valor já veio como array com Key/Value, honrar direto
            if (is_array($value) && isset($value['Key'])) {
                $apiOptions[] = [
                    'Key' => $value['Key'],
                    'Value' => $value['Value'] ?? '',
                ];
                continue;
            }

            // Se o valor for string e corresponder a uma Key do mapeamento, usar essa key
            if (is_string($value) && isset($keyToText[$value])) {
                $apiOptions[] = [
                    'Key' => $value,
                    'Value' => $keyToText[$value],
                ];
                continue;
            }

            $apiOptions[] = [
                'Key' => $field['key'],
                'Value' => $value,
            ];
        }
        // Normalizar valores usando texto canônico do mapeamento (preserva espaçamentos que a API espera)
        $apiOptions = $this->canonicalizeOptionValues($slug, $apiOptions);
        
        // Log para verificar se todas as keys foram incluídas
        if ($slug === 'impressao-de-apostila') {
            \Log::info("Payload construído para apostila", [
                'total_fields' => count($preparedFields),
                'total_options' => count($apiOptions),
                'keys_incluidas' => array_column($apiOptions, 'Key')
            ]);
        }

        $payload = [
            'pricingParameters' => [
                'KitParameters' => null,
                $quantityKey => (string) $quantity,
                'Options' => $apiOptions,
            ],
        ];
        
        // Para apostila, adicionar hdnTotalCost e hdnTotalWeight
        // Esses campos são calculados pelo frontend, mas podem ser necessários
        // Vamos adicionar como null inicialmente e deixar a API calcular
        // Se a API exigir valores específicos, precisaremos calcular antes
        if ($slug === 'impressao-de-apostila') {
            // Inicialmente não incluímos, pois são calculados pela API
            // Se a API rejeitar, podemos tentar calcular ou deixar vazio
            // $payload['pricingParameters']['hdnTotalCost'] = null;
            // $payload['pricingParameters']['hdnTotalWeight'] = null;
        }
        
        return $payload;
    }

    protected function normalizeQuantity($quantity): int
    {
        $quantity = (int) $quantity;
        return $quantity > 0 ? $quantity : 1;
    }

    protected function resolveOptionValue(string $name, array $options, array $meta)
    {
        $receivedValue = null;
        
        if (array_key_exists($name, $options)) {
            $receivedValue = $options[$name];
        } elseif (isset($options['options']) && array_key_exists($name, $options['options'])) {
            $receivedValue = $options['options'][$name];
        } else {
            // Alias para nomes divergentes entre field-keys e config (ex.: formato vs formato_miolo_paginas)
            $aliases = [
                'impressao-de-revista' => [
                    'formato_miolo_paginas' => 'formato',
                ],
                'impressao-de-livro' => [
                    'formato_miolo_paginas' => 'formato',
                ],
            ];
            if ($this->currentSlug && isset($aliases[$this->currentSlug][$name])) {
                $aliasName = $aliases[$this->currentSlug][$name];
                if (array_key_exists($aliasName, $options)) {
                    $receivedValue = $options[$aliasName];
                } elseif (isset($options['options']) && array_key_exists($aliasName, $options['options'])) {
                    $receivedValue = $options['options'][$aliasName];
                }
            }

            // Log para debug quando campo não é encontrado
            if ($this->currentSlug === 'impressao-de-apostila' && $name === 'formato') {
                \Log::warning("Campo formato não encontrado nas opções", [
                    'name' => $name,
                    'options_keys' => array_keys($options),
                    'has_options_key' => isset($options['options']),
                    'options_options_keys' => isset($options['options']) ? array_keys($options['options']) : []
                ]);
            }
            if ($receivedValue === null) {
                return $meta['default'] ?? null;
            }
        }

        // Mapeamento especial para campos da apostila
        if ($this->currentSlug === 'impressao-de-apostila') {
            // folhas_miolo_1: API espera formato "Miolo X folhas" ao invés de número
            if ($name === 'folhas_miolo_1') {
                $numFolhas = (int) $receivedValue;
                if ($numFolhas > 0) {
                    return "Miolo {$numFolhas} folhas";
                }
                return "Miolo 4 folhas"; // fallback
            }
            
            // folhas_miolo_2: Lógica especial
            // Se valor é 0 ou não há papel_miolo_2 válido, retornar "NÃO TEM MIOLO 2"
            // Caso contrário, retornar "Miolo X folhas"
            if ($name === 'folhas_miolo_2') {
                $numFolhas = (int) $receivedValue;
                // Verificar se há papel_miolo_2 configurado e válido
                $papelMiolo2 = $options['papel_miolo_2'] ?? $options['options']['papel_miolo_2'] ?? null;
                
                // Se não há folhas OU não há papel_miolo_2 válido, retornar "NÃO TEM MIOLO 2"
                if ($numFolhas <= 0 || empty($papelMiolo2) || $papelMiolo2 === 'Nenhum' || trim($papelMiolo2) === '') {
                    return "NÃO TEM MIOLO 2";
                }
                
                // Se há folhas e papel_miolo_2 válido, retornar formato "Miolo X folhas"
                return "Miolo {$numFolhas} folhas";
            }
        }

        // Converter para string para processamento
        $receivedValue = (string) $receivedValue;

        // Tentar encontrar o valor exato no JSON de configuração
        // Isso garante que valores com espaços sejam preservados
        if ($this->currentSlug) {
            $configPath = \App\Services\ProductConfig::configPathForSlug($this->currentSlug);
            if (file_exists($configPath)) {
                $config = json_decode(file_get_contents($configPath), true);
                
                if ($config && is_array($config)) {
                    foreach ($config['options'] ?? [] as $opt) {
                        if (($opt['name'] ?? '') === $name) {
                            foreach ($opt['choices'] ?? [] as $choice) {
                                $choiceValue = $choice['value'] ?? $choice['label'] ?? '';
                                $choiceLabel = $choice['label'] ?? $choiceValue;
                                
                                // Match exato (case-insensitive, ignorando espaços extras)
                                $receivedTrimmed = trim($receivedValue);
                                $choiceValueTrimmed = trim($choiceValue);
                                $choiceLabelTrimmed = trim($choiceLabel);
                                
                                if (strcasecmp($choiceValueTrimmed, $receivedTrimmed) === 0 ||
                                    strcasecmp($choiceLabelTrimmed, $receivedTrimmed) === 0) {
                                    // Retornar o valor exato do JSON (com espaços preservados)
                                    return $choiceValue;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Se não encontrou match, retornar o valor recebido
        return $receivedValue;
    }

    // Substitui o Value pelo texto canônico do mapeamento (evita perder espaçamentos que a API externa espera).
    private function canonicalizeOptionValues(string $slug, array $apiOptions): array
    {
        $mapPath = base_path('mapeamento_keys_todos_produtos.json');
        if (!is_file($mapPath)) {
            return $apiOptions;
        }

        $data = json_decode(file_get_contents($mapPath), true);
        $byProduct = $data['mapeamento_por_produto'][$slug] ?? null;
        if (!$byProduct || !is_array($byProduct)) {
            return $apiOptions;
        }

        $keyToText = [];
        foreach ($byProduct as $text => $key) {
            if (!isset($keyToText[$key])) {
                $keyToText[$key] = $text;
            }
        }

        foreach ($apiOptions as &$opt) {
            $key = $opt['Key'] ?? null;
            $hasValue = isset($opt['Value']) && trim((string) $opt['Value']) !== '';
            if ($key && isset($keyToText[$key]) && !$hasValue) {
                $opt['Value'] = $keyToText[$key];
            }
        }
        unset($opt);

        return $apiOptions;
    }

    protected function loadFieldDefinition(string $slug): array
    {
        $path = $this->fieldKeysPath($slug);
        $contents = file_get_contents($path);
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid field key definition for {$slug}");
        }

        return $data;
    }

    protected function extractPrice(array $response): ?float
    {
        $candidates = [
            Arr::get($response, 'Cost'),
            Arr::get($response, 'NonMarkupCost'),
            Arr::get($response, 'price'),
            Arr::get($response, 'FormattedCost'),
            Arr::get($response, 'FormattedNonMarkupCost'),
        ];

        foreach ($candidates as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $number = $this->toFloat($value);
            if ($number !== null) {
                return $number;
            }
        }

        return null;
    }

    protected function toFloat($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace('.', '', $value);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = preg_replace('/[^0-9\\.]/', '', (string) $normalized);

        return $normalized !== '' && is_numeric($normalized)
            ? (float) $normalized
            : null;
    }

    protected function fieldKeysPath(string $slug): string
    {
        return base_path("resources/data/products/{$slug}-field-keys.json");
    }

    protected function obtainSessionCookies(string $slug): array
    {
        try {
            // Fazer uma requisição GET para a página do produto para obter cookies
            $pageResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            ])
            ->timeout(10)
            ->get(self::BASE_URL . "/product/{$slug}");
            
            if ($pageResponse->successful()) {
                $cookies = [];
                
                // Extrair de Set-Cookie headers
                $cookieHeaders = $pageResponse->headers()['Set-Cookie'] ?? [];
                if (is_string($cookieHeaders)) {
                    $cookieHeaders = [$cookieHeaders];
                }
                
                foreach ($cookieHeaders as $cookieHeader) {
                    // Extrair nome e valor do cookie
                    if (preg_match('/([^=]+)=([^;]+)/', $cookieHeader, $matches)) {
                        $cookieName = trim($matches[1]);
                        $cookieValue = trim($matches[2]);
                        
                        // Capturar apenas cookies importantes
                        if (in_array($cookieName, ['ASP.NET_SessionId', '__RequestVerificationToken'])) {
                            $cookies[$cookieName] = $cookieValue;
                        }
                    }
                }
                
                if (!empty($cookies)) {
                    \Log::info("Cookies obtidos com sucesso", [
                        'slug' => $slug,
                        'cookies' => array_keys($cookies)
                    ]);
                }
                
                return $cookies;
            }
        } catch (\Throwable $e) {
            \Log::warning("Falha ao obter cookies de sessão", [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
        }
        
        return [];
    }

    protected function defaultHeaders(string $slug): array
    {
        // Cabeçalho fixo idêntico ao capturado no browser oficial (01/12/2025)
        // Baseado em requisição bem-sucedida do site oficial
        // IMPORTANTE: Alguns headers podem ser obrigatórios para a API aceitar a requisição
        return [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Content-Type' => 'application/json; charset=UTF-8;',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            'Referer' => self::BASE_URL . "/product/{$slug}",
            'Origin' => self::BASE_URL,
            'Connection' => 'keep-alive',
            'Priority' => 'u=1, i', // Header observado na requisição bem-sucedida
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'X-Requested-With' => 'XMLHttpRequest',
            // NOTA: Cookies (__RequestVerificationToken, ASP.NET_SessionId) podem ser necessários
            // mas são dinâmicos e precisam ser obtidos de uma sessão ativa
            // Se a API rejeitar requisições, pode ser necessário implementar obtenção de sessão primeiro
        ];
    }

}
