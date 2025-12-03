<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductConfig;
use App\Services\Pricing;
use App\Http\Controllers\ProductPriceController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
    {
        if ($response = $this->ensurePurchasingEnabled()) {
            return $response;
        }

        $cart = session()->get('cart', []);
        $total = 0;
        foreach ($cart as $item) {
            if (isset($item['line_total'])) {
                $total += $item['line_total'];
                continue;
            }
            $unit = (float) ($item['price'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 1);
            $total += $unit * max(1, $qty);
        }

        $shippingDefaults = [
            'postal_code' => null,
            'service' => 'PAC',
            'carrier' => 'Correios',
            'street' => null,
            'number' => null,
            'complement' => null,
            'district' => null,
            'city' => null,
            'state' => null,
            'price' => 0,
            'status' => 'pending',
        ];
        $shipping = array_merge($shippingDefaults, session()->get('cart_shipping', []));
        $shippingCost = (float) ($shipping['price'] ?? 0);
        $orderTotal = $total + $shippingCost;

        return view('cart', compact('cart', 'total', 'shipping', 'shippingCost', 'orderTotal'));
    }

    public function add(Request $request, Product $product)
    {
        if ($response = $this->ensurePurchasingEnabled($product)) {
            return $response;
        }

        $cart = session()->get('cart', []);

        // Get selected options - pegar todas as opções do formulário
        $options = $request->input('options', []);
        if (empty($options)) {
            // Fallback para campos antigos
            $options = $request->only(['capa_tipo', 'paginas', 'papel_tipo', 'uploaded_file_path']);
        }
        $quantity = (int) ($request->input('quantity') ?? $request->input('options.quantity') ?? 1);

        $shippingMetaRaw = $request->input('shipping_meta');
        $shippingMeta = null;
        if (is_string($shippingMetaRaw) && $shippingMetaRaw !== '') {
            $decodedShipping = json_decode($shippingMetaRaw, true);
            if (is_array($decodedShipping)) {
                $shippingMeta = $decodedShipping;
            }
        }

        // Garantir quantidade mínima por produto (alguns aceitam 20 unidades)
        $minQuantityBySlug = [
            'impressao-de-apostila' => 20,
            'impressao-de-tabloide' => 20,
        ];
        $minQuantity = $minQuantityBySlug[$pricingSlug ?? ''] ?? 50;
        $quantity = max($minQuantity, (int) $quantity);
        // Atualiza também no array de opções para manter consistência com o frontend
        $options['quantity'] = $quantity;

        // Calcula preço: se houver config JSON do produto, usa as regras do JSON (preço final + extras)
        $slugAliases = [
            'livro' => 'impressao-de-livro',
            'apostila' => 'impressao-de-apostila',
            'jornal' => 'impressao-de-jornal-de-bairro',
            'jornal-de-bairro' => 'impressao-de-jornal-de-bairro',
            'livretos' => 'impressao-online-de-livretos-personalizados',
            'revista' => 'impressao-de-revista',
            'tabloide' => 'impressao-de-tabloide',
            'panfleto' => 'impressao-de-panfleto',
        ];

        $configSlug = null;
        if ($product->usesConfigTemplate() || $product->template === Product::TEMPLATE_CONFIG_AUTO) {
            $configSlug = $product->templateSlug();
            if (!$configSlug || $product->template === Product::TEMPLATE_CONFIG_AUTO) {
                $configSlug = ProductConfig::slugForProduct($product);
            }
        }
        if ($configSlug && isset($slugAliases[$configSlug])) {
            $configSlug = $slugAliases[$configSlug];
        }
        $config = ProductConfig::loadForProduct($product, $configSlug);
        $detectedSlug = ProductConfig::slugForProduct($product);
        if ($detectedSlug && isset($slugAliases[$detectedSlug])) {
            $detectedSlug = $slugAliases[$detectedSlug];
        }
        $pricingSlug = $detectedSlug ?: $configSlug;
        if (!$pricingSlug && $configSlug) {
            $pricingSlug = $configSlug;
        }
        if ($pricingSlug && isset($slugAliases[$pricingSlug])) {
            $pricingSlug = $slugAliases[$pricingSlug];
        }
        if (!$config && $pricingSlug) {
            $config = ProductConfig::loadForProduct($product, $pricingSlug);
        }
        
        // VALIDAÇÃO DUPLA: Validar preço no checkout antes de adicionar ao carrinho
        $produtosComValidacao = [
            'impressao-de-livro',
            'impressao-de-panfleto',
            'impressao-de-apostila',
            'impressao-online-de-livretos-personalizados',
            'impressao-de-revista',
            'impressao-de-tabloide',
            'impressao-de-jornal-de-bairro',
            'impressao-de-guia-de-bairro'
        ];
        $requiresMatrixValidation = in_array($pricingSlug, $produtosComValidacao);

        $unitPrice = 0;
        $lineTotal = 0;
        $skipMarkup = false; // Se usarmos preço já com markup vindo do front, não aplicar markup novamente

        if ($requiresMatrixValidation) {
            // VALIDAÇÃO DUPLA: Validar preço antes de adicionar ao carrinho
            /** @var ProductPriceController $priceController */
            $priceController = app(ProductPriceController::class);
            // Preparar dados para validação
            $validationPayload = array_merge($options, [
                'quantity' => $quantity,
                'product_slug' => $pricingSlug
            ]);
            
            $validationRequest = new Request();
            $validationRequest->merge($validationPayload);
            $validationRequest->headers->set('Content-Type', 'application/json');
            $validationResponse = $priceController->validatePrice($validationRequest);
            $validationResult = json_decode($validationResponse->getContent(), true);
            
            if (!($validationResult['success'] ?? false) || !($validationResult['validated'] ?? false)) {
                // Se o front já enviou um preço calculado previamente, usar como fallback para não travar o fluxo
                $postedTotal = (float) ($request->input('price') ?? 0);
                if ($postedTotal <= 0) {
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Erro ao validar preço no checkout. Por favor, tente novamente.',
                            'error' => 'Validação de preço falhou no checkout'
                        ], 400);
                    }
                    return redirect()->back()->withErrors(['price' => 'Erro ao validar preço no checkout. Por favor, tente novamente.']);
                }

                // Considera preço já com markup (veio do front-end), então não aplicar markup novamente
                $totalPrice = $postedTotal;
                $skipMarkup = true;
                $unitPrice = $totalPrice / max(1, $quantity);
                $lineTotal = $totalPrice;
            } else {
                // Usar preço validado
                $totalPrice = (float) ($validationResult['price'] ?? 0);
                $unitPrice = $totalPrice / max(1, $quantity);
                $lineTotal = $totalPrice;
            }
        } else if ($config) {
            // Preço unitário derivado da configuração (fallback: soma de increments)
            $unitPrice = ProductConfig::computePrice($config, array_merge($options, ['quantity' => $quantity]), $product->price) / max(1, $quantity);
            $lineTotal = $unitPrice * max(1, $quantity);
        } else {
            $basePrice = $product->price;
            $additionalPrice = 0;

            if ($request->input('capa_tipo') === 'capa_dura') {
                $additionalPrice += 15;
            }

            $paginas = $request->input('paginas');
            if ($paginas === '100') {
                $additionalPrice += 10;
            } elseif ($paginas === '200') {
                $additionalPrice += 20;
            } elseif ($paginas === '300') {
                $additionalPrice += 30;
            }

            $papelTipo = $request->input('papel_tipo');
            if ($papelTipo === 'offset_90') {
                $additionalPrice += 5;
            } elseif ($papelTipo === 'couche_120') {
                $additionalPrice += 10;
            }

            $unitPrice = $basePrice + $additionalPrice;
            $lineTotal = $unitPrice * max(1, $quantity);
        }

        $labeledOptions = $options;
        if ($config) {
            foreach (($config['options'] ?? []) as $opt) {
                $name = $opt['name'] ?? null;
                if (!$name || !array_key_exists($name, $labeledOptions)) {
                    continue;
                }
                $selected = $labeledOptions[$name];
                $label = null;
                foreach (($opt['choices'] ?? []) as $choice) {
                    if (($choice['value'] ?? null) == $selected) {
                        $label = $choice['label'] ?? $selected;
                        break;
                    }
                }
                if ($label !== null) {
                    $labeledOptions[$name] = $label;
                }
            }
        }

        if (($lineTotal ?? 0) <= 0) {
            $postedTotal = (float) ($request->input('price') ?? 0);
            if ($postedTotal > 0) {
                $lineTotal = $postedTotal;
                $unitPrice = $postedTotal / max(1, $quantity);
                $skipMarkup = true;
            }
        }

        $unitPrice = $unitPrice ?? 0;
        $lineTotal = $lineTotal ?? ($unitPrice * max(1, $quantity));
        $markupFactor = Pricing::multiplierFor($product, true);
        if ($markupFactor !== 1.0 && !$skipMarkup) {
            $unitPrice *= $markupFactor;
            $lineTotal *= $markupFactor;
        }

        // Create a unique ID for the cart item to handle cases where the same product is added with different options
        $cartItemId = $product->id . '_' . md5(implode('_', array_filter($labeledOptions)));

        $cart[$cartItemId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $unitPrice,
            'line_total' => $lineTotal,
            'image' => $product->image,
            'quantity' => $quantity,
            'options' => $labeledOptions,
            'shipping_meta' => $shippingMeta,
            // Para produtos dirigidos por JSON do site, consideramos preco final (sem markup global)
            'includes_markup' => (bool) $config,
            'type' => $config ? 'json_product' : null,
        ];

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produto adicionado ao carrinho!',
                'cart_item_id' => $cartItemId,
                'redirect_url' => route('cart.index'),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Produto adicionado ao carrinho!');
    }

    public function updateShipping(Request $request)
    {
        $data = $request->validate([
            'postal_code' => ['required', 'string', 'min:8', 'max:12'],
            'service' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:120'],
            'number' => ['nullable', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:2'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $postalCode = preg_replace('/[^0-9]/', '', $data['postal_code']);

        $shippingData = [
            'postal_code' => $postalCode,
            'service' => $data['service'],
            'carrier' => 'Correios',
            'street' => $data['street'],
            'number' => $data['number'] ?? null,
            'complement' => $data['complement'] ?? null,
            'district' => $data['district'] ?? null,
            'city' => $data['city'],
            'state' => strtoupper($data['state']),
            'price' => isset($data['price']) ? (float) $data['price'] : 0.0,
            'status' => ($data['price'] ?? null) ? 'quoted' : 'pending',
        ];

        session()->put('cart_shipping', $shippingData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'shipping' => $shippingData,
            ]);
        }

        $redirectTo = $data['redirect_to'] ?? $request->input('redirect_to');
        if ($redirectTo && Str::startsWith($redirectTo, url('/'))) {
            return redirect($redirectTo)->with('info', 'Opção de frete atualizada! Integração automática com os Correios será aplicada em breve.');
        }

        return redirect()
            ->route('cart.index')
            ->with('info', 'Opção de frete atualizada! Integração automática com os Correios será aplicada em breve.');
    }

    public function attachArtwork(Request $request, string $cartItemId)
    {
        $cart = session()->get('cart', []);

        if (!isset($cart[$cartItemId])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                'message' => 'Item do carrinho nao encontrado.',
                ], 404);
            }
            return redirect()->route('cart.index')->with('error', 'Item do carrinho nao encontrado.');
        }

        $productId = $cart[$cartItemId]['product_id'] ?? null;
        $product = $productId ? Product::find($productId) : null;

        $request->validate([
            'file_front' => 'required|file|mimes:pdf|max:51200',
            'file_back' => 'nullable|file|mimes:pdf|max:51200',
            'margin_confirmation' => 'required',
        ], [
            'file_front.required' => 'O arquivo da frente e obrigatorio.',
            'file_front.mimes' => 'Envie o arquivo da frente em formato PDF.',
            'file_back.mimes' => 'O arquivo do verso deve estar em formato PDF.',
            'margin_confirmation.required' => 'Voce deve confirmar que verificou as margens de seguranca.',
        ]);

        $artworkFiles = $cart[$cartItemId]['artwork'] ?? [];
        $fileBase = $cartItemId . '_' . Str::random(6);

        if ($request->hasFile('file_front')) {
            $file = $request->file('file_front');
            $fileName = "{$fileBase}_frente.pdf";
            $path = $file->storeAs('public/uploads', $fileName);
            $artworkFiles['front'] = Str::after($path, 'public/');
        }

        if ($product && !empty($product->is_duplex) && $request->hasFile('file_back')) {
            $file = $request->file('file_back');
            $fileName = "{$fileBase}_verso.pdf";
            $path = $file->storeAs('public/uploads', $fileName);
            $artworkFiles['back'] = Str::after($path, 'public/');
        }

        $cart[$cartItemId]['artwork'] = $artworkFiles;
        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Arte enviada com sucesso!',
                'redirect_url' => route('cart.index'),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Arte enviada com sucesso!');
    }

    public function remove($cartItemId)
    {
        $cart = session()->get('cart', []);
        unset($cart[$cartItemId]);
        session()->put('cart', $cart);
        return redirect()->back()->with('success', 'Produto removido do carrinho!');
    }

    public function addFlyer(Request $request)
    {
        $flyerProduct = Product::where('template', Product::TEMPLATE_FLYER)->first();
        if ($response = $this->ensurePurchasingEnabled($flyerProduct)) {
            return $response;
        }

        $cart = session()->get('cart', []);

        // Coleta das escolhas: prioriza JSON "details" (com textos), senão usa options[...]
        $detailsRaw = $request->input('details');
        $opts = $request->input('options', []);
        $chosen = [
            'quantity' => $opts['quantity'] ?? null,
            'size' => $opts['size'] ?? null,
            'paper' => $opts['paper'] ?? null,
            'color' => $opts['color'] ?? null,
            'finishing' => $opts['finishing'] ?? null,
            'file_format' => $opts['file_format'] ?? null,
            'file_check' => $opts['file_check'] ?? null,
        ];
        $chosenText = [];
        if ($detailsRaw) {
            $decoded = json_decode($detailsRaw, true);
            if (is_array($decoded)) {
                foreach (['quantity','size','paper','color','finishing','file_format','file_check'] as $k) {
                    if (isset($decoded[$k]) && $decoded[$k] !== '') {
                        $chosenText[$k] = (string) $decoded[$k];
                    }
                }
            }
        }

        // Carrega tabela de preços
        $pricesPath = base_path('precos_flyer.json');
        if (!is_file($pricesPath)) {
            return redirect()->back()->with('error', 'Tabela de preços de flyer não encontrada.');
        }
        $table = json_decode(file_get_contents($pricesPath), true) ?: [];

        // Normalizadores para casar chaves do JSON com o que veio do form (mantemos os labels originais na UI)
        $norm = function ($s) {
            $s = trim((string) $s);
            return mb_strtolower(preg_replace('/\s+/', ' ', $s));
        };
        $pickKey = function(array $options, ?string $wanted) use ($norm) {
            if ($wanted === null || $wanted === '') return null;
            $wantedN = $norm($wanted);
            foreach ($options as $k) {
                if ($norm($k) === $wantedN) return $k;
            }
            foreach ($options as $k) {
                if (str_contains($norm($k), $wantedN) || str_contains($wantedN, $norm($k))) return $k;
            }
            return null;
        };

        $qtyKey = $pickKey(array_keys($table), (string)($chosenText['quantity'] ?? $chosen['quantity'] ?? ''));
        if (!$qtyKey) {
            return redirect()->back()->with('error', 'Quantidade inválida para o flyer.');
        }
        $sizes = $table[$qtyKey] ?? [];
        $sizeKey = $pickKey(array_keys($sizes), (string)($chosenText['size'] ?? $chosen['size'] ?? ''));
        if (!$sizeKey) {
            return redirect()->back()->with('error', 'Formato inválido para o flyer.');
        }
        $papers = $sizes[$sizeKey] ?? [];
        $paperKey = $pickKey(array_keys($papers), (string)($chosenText['paper'] ?? $chosen['paper'] ?? ''));
        if (!$paperKey) {
            return redirect()->back()->with('error', 'Tipo de papel inválido para o flyer.');
        }
        $colors = $papers[$paperKey] ?? [];
        $colorKey = $pickKey(array_keys($colors), (string)($chosenText['color'] ?? $chosen['color'] ?? ''));
        if (!$colorKey || !isset($colors[$colorKey])) {
            return redirect()->back()->with('error', 'Configuração de cores inválida para o flyer.');
        }

        $baseLotPrice = (float) $colors[$colorKey];

        // Custos adicionais (valores vêm nos value dos selects; labels via details JSON)
        $extraFinishing = (float) ($opts['finishing'] ?? 0);
        $extraFileFormat = (float) ($opts['file_format'] ?? 0);
        $extraFileCheck = (float) ($opts['file_check'] ?? 0);
        $extras = $extraFinishing + $extraFileFormat + $extraFileCheck;

        $flyerPrice = $baseLotPrice + $extras; // preço do lote + extras

        // Não aplicar acréscimo global: preço final vem do site + extras

        $details = [
            'quantity' => (int) ($chosenText['quantity'] ?? $chosen['quantity'] ?? 1),
            'size' => $sizeKey,
            'paper' => $paperKey,
            'color' => $colorKey,
            'finishing' => $chosenText['finishing'] ?? null,
            'file_format' => $chosenText['file_format'] ?? null,
            'file_check' => $chosenText['file_check'] ?? null,
        ];

        $flyerId = 'flyer_' . md5(implode('|', [
            $qtyKey,
            $sizeKey,
            $paperKey,
            $colorKey,
            $details['finishing'] ?? '',
            $details['file_format'] ?? '',
            $details['file_check'] ?? '',
        ]));

        $cart[$flyerId] = [
            'product_id' => $flyerProduct?->id,
            'name' => 'Flyer Personalizado',
            'price' => $flyerPrice,
            'quantity' => 1, // 1 lote
            'options' => $details,
            'image' => null,
            'type' => 'flyer',
            'includes_markup' => true,
            'artwork' => $cart[$flyerId]['artwork'] ?? [],
        ];

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Flyer adicionado ao carrinho!',
                'cart_item_id' => $flyerId,
                'redirect_url' => route('cart.index'),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Flyer adicionado ao carrinho!');
    }

    /**
     * Impede operações de compra caso o modo orçamento esteja ativo.
     */
    protected function ensurePurchasingEnabled(?Product $product = null): RedirectResponse|null
    {
        if (Setting::boolean('request_only', false)) {
            return redirect()->route('home')->with('info', 'As compras estão temporariamente desativadas. Solicite um orçamento com nossa equipe.');
        }

        if ($product && $product->request_only) {
            return redirect()
                ->route('product.show', $product)
                ->with('info', 'Este produto está disponível apenas mediante orçamento. Solicite uma proposta com nossa equipe.');
        }

        return null;
    }
}
