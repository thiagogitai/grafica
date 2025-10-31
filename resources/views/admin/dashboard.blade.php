@extends('layouts.admin')

@section('title', 'Painel Admin - Gráfica')

@section('content')
<h1>Painel Administrativo</h1>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Pedidos Recentes</h5>
            </div>
            <div class="card-body">
                @forelse($orders->take(5) as $order)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>Pedido #{{ $order->id }}</strong><br>
                            <small>{{ $order->user->name }} - {{ $order->created_at->format('d/m/Y') }}</small>
                        </div>
                        <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">
                            @switch($order->status)
                                @case('pending')
                                    Pendente
                                    @break
                                @case('processing')
                                    Processando
                                    @break
                                @case('completed')
                                    Concluído
                                    @break
                                @case('cancelled')
                                    Cancelado
                                    @break
                                @default
                                    {{ ucfirst($order->status) }}
                            @endswitch
                        </span>
                    </div>
                @empty
                    <p>Nenhum pedido encontrado.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Produtos</h5>
            </div>
            <div class="card-body">
                <p>Total de produtos: {{ $products->count() }}</p>
                <a href="{{ route('admin.products') }}" class="btn btn-primary">Gerenciar Produtos</a>
            </div>
        </div>
    </div>
</div>
@endsection
