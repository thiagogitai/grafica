@extends('layouts.admin')

@section('admin-title', 'Visão Geral')
@section('admin-subtitle', 'Monitore o desempenho da gráfica, pedidos recentes e produtos em destaque.')

@php
    $totalOrders = $orders->count();
    $totalRevenue = $orders->sum('total');
    $pendingOrders = $orders->where('status', 'pending')->count();
    $processingOrders = $orders->where('status', 'processing')->count();
    $completedOrders = $orders->where('status', 'completed')->count();
    $cancelledOrders = $orders->where('status', 'cancelled')->count();
    $customersServed = $orders->pluck('user_id')->filter()->unique()->count();

    $statusCounts = $orders->groupBy('status')->map->count();
    $statusChartLabels = $statusCounts->keys()->map(fn($status) => ucfirst(__($status)));
    $statusChartValues = $statusCounts->values();

    $monthlyRevenue = $orders->groupBy(function($order) {
        return $order->created_at->format('Y-m');
    })->map->sum('total')->sortKeys();
    $monthlyLabels = $monthlyRevenue->keys()->map(fn($label) => \Carbon\Carbon::createFromFormat('Y-m', $label)->locale('pt_BR')->translatedFormat('M Y'));
    $monthlyValues = $monthlyRevenue->values()->map(fn($value) => round($value, 2));
@endphp

@section('admin-actions')
    <a href="{{ route('admin.orders') }}" class="btn btn-light text-warning border-0">
        <i class="fas fa-shopping-cart me-2"></i>Ver pedidos
    </a>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary" style="background-color: #D4A017; border-color: #D4A017;">
        <i class="fas fa-plus me-2"></i>Novo produto
    </a>
@endsection

@section('admin-content')
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Receita total</p>
                            <h3 class="mb-0">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</h3>
                        </div>
                        <span class="badge" style="background-color: rgba(212,160,23,0.18); color: #8c670f; font-size: 1.15rem;">
                            <i class="fas fa-wallet"></i>
                        </span>
                    </div>
                    <p class="mt-3 text-muted small">Pedidos registrados no sistema.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Pedidos pendentes</p>
                            <h3 class="mb-0">{{ $pendingOrders }}</h3>
                        </div>
                        <span class="badge" style="background-color: rgba(228,191,87,0.25); color: #9c7a12; font-size: 1.15rem;">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                    </div>
                    <p class="mt-3 text-muted small">Aguardando pagamento/validação.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Pedidos concluídos</p>
                            <h3 class="mb-0">{{ $completedOrders }}</h3>
                        </div>
                        <span class="badge" style="background-color: rgba(139,195,74,0.18); color: #4c6b11; font-size: 1.15rem;">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                    <p class="mt-3 text-muted small">Pedidos finalizados e entregues.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Clientes atendidos</p>
                            <h3 class="mb-0">{{ $customersServed }}</h3>
                        </div>
                        <span class="badge" style="background-color: rgba(178, 190, 195, 0.2); color: #3f4b53; font-size: 1.15rem;">
                            <i class="fas fa-users"></i>
                        </span>
                    </div>
                    <p class="mt-3 text-muted small">Clientes com pedidos registrados.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Receita por mês</h5>
                    <span class="badge bg-light text-dark">{{ $monthlyLabels->last() ?? 'Sem dados' }}</span>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="200"></canvas>
                    @if($monthlyRevenue->isEmpty())
                        <p class="text-muted text-center mt-3 mb-0">Sem dados de faturamento ainda.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Status dos pedidos</h5>
                    <span class="badge bg-light text-dark">{{ $totalOrders }} no total</span>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                    @if($statusCounts->isEmpty())
                        <p class="text-muted text-center mt-3 mb-0">Nenhum pedido registrado.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Pedidos recentes</h5>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($orders->take(6) as $order)
                        <div class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <strong>#{{ $order->id }}</strong>
                                <div class="text-muted small">{{ $order->user->name ?? 'Cliente' }} • {{ $order->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst(__($order->status)) }}
                                </span>
                                <div class="fw-bold mt-1">R$ {{ number_format($order->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4 mb-0">Nenhum pedido encontrado.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Produtos em destaque</h5>
                    <span class="badge bg-light text-dark">{{ $products->count() }} itens</span>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @foreach($products->take(6) as $product)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    <div class="small text-muted">{{ Str::limit($product->description, 80) }}</div>
                                </div>
                                <span class="badge" style="background-color: rgba(212,160,23,0.15); color:#85640c;">{{ $product->effectiveTemplateLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('admin.products') }}" class="btn btn-outline-primary w-100 mt-3">
                        Gerenciar todos os produtos
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const revenueCtx = document.getElementById('revenueChart');
    const statusCtx = document.getElementById('statusChart');

    const revenueData = {
        labels: {!! $monthlyLabels->toJson() !!},
        datasets: [{
            label: 'Faturamento (R$)',
            data: {!! $monthlyValues->toJson() !!},
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            borderColor: 'rgba(212, 160, 23, 1)',
            backgroundColor: 'rgba(212, 160, 23, 0.18)',
            pointBackgroundColor: 'rgba(212, 160, 23, 1)',
        }]
    };

    const statusData = {
        labels: {!! $statusChartLabels->toJson() !!},
        datasets: [{
            data: {!! $statusChartValues->toJson() !!},
            backgroundColor: ['#D4A017', '#F4D687', '#9c882d', '#b58f3a', '#e1a124'],
            borderWidth: 0,
        }]
    };

    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: revenueData,
            options: {
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        ticks: {
                            callback(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        },
                        grid: { color: 'rgba(15,23,42,0.08)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: statusData,
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '65%'
            }
        });
    }
</script>
@endpush
