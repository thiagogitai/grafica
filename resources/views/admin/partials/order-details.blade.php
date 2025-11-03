@php
    $data = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
    $items = $data['items'] ?? [];
    $address = $order->shipping_address ?? ($data['shipping_address'] ?? null);
    $payment = $order->payment_method ?? ($data['payment_method'] ?? null);
@endphp

@if(!empty($items))
    <h6 class="fw-semibold">Itens</h6>
    <ul class="list-group mb-3">
        @foreach($items as $item)
            <li class="list-group-item">
                @php
                    $quantity = (int)($item['quantity'] ?? 1);
                    $unitPrice = (float)($item['unit_price'] ?? ($item['price'] ?? 0));
                    $lineTotal = $unitPrice * $quantity;
                    $options = $item['options'] ?? [];
                @endphp
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">{{ $item['name'] ?? 'Item' }}</div>
                        <small class="text-muted">Qtd: {{ $quantity }}</small>
                    </div>
                    <div>R$ {{ number_format($lineTotal, 2, ',', '.') }}</div>
                </div>
                @if(!empty($options))
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
                        @foreach($options as $key => $value)
                            @if($value && $key !== 'quantity')
                                <li><strong>{{ $labelMap[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted">Nenhum item registrado.</p>
@endif

<h6 class="fw-semibold">Informações adicionais</h6>
<dl class="row mb-0 small">
    <dt class="col-sm-4">Endereço</dt>
    <dd class="col-sm-8">{{ $address ?? '-' }}</dd>
    <dt class="col-sm-4">Pagamento</dt>
    <dd class="col-sm-8 text-capitalize">{{ $payment ? str_replace('_', ' ', $payment) : '-' }}</dd>
</dl>
