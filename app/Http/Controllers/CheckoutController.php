<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\EvolutionWhatsapp;

class CheckoutController extends Controller
{
    /**
     * Exibe a página de checkout para o carrinho.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        $user = Auth::user();
        return view('checkout', compact('cart', 'total', 'user'));
    }

    /**
     * Exibe uma página de checkout personalizada para um único produto.
     */
    public function productCheckout(Product $product)
    {
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'payment_method' => 'required|string|in:pix,credit_card,bank_transfer',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Seu carrinho está vazio.');
        }

        // Calcula total a partir do preço salvo no carrinho, aplicando percentual de acréscimo
        $total = 0;
        $price_percentage = (float) Setting::get('price_percentage', 0);
        $orderItems = [];
        foreach ($cart as $cartKey => $item) {
            $unitPrice = (float) ($item['price'] ?? 0);
            $includesMarkup = (bool)($item['includes_markup'] ?? false);
            if ($price_percentage > 0 && !$includesMarkup) {
                $unitPrice = $unitPrice * (1 + ($price_percentage / 100));
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

        // Cria o pedido
        $order = Order::create([
            'user_id' => Auth::id(),
            'total' => $total,
            'status' => 'pending',
            'shipping_address' => $request->address,
            'payment_method' => $request->payment_method,
            // Guardamos apenas as linhas do pedido no JSON
            'items' => [
                'items' => $orderItems,
            ],
        ]);

        // Mantém $cart disponível para mensagem e limpa sessão em seguida
        session()->forget('cart');

        // Integração com WhatsApp
        $whatsappNumber = Setting::get('whatsapp_number');
        $message = "Olá! Meu nome é " . $request->name . " e acabei de fazer um pedido (#" . $order->id . ") no valor total de R$" . number_format($total, 2, ',', '.') . ".\n\n";
        $message .= "Detalhes do pedido:\n";
        foreach ($orderItems as $item) {
            $message .= "- " . $item['name'] . " (x" . $item['quantity'] . ")\n";
        }
        $message .= "\nEndereço de entrega: " . $request->address;
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
}
