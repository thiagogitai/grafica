<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\EvolutionWhatsapp;
use App\Services\Pricing;

class CheckoutController extends Controller
{
    /**
     * Exibe a página de checkout para o carrinho.
     */
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
        ];
        $shipping = array_merge($shippingDefaults, session()->get('cart_shipping', []));
        $shippingAddress = $this->formatShippingAddress($shipping);

        $user = Auth::user();
        return view('checkout', compact('cart', 'total', 'user', 'shipping', 'shippingAddress'));
    }

    /**
     * Exibe uma página de checkout personalizada para um único produto.
     */
    public function productCheckout(Product $product)
    {
        if ($response = $this->ensurePurchasingEnabled()) {
            return $response;
        }

        $cart = [
            $product->id => [
                "name" => $product->name,
                "quantity" => 1,
                "price" => $product->price,
                "image" => $product->image
            ]
        ];

        // Para outros produtos, exibe a página de checkout padrão passando o produto
        return view('checkout', ['product' => $product, 'cart' => $cart]);
    }

    /**
     * Processa o checkout.
     */
    public function process(Request $request)
    {
        if ($response = $this->ensurePurchasingEnabled()) {
            return $response;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|string|in:pix,credit_card,bank_transfer',
            'postal_code' => 'required|string|min:8|max:12',
            'street' => 'required|string|max:120',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:80',
            'district' => 'nullable|string|max:80',
            'city' => 'required|string|max:80',
            'state' => 'required|string|max:2',
            'service' => 'required|string|max:50',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Seu carrinho está vazio.');
        }

        $shippingData = [
            'postal_code' => preg_replace('/[^0-9]/', '', $validated['postal_code']),
            'street' => $validated['street'],
            'number' => $validated['number'] ?? null,
            'complement' => $validated['complement'] ?? null,
            'district' => $validated['district'] ?? null,
            'city' => $validated['city'],
            'state' => strtoupper($validated['state']),
            'service' => $validated['service'],
        ];
        $shippingAddress = $this->formatShippingAddress($shippingData);
        if (!$shippingAddress) {
            $shippingAddress = trim($shippingData['street'] . ' ' . ($shippingData['number'] ?? ''));
        }

        // Calcula total a partir do preço salvo no carrinho, aplicando percentual de acréscimo
        $total = 0;
        $productIds = collect($cart)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $products = $productIds->isEmpty()
            ? collect()
            : Product::whereIn('id', $productIds)->get()->keyBy('id');

        $globalMarkup = Pricing::globalPercentage();
        $orderItems = [];
        foreach ($cart as $cartKey => $item) {
            $unitPrice = (float) ($item['price'] ?? 0);
            $includesMarkup = (bool)($item['includes_markup'] ?? false);
            if (!$includesMarkup) {
                $productMarkup = 1 + ($globalMarkup / 100);

                if (!empty($item['product_id']) && $products->has($item['product_id'])) {
                    $product = $products->get($item['product_id']);
                    $productMarkup = Pricing::multiplierFor($product);
                }

                $unitPrice *= $productMarkup;
            }
            $lineTotal = $unitPrice * (int) ($item['quantity'] ?? 1);
            $total += $lineTotal;

            $orderItems[] = [
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => $unitPrice,
                'options' => $item['options'] ?? [],
                'artwork' => $item['artwork'] ?? [],
                'type' => $item['type'] ?? null,
            ];
        }

        $shippingSession = session()->get('cart_shipping', []);
        $shippingCost = (float) ($shippingSession['price'] ?? 0);
        $total += $shippingCost;

        // Cria o pedido
        $order = Order::create([
            'user_id' => Auth::id(),
            'total' => $total,
            'status' => 'pending',
            'shipping_address' => $shippingAddress,
            'payment_method' => $request->payment_method,
            // Guardamos apenas as linhas do pedido no JSON
            'items' => [
                'items' => $orderItems,
                'shipping' => array_merge($shippingData, [
                    'price' => $shippingCost,
                ]),
            ],
        ]);

        // Mantém $cart disponível para mensagem e limpa sessão em seguida
        session()->forget('cart');

        // Integração com WhatsApp
        $whatsappNumber = '11981818180';
        $message = "Olá! Meu nome é " . $request->name . " e acabei de fazer um pedido (#" . $order->id . ") no valor total de R$" . number_format($total, 2, ',', '.') . ".\n\n";
        $message .= "Detalhes do pedido:\n";
        foreach ($orderItems as $item) {
            $message .= "- " . $item['name'] . " (x" . $item['quantity'] . ")\n";
        }
        $message .= "\nEndereço de entrega:\n" . $shippingAddress;
        $message .= "\nMétodo de pagamento: " . $request->payment_method;
        $message .= "\n\nPor favor, entre em contato para finalizar a compra.";

        if ($whatsappNumber) {
            if (EvolutionWhatsapp::enabled()) {
                $normalized = preg_replace('/\D+/', '', (string) $whatsappNumber);
                $ok = EvolutionWhatsapp::sendText($normalized, $message);
                if ($ok) {
                    return redirect()->route('checkout.success')->with('success', 'Pedido realizado e enviado no WhatsApp.');
                }
                // Fallback para link
            }
            $whatsappLink = "https://api.whatsapp.com/send?phone=" . $whatsappNumber . "&text=" . urlencode($message);
            return redirect()->away($whatsappLink);
        }

        return redirect()->route('checkout.success')->with('success', 'Pedido realizado com sucesso!');
    }

    /**
     * Exibe a página de sucesso do checkout.
     */
    public function success()
    {
        return view('checkout.success');
    }

    /**
     * Exibe a página de falha do checkout.
     */
    public function failure()
    {
        return view('checkout.failure');
    }

    /**
     * Exibe a página de checkout pendente.
     */
    public function pending()
    {
        return view('checkout.pending');
    }

    protected function ensurePurchasingEnabled(): RedirectResponse|null
    {
        if (Setting::boolean('request_only', false)) {
            return redirect()->route('home')->with('info', 'As compras estão temporariamente desativadas. Solicite um orçamento com nossa equipe.');
        }

        return null;
    }

    protected function formatShippingAddress(array $shipping): ?string
    {
        $parts = [];
        if (!empty($shipping['street'])) {
            $streetLine = $shipping['street'];
            if (!empty($shipping['number'])) {
                $streetLine .= ', ' . $shipping['number'];
            }
            if (!empty($shipping['complement'])) {
                $streetLine .= ' - ' . $shipping['complement'];
            }
            $parts[] = $streetLine;
        }
        if (!empty($shipping['district'])) {
            $parts[] = $shipping['district'];
        }
        $cityState = [];
        if (!empty($shipping['city'])) {
            $cityState[] = $shipping['city'];
        }
        if (!empty($shipping['state'])) {
            $cityState[] = strtoupper($shipping['state']);
        }
        if ($cityState) {
            $parts[] = implode(' / ', $cityState);
        }
        if (!empty($shipping['postal_code'])) {
            $parts[] = 'CEP: ' . $shipping['postal_code'];
        }
        if (!empty($shipping['service'])) {
            $parts[] = 'Serviço: ' . $shipping['service'];
        }

        return $parts ? implode("\n", $parts) : null;
    }
}
