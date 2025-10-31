@extends('layouts.admin')

@section('title', 'Gerenciar Pedidos - Gráfica')

@section('content')
<h1>Gerenciar Pedidos</h1>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Status</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->user->name }}</td>
                    <td>R$ {{ number_format($order->total, 2, ',', '.') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pendente</option>
                                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processando</option>
                                <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Concluído</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </form>
                    </td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="showOrderDetails({{ $order->id }})">Detalhes</button>
                        <div id="order-details-{{ $order->id }}" class="d-none">
                            @php
                                $data = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
                                $itens = $data['items'] ?? [];
                                $endereco = $order->shipping_address ?? ($data['shipping_address'] ?? null);
                                $pagamento = $order->payment_method ?? ($data['payment_method'] ?? null);
                            @endphp
                            <h6>Itens</h6>
                            @if(!empty($itens))
                                <ul class="list-group mb-3">
                                    @foreach($itens as $it)
                                        <li class="list-group-item">
                                            @php
                                                $qtyItem = (int)($it['quantity'] ?? 1);
                                                $unit = (float)($it['unit_price'] ?? 0);
                                                $lineTotal = $unit * $qtyItem;
                                                $opts = $it['options'] ?? [];

                                                $baseCalc = null; $extrasCalc = null;
                                                $pricesPath = base_path('precos_flyer.json');
                                                if (isset($opts['quantity'],$opts['size'],$opts['paper'],$opts['color']) && is_file($pricesPath)) {
                                                    $tbl = json_decode(file_get_contents($pricesPath), true) ?: [];
                                                    $q = $opts['quantity']; $s = $opts['size']; $p = $opts['paper']; $c = $opts['color'];
                                                    if (isset($tbl[$q][$s][$p][$c])) {
                                                        $baseRaw = (float)$tbl[$q][$s][$p][$c];
                                                        $baseCalc = $baseRaw; // usar preço final do site (sem acréscimo)
                                                        $extrasCalc = max(0, $lineTotal - $baseCalc);
                                                    }
                                                }
                                            @endphp

                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold">{{ $it['name'] ?? 'Item' }}</div>
                                                    <small class="text-muted">Qtd: {{ $qtyItem }}</small>
                                                </div>
                                                <div>R$ {{ number_format($lineTotal, 2, ',', '.') }}</div>
                                            </div>
                                            @php $opts = $it['options'] ?? []; @endphp
                                            @if(!empty($opts))
                                                @php
                                                    $labelMap = [
                                                        'size' => 'Formato',
                                                        'paper' => 'Papel',
                                                        'color' => 'Cores',
                                                        'finishing' => 'Acabamento',
                                                        'file_format' => 'Formato do arquivo',
                                                        'file_check' => 'Verificação do arquivo',
                                                    ];
                                                @endphp
                                                <ul class="list-unstyled small text-muted mb-0 mt-2">
                                                    @foreach($opts as $key => $value)
                                                        @if($value && $key != 'quantity')
                                                            @php $label = $labelMap[$key] ?? ucfirst(str_replace('_',' ', $key)); @endphp
                                                            <li><strong>{{ $label }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @endif

                                            @if(!is_null($baseCalc))
                                                <div class="mt-2 small">
                                                    <div class="d-flex justify-content-between"><span class="text-muted">Subtotal (base):</span><span>R$ {{ number_format($baseCalc, 2, ',', '.') }}</span></div>
                                                    <div class="d-flex justify-content-between"><span class="text-muted">Adicionais:</span><span>R$ {{ number_format($extrasCalc, 2, ',', '.') }}</span></div>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">Sem itens registrados.</p>
                            @endif
                            <h6>Informações</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Endereço</dt>
                                <dd class="col-sm-8">{{ $endereco ?? '-' }}</dd>
                                <dt class="col-sm-4">Pagamento</dt>
                                <dd class="col-sm-8">{{ $pagamento ?? '-' }}</dd>
                            </dl>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Nenhum pedido encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}

<!-- Modal para detalhes do pedido -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>
</div>

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
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        modal.show();
    } else if (window.$) {
        $('#orderDetailsModal').modal('show');
    }
}
</script>
@endpush
@endsection
