<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Order;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        if (!$profile) {
            $profile = new CustomerProfile([
                'phone' => $user->phone,
            ]);
        }

        $orders = $user->orders()
            ->with('feedback')
            ->latest()
            ->paginate(10);

        $pendingFeedback = $user->orders()
            ->where('status', 'completed')
            ->doesntHave('feedback')
            ->latest()
            ->take(5)
            ->get();

        return view('customer.dashboard', [
            'user' => $user,
            'profile' => $profile,
            'orders' => $orders,
            'pendingFeedback' => $pendingFeedback,
        ]);
    }

    public function showOrder(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $order->load('feedback');

        return view('customer.orders.show', [
            'order' => $order,
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:32',
            'phone' => 'nullable|string|max:20',
            'default_address' => 'nullable|string|max:255',
            'preferences' => 'nullable|array',
        ]);

        $user = $request->user();
        $profile = $user->profile ?: new CustomerProfile(['user_id' => $user->id]);
        $profile->fill($data);
        $profile->save();

        if (!empty($data['phone'])) {
            $user->phone = $data['phone'];
            $user->save();
        }

        return redirect()->route('customer.dashboard')->with('success', 'Perfil atualizado com sucesso!');
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
