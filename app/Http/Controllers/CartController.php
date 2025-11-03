<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductConfig;
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
            $total += $item['price'] * $item['quantity'];
        }
        return view('cart', compact('cart', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        if ($response = $this->ensurePurchasingEnabled($product)) {
            return $response;
        }

        $cart = session()->get('cart', []);

        // Get selected options
        $options = $request->only(['capa_tipo', 'paginas', 'papel_tipo', 'uploaded_file_path']);
        $quantity = $request->input('quantity', 1);

        // Calcula preço: se houver config JSON do produto, usa as regras do JSON (preço final + extras)
        $configSlug = null;
        if ($product->usesConfigTemplate() || $product->template === Product::TEMPLATE_CONFIG_AUTO) {
            $configSlug = $product->templateSlug();
            if (!$configSlug || $product->template === Product::TEMPLATE_CONFIG_AUTO) {
                $configSlug = ProductConfig::slugForProduct($product);
            }
        }
        $config = ProductConfig::loadForProduct($product, $configSlug);
        if ($config) {
            // Converter valores selecionados para labels conforme o catálogo (para exibição fiel)
            $labeledOptions = $options;
            foreach (($config['options'] ?? []) as $opt) {
                $name = $opt['name'] ?? null;
                if (!$name || !array_key_exists($name, $labeledOptions)) { continue; }
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

            // Preço unitário derivado da configuração (fallback: soma de increments)
            $totalPrice = ProductConfig::computePrice($config, array_merge($options, ['quantity' => $quantity]), $product->price) / max(1, $quantity);

            $options = $labeledOptions; // guarda labels para o carrinho
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

            $totalPrice = $basePrice + $additionalPrice;
        }

        // Create a unique ID for the cart item to handle cases where the same product is added with different options
        $cartItemId = $product->id . '_' . md5(implode('_', array_filter($options)));

        $cart[$cartItemId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $totalPrice,
            'image' => $product->image,
            'quantity' => $quantity,
            'options' => $options,
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

        $flyerId = 'flyer_' . md5(implode('|', [$qtyKey, $sizeKey, $paperKey, $colorKey, $details['finishing'] ?? '', $details['file_format'] ?? '', $details['file_check'] ?? '']));

        $cart[$flyerId] = [
            'name' => 'Flyer Personalizado',
            'price' => $flyerPrice,
            'quantity' => 1, // 1 lote
            'options' => $details,
            'image' => null,
            'type' => 'flyer',
            'includes_markup' => true,
        ];

        session()->put('cart', $cart);

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

