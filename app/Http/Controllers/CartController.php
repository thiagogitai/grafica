<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ProductConfig;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return view('cart', compact('cart', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        $cart = session()->get('cart', []);

        // Get selected options
        $options = $request->only(['capa_tipo', 'paginas', 'papel_tipo', 'uploaded_file_path']);
        $quantity = $request->input('quantity', 1);

        // Calcula preço: se houver config JSON do produto, usa as regras do JSON (preço final + extras)
        $config = ProductConfig::loadForProduct($product);
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
            // Para produtos dirigidos por JSON do site, consideramos preço final (sem markup global)
            'includes_markup' => (bool) $config,
            'type' => $config ? 'json_product' : null,
        ];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Produto adicionado ao carrinho!');
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
}
