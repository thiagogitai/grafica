@extends('layouts.app')

@section('body-class', 'cart-page')

@section('title', 'Carrinho - Gráfica')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Carrinho de Compras</h1>
        </div>

        @if(empty($cart))
            <div class="text-center">
                <div class="alert alert-info mb-4">Seu carrinho está vazio.</div>
                <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Continuar Comprando</a>
            </div>
        @else
            @php
                $summaryUploads = [];
                foreach ($cart as $cartItemId => $cartItem) {
                    if (!empty($cartItem['artwork']) && is_array($cartItem['artwork'])) {
                        foreach ($cartItem['artwork'] as $type => $path) {
                            $summaryUploads[] = [
                                'product' => $cartItem['name'] ?? 'Produto',
                                'type' => strtoupper($type),
                                'path' => $path,
                            ];
                        }
                    }
                }
            @endphp
            <div class="row">
                <div class="col-lg-8">
                    @foreach($cart as $id => $item)
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        @if(isset($item['image']) && $item['image'])
                                            <img src="{{ asset('storage/' . $item['image']) }}" class="img-fluid rounded" alt="{{ $item['name'] }}">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 100px;">
                                                <i class="fas fa-image text-muted fa-2x"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-5">
                                        <h5 class="card-title">{{ $item['name'] }}</h5>
                                        
                                        {{-- Exibe as opções de personalização --}}
                                        @if(!empty($item['options']))
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
                                            <ul class="list-unstyled small text-muted mb-2">
                                                @foreach($item['options'] as $key => $value)
                                                    @if($value && $key != 'quantity') {{-- A quantidade já é exibida separadamente --}}
                                                        @php
                                                            $label = $labelMap[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                                        @endphp
                                                        <li>
                                                            <strong>{{ $label }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif

                                        {{-- Exibe os links dos arquivos de arte --}}
                                        @if(!empty($item['artwork']))
                                            <div class="mt-2">
                                                <strong class="small text-muted">Arquivos:</strong>
                                                <ul class="list-unstyled small">
                                                    @foreach($item['artwork'] as $type => $path)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="btn btn-link p-0 text-decoration-none"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#pdfPreviewModal"
                                                                data-pdf="{{ asset('storage/' . $path) }}"
                                                                data-title="{{ $item['name'] }} • {{ strtoupper($type) }}"
                                                            >
                                                                <i class="fas fa-file-pdf me-1 text-danger"></i>
                                                                Ver arquivo ({{ $type }})
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @php
                                            $itemTotal = $item['line_total'] ?? (($item['price'] ?? 0) * ($item['quantity'] ?? 1));
                                        @endphp
                                        <p class="card-text text-muted fw-bold">R$ {{ number_format($itemTotal, 2, ',', '.') }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-0"><strong>Quantidade:</strong> {{ $item['quantity'] }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <form method="POST" action="{{ route('cart.remove', $id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-dark btn-sm w-100">Remover</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-top">
                                    @php
                                        $shippingMeta = $item['shipping_meta'] ?? null;
                                        $weightLabel = null;
                                        $perUnitWeightLabel = null;
                                        $dimensionsLabel = null;
                                        $packagesCount = null;
                                        if (!empty($shippingMeta) && is_array($shippingMeta)) {
                                            $weightLabel = $shippingMeta['formatted_weight']
                                                ?? (isset($shippingMeta['weight']) ? number_format((float) $shippingMeta['weight'], 3, ',', '.') . ' kg' : null);
                                            $perUnitWeightLabel = isset($shippingMeta['per_unit_weight'])
                                                ? number_format((float) $shippingMeta['per_unit_weight'], 3, ',', '.') . ' kg'
                                                : null;
                                            if (!empty($shippingMeta['dimensions']) && is_array($shippingMeta['dimensions'])) {
                                                $d = $shippingMeta['dimensions'];
                                                if (isset($d['length'], $d['width'], $d['height'])) {
                                                    $dimensionsLabel = "{$d['length']} x {$d['width']} x {$d['height']} (cm)";
                                                }
                                            }
                                            $packagesCount = isset($shippingMeta['packages']) && is_array($shippingMeta['packages'])
                                                ? count($shippingMeta['packages'])
                                                : null;
                                        }
                                    @endphp

                                    @if($shippingMeta)
                                        <div class="rounded-3 border p-3 bg-light-subtle mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="small text-muted text-uppercase">Dados estimados para frete</strong>
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">Pré-calculado</span>
                                            </div>
                                            <ul class="list-unstyled small text-muted mb-0">
                                                @if($weightLabel)
                                                    <li><strong>Peso total:</strong> {{ $weightLabel }}</li>
                                                @endif
                                                @if($perUnitWeightLabel)
                                                    <li><strong>Peso por unidade:</strong> {{ $perUnitWeightLabel }}</li>
                                                @endif
                                                @if($dimensionsLabel)
                                                    <li><strong>Dimensões:</strong> {{ $dimensionsLabel }}</li>
                                                @endif
                                                @if($packagesCount)
                                                    <li><strong>Volumes previstos:</strong> {{ $packagesCount }}</li>
                                                @endif
                                                @if(!$weightLabel && !$dimensionsLabel && !$packagesCount)
                                                    <li>Este item ainda não recebeu medições.</li>
                                                @endif
                                            </ul>
                                            <small class="d-block text-muted mt-2">Integraremos automaticamente com Correios/transportadoras usando estes valores.</small>
                                        </div>
                                    @endif

                                    <div class="rounded-4 border border-2 p-4 bg-white shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <p class="fw-bold mb-1">Upload deste item</p>
                                                <small class="text-muted">Envie o PDF final diretamente pelo carrinho.</small>
                                            </div>
                                            <span class="badge rounded-pill" style="background:#FFF1E6;color:#B35400;">PDF até 50 MB</span>
                                        </div>
                                        <form action="{{ route('cart.attach.artwork', $id) }}" method="POST" enctype="multipart/form-data" class="upload-form">
                                            @csrf
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small text-uppercase" for="file-front-{{ $loop->index }}">
                                                        Frente (PDF) <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="file" class="form-control form-control-sm" id="file-front-{{ $loop->index }}" name="file_front" accept="application/pdf" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold small text-uppercase" for="file-back-{{ $loop->index }}">
                                                        Verso (opcional)
                                                    </label>
                                                    <input type="file" class="form-control form-control-sm" id="file-back-{{ $loop->index }}" name="file_back" accept="application/pdf">
                                                </div>
                                            </div>
                                            <div class="bg-light rounded-3 p-3 mt-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" id="margin-confirm-{{ $loop->index }}" name="margin_confirmation" required>
                                                    <label class="form-check-label small" for="margin-confirm-{{ $loop->index }}">
                                                        Confirmo que revisei margens de segurança, sangrias e resolução do arquivo.
                                                    </label>
                                                </div>
                                                <ul class="small text-muted mt-2 mb-0 ps-3">
                                                    <li>Formatos aceitos: PDF/X-1a, PDF padrão.</li>
                                                    <li>Limite: 50 MB por arquivo.</li>
                                                    <li>Pode reenviar pelo painel do cliente, se necessário.</li>
                                                </ul>
                                            </div>
                                            <button type="submit" class="btn w-100 mt-3" style="background-color:#FF750F;color:#fff;border:none;">
                                                <i class="fas fa-upload me-1"></i> Enviar arquivos agora
                                            </button>
                                            @if($errors->any())
                                                <div class="text-danger small mt-2">{{ $errors->first() }}</div>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Resumo do Pedido</h5>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <strong>R$ {{ number_format($total, 2, ',', '.') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frete
                                    @if(!empty($shipping['service']))
                                        <small class="text-muted d-block">Correios · {{ $shipping['service'] }}</small>
                                    @endif
                                </span>
                                <strong>
                                    @if($shippingCost > 0)
                                        R$ {{ number_format($shippingCost, 2, ',', '.') }}
                                    @else
                                        <span class="text-muted">A calcular</span>
                                    @endif
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total</span>
                                <strong style="color: #000; font-size: 1.75rem;">R$ {{ number_format($orderTotal, 2, ',', '.') }}</strong>
                            </div>
                            <form action="{{ route('cart.shipping') }}" method="POST" class="border rounded-3 p-3 mb-3 bg-light">
                                @csrf
                                <p class="fw-semibold mb-3">Calcular Frete (Correios)</p>
                                <div class="mb-3">
                                    <label for="shipping-postal-code" class="form-label">CEP de entrega</label>
                                    <input
                                        type="text"
                                        id="shipping-postal-code"
                                        name="postal_code"
                                        class="form-control @error('postal_code') is-invalid @enderror"
                                        value="{{ old('postal_code', $shipping['postal_code'] ?? '') }}"
                                        placeholder="00000000"
                                    maxlength="9"
                                        required
                                    >
                                    @error('postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <label for="shipping-street" class="form-label">Endereço</label>
                                        <input type="text" id="shipping-street" name="street" class="form-control @error('street') is-invalid @enderror" value="{{ old('street', $shipping['street'] ?? '') }}" required>
                                        @error('street') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="shipping-number" class="form-label">Número</label>
                                        <input type="text" id="shipping-number" name="number" class="form-control @error('number') is-invalid @enderror" value="{{ old('number', $shipping['number'] ?? '') }}">
                                        @error('number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <label for="shipping-complement" class="form-label">Complemento</label>
                                        <input type="text" id="shipping-complement" name="complement" class="form-control" value="{{ old('complement', $shipping['complement'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="shipping-district" class="form-label">Bairro</label>
                                        <input type="text" id="shipping-district" name="district" class="form-control" value="{{ old('district', $shipping['district'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-8">
                                        <label for="shipping-city" class="form-label">Cidade</label>
                                        <input type="text" id="shipping-city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $shipping['city'] ?? '') }}" required>
                                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="shipping-state" class="form-label">UF</label>
                                        <input type="text" id="shipping-state" name="state" class="form-control text-uppercase @error('state') is-invalid @enderror" maxlength="2" value="{{ old('state', $shipping['state'] ?? '') }}" required>
                                        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                @php
                                    $selectedService = old('service', $shipping['service'] ?? 'PAC');
                                    $services = [
                                        'PAC' => 'PAC (econômico)',
                                        'SEDEX' => 'SEDEX (expresso)',
                                        'MINI_ENVIOS' => 'Mini Envios',
                                    ];
                                @endphp
                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <label for="shipping-service" class="form-label">Serviço</label>
                                        <select
                                            id="shipping-service"
                                            name="service"
                                            class="form-select @error('service') is-invalid @enderror"
                                            required
                                        >
                                            @foreach($services as $value => $label)
                                                <option value="{{ $value }}" @selected($selectedService === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('service')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="shipping-price" class="form-label">Valor do frete (R$)</label>
                                        <input type="number" step="0.01" min="0" id="shipping-price" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $shippingCost > 0 ? number_format($shippingCost, 2, '.', '') : '') }}">
                                        @error('price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-outline-primary w-100 mt-3">Atualizar frete</button>
                                <small class="text-muted d-block mt-2">
                                    Informe o endereço completo e, se tiver, o valor estimado. Integração automática com Correios em breve.
                                </small>
                            </form>
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg rounded-pill py-3 w-100" style="background-color: #FF750F; color: #000; border-color: #FF750F; font-size: 1.25rem;">Finalizar Compra</a>
                            <small class="text-muted d-block mt-2">
                                O envio dos arquivos finais será solicitado aqui no carrinho, logo após adicionar o produto.
                            </small>

                            @if(!empty($summaryUploads))
                                <div class="mt-4">
                                    <strong class="small text-muted text-uppercase d-block mb-2">Arquivos enviados</strong>
                                    <div class="list-group small shadow-sm">
                                        @foreach($summaryUploads as $file)
                                            <button
                                                type="button"
                                                class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#pdfPreviewModal"
                                                data-pdf="{{ asset('storage/' . $file['path']) }}"
                                                data-title="{{ $file['product'] }} • {{ $file['type'] }}"
                                            >
                                                <span class="badge bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                                    <i class="fas fa-file-pdf"></i>
                                                </span>
                                                <div class="text-start">
                                                    <div class="fw-semibold">{{ $file['product'] }}</div>
                                                    <small class="text-muted">{{ $file['type'] }}</small>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const pdfModal = document.getElementById('pdfPreviewModal');
    if (!pdfModal) return;
    const iframe = pdfModal.querySelector('iframe');
    const titleEl = pdfModal.querySelector('.modal-title');

    pdfModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const url = button?.getAttribute('data-pdf');
        const title = button?.getAttribute('data-title') || 'Visualizar arquivo';
        if (iframe && url) {
            iframe.src = url + '#toolbar=0&navpanes=0';
        }
        if (titleEl) {
            titleEl.textContent = title;
        }
    });

    pdfModal.addEventListener('hidden.bs.modal', () => {
        if (iframe) {
            iframe.src = '';
        }
    });
});
</script>
@endpush

@push('modals')
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualizar arquivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div style="height:80vh;">
                    <iframe src="" class="w-100 h-100 border-0" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush
