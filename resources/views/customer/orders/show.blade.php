@extends('layouts.app')

@section('title', 'Pedido #' . $order->id . ' - Minha Conta')

@section('body-class', 'customer-order-detail')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Pedido #{{ $order->id }}</h1>
            <small class="text-muted">Realizado em {{ $order->created_at->format('d/m/Y H:i') }}</small>
        </div>
        <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary">Voltar para minha conta</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Itens do pedido</h5>
                </div>
                <div class="card-body">
                    @forelse(($order->items['items'] ?? []) as $item)
                        <div class="d-flex justify-content-between align-items-start py-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $item['name'] ?? 'Produto' }}</h6>
                                @if(!empty($item['options']))
                                    <ul class="list-unstyled small text-muted mb-0">
                                        @foreach($item['options'] as $key => $value)
                                            <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">R$ {{ number_format($item['unit_price'] ?? ($item['price'] ?? 0), 2, ',', '.') }}</div>
                                <small class="text-muted">Qtd: {{ $item['quantity'] ?? 1 }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Nenhum item detalhado.</p>
                    @endforelse
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <strong>Total</strong>
                    <strong>R$ {{ number_format($order->total, 2, ',', '.') }}</strong>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informações de entrega & pagamento</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Endereço</h6>
                            <p class="mb-0">{{ $order->shipping_address ?? 'Não informado' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Método de pagamento</h6>
                            <p class="mb-0 text-capitalize">{{ str_replace('_', ' ', $order->payment_method ?? 'não informado') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Status do pedido</h5>
                    <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">
                        {{ ucfirst(__($order->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">Acompanhe o andamento do seu pedido. Notificaremos qualquer atualização por e-mail.</p>
                </div>
            </div>

            @if($order->status === 'completed')
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Satisfação</h5>
                    </div>
                    <div class="card-body">
                        @if($order->feedback)
                            <p class="text-muted mb-2">Obrigado! Sua avaliação:</p>
                            <div class="mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $order->feedback->rating >= $i ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </div>
                            @if($order->feedback->headline)
                                <h6>{{ $order->feedback->headline }}</h6>
                            @endif
                            <p class="mb-0">{{ $order->feedback->comment }}</p>
                        @else
                            <p class="mb-3">Como foi a experiência com este pedido?</p>
                            <a href="{{ route('customer.feedback.create', $order) }}" class="btn btn-primary w-100">Avaliar pedido</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
