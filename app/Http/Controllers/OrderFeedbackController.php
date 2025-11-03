<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFeedback;
use Illuminate\Http\Request;

class OrderFeedbackController extends Controller
{
    public function create(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if ($order->feedback) {
            return redirect()->route('customer.orders.show', $order)->with('info', 'Você já enviou uma avaliação para este pedido.');
        }

        return view('customer.feedback.create', [
            'order' => $order,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if ($order->feedback) {
            return redirect()->route('customer.orders.show', $order)->with('info', 'Você já enviou uma avaliação para este pedido.');
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'headline' => 'nullable|string|max:150',
            'comment' => 'nullable|string|max:2000',
        ]);

        $feedback = new OrderFeedback($data);
        $feedback->order_id = $order->id;
        $feedback->user_id = $request->user()->id;
        $feedback->submitted_at = now();
        $feedback->save();

        return redirect()->route('customer.orders.show', $order)->with('success', 'Obrigado! Sua avaliação foi enviada.');
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
