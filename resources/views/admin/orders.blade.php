@extends('layouts.admin')

@section('title', 'Pedidos - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Pedidos</span>
@endsection
@section('admin-title', 'Gerenciar pedidos')
@section('admin-subtitle', 'Atualize status, visualize itens e acompanhe o progresso dos pedidos.')

@section('admin-content')
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pedidos recentes</h5>
            <span class="badge bg-light text-dark">{{ $orders->total() }} registros</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->user->name ?? 'Cliente' }}</td>
                            <td>R$ {{ number_format($order->total, 2, ',', '.') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="d-flex align-items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pendente</option>
                                        <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processando</option>
                                        <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Concluído</option>
                                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                    </select>
                                </form>
                            </td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <button class="btn btn-outline-dark btn-sm" onclick="showOrderDetails({{ $order->id }})">
                                    <i class="fas fa-eye me-1"></i>Detalhes
                                </button>
                                <div id="order-details-{{ $order->id }}" class="d-none">
                                    @include('admin.partials.order-details', ['order' => $order])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Nenhum pedido encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $orders->links() }}
        </div>
    </div>

    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function showOrderDetails(orderId) {
    const src = document.getElementById('order-details-' + orderId);
    const target = document.getElementById('orderDetailsContent');
    if (src && target) {
        target.innerHTML = src.innerHTML;
    } else if (target) {
        target.innerHTML = '<p class="text-muted">Detalhes não disponíveis.</p>';
    }
    if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
    } else if (window.$) {
        $('#orderDetailsModal').modal('show');
    }
}
</script>
@endpush
