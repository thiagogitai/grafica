@extends('layouts.app')

@php
    $requestOnlyGlobal = $requestOnlyGlobal ?? ($requestOnlyMode ?? false);
    $requestOnlyProduct = $requestOnlyProduct ?? ($product->request_only ?? false);
    $requestOnlyCombined = $requestOnly ?? ($requestOnlyGlobal || $requestOnlyProduct);

    $configOptions = $config['options'] ?? [];
    $quantityOption = collect($configOptions)->firstWhere('name', 'quantity');
    $nonQuantityOptions = collect($configOptions)->filter(fn ($opt) => ($opt['name'] ?? '') !== 'quantity')->values()->all();

    $configSlug = $configSlug ?? \App\Services\ProductConfig::slugForProduct($product);
    $hero = $hero ?? [
        'title' => $product->name,
        'subtitle' => 'Configuração fiel ao fluxo da matriz Eskenazi, com validação oficial de preço em tempo real.',
    ];
    $availableQuantities = [
        50, 100, 150, 200, 250, 300, 350, 400, 450, 500,
        600, 700, 800, 900, 1000, 1250, 1500, 1750, 2000,
        2250, 2500, 2750, 3000, 3250, 3500, 3750, 4000,
        4250, 4500, 4750, 5000
    ];
    // Para apostila, quantidade mínima é 20
    $minQuantity = ($configSlug === 'impressao-de-apostila') ? 20 : ($quantityOption['min'] ?? 50);
    $defaultQuantity = (int) old('options.quantity', $quantityOption['default'] ?? $minQuantity);
    // Garantir que defaultQuantity seja pelo menos minQuantity
    if ($defaultQuantity < $minQuantity) {
        $defaultQuantity = $minQuantity;
    }
    if (!in_array($defaultQuantity, $availableQuantities, true)) {
        $defaultQuantity = $availableQuantities[0];
    }
    $markupFactor = $markupFactor ?? 1;
    $initialTotalDisplay = $initialFormattedPrice ?? '--';
    $initialUnitDisplay = $initialUnitFormattedPrice ?? '--';
    $initialPriceValue = $initialPrice ?? '';
    $initialDetailsValue = $initialOptionsJson ?? '';
@endphp

@section('title', $config['title_override'] ?? $product->name)

@push('styles')
<style>
    :root {
        --eskenazi-primary: #ff6600;
        --eskenazi-dark: #1f1f1f;
        --eskenazi-muted: #6c6c6c;
    }

    body.product-eskenazi {
        background: #f5f5f5;
    }

    .eskenazi-hero {
        background: linear-gradient(135deg, #fff 0%, #fff5ed 60%, #ffe4d0 100%);
        border-radius: 28px;
        padding: 3rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .eskenazi-hero h1 {
        font-size: clamp(2rem, 3vw, 2.8rem);
        font-weight: 700;
        color: var(--eskenazi-dark);
    }

    .eskenazi-hero p {
        font-size: 1.1rem;
        color: #4a4a4a;
        max-width: 600px;
    }

    .eskenazi-grid {
        display: grid;
        grid-template-columns: minmax(220px, 0.9fr) minmax(360px, 1.4fr) minmax(300px, 1fr);
        gap: 1.5rem;
        align-items: start;
    }

    .eskenazi-column--sticky {
        position: sticky;
        top: 24px;
        align-self: start;
    }

    .eskenazi-card {
        background: #fff;
        border-radius: 18px;
        padding: 1.75rem;
        box-shadow: 0 15px 45px rgba(17, 17, 17, .08);
    }

    .eskenazi-pill-list {
        display: grid;
        gap: 1rem;
    }

    .eskenazi-pill {
        border: 1px dashed rgba(255, 102, 0, .35);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        font-weight: 600;
        color: #444;
        background: #fff;
    }

    .eskenazi-pill span {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 102, 0, .12);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--eskenazi-primary);
        font-size: 1.2rem;
    }

    .pricing-panel header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .pricing-panel h2 {
        font-size: 1.5rem;
        margin: 0;
        color: var(--eskenazi-dark);
    }

    .pricing-panel small {
        color: var(--eskenazi-muted);
        font-weight: 600;
    }

    .eskenazi-form label {
        font-weight: 700;
        font-size: .95rem;
        color: var(--eskenazi-dark);
    }

    .eskenazi-form .form-control,
    .eskenazi-form .form-select {
        border-radius: 12px;
        border: 1px solid #e2e2e2;
        padding: .85rem 1rem;
        font-size: .95rem;
        transition: border .2s ease, box-shadow .2s ease;
    }

    .eskenazi-form .form-control:focus,
    .eskenazi-form .form-select:focus {
        border-color: var(--eskenazi-primary);
        box-shadow: 0 0 0 .15rem rgba(255, 102, 0, .15);
    }

    .quantity-warning {
        font-size: .85rem;
        color: #b45309;
        margin-top: .4rem;
        display: none;
    }

    .quantity-warning.active {
        display: block;
    }

    .summary-card {
        background: #ffffff;
        color: #1f1f1f;
        border: 1px solid #e2e2e2;
        border-radius: 20px;
        padding: 2rem;
        position: sticky;
        top: 24px;
        overflow: hidden;
        box-shadow: 0 15px 45px rgba(17, 17, 17, .08);
    }

    .summary-card h3 {
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--eskenazi-muted);
    }

    .summary-card .total-price {
        font-size: 2.4rem;
        font-weight: 700;
        margin: .2rem 0;
        color: var(--eskenazi-dark);
    }

    .summary-card .unit-price {
        color: var(--eskenazi-muted);
        font-size: 1rem;
    }

    .summary-card .status {
        margin-top: 1rem;
        font-size: .95rem;
        color: var(--eskenazi-muted);
    }

    .summary-card .btn-primary {
        width: 100%;
        margin-top: 1.5rem;
        background: var(--eskenazi-primary);
        border: none;
        font-weight: 700;
        letter-spacing: .5px;
        padding: .95rem 1.25rem;
        border-radius: 12px;
    }

    .summary-card .btn-primary:disabled {
        opacity: .6;
    }

    .file-upload-block {
        margin-top: 2rem;
        background: rgba(0, 0, 0, .03);
        border-radius: 14px;
        padding: 1.25rem;
    }

    .file-upload-block label {
        font-size: .95rem;
        font-weight: 600;
        color: var(--eskenazi-dark);
    }

    .eskenazi-badge-list {
        margin-top: 2.5rem;
        display: grid;
        gap: .8rem;
    }

    .eskenazi-badge {
        display: flex;
        align-items: center;
        gap: .75rem;
        font-weight: 600;
        color: #333;
    }

    .eskenazi-badge i {
        color: var(--eskenazi-primary);
        font-size: 1.2rem;
    }

    @media (max-width: 992px) {
        .eskenazi-grid {
            grid-template-columns: 1fr;
        }
        .eskenazi-column--sticky {
            position: static;
        }
    }
</style>
@endpush

@section('body-class', 'product-eskenazi')

@section('content')
<div class="container py-4 py-lg-5">
        @if(isset($banners) && $banners->count() > 0)
            <div class="banners-section mb-4">
                <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($banners as $index => $banner)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="_blank">
                                        <img src="{{ asset('storage/' . $banner->image) }}" class="d-block w-100" alt="{{ $banner->title ?? 'Banner' }}" style="max-height: 400px; object-fit: cover; border-radius: 12px;">
                                    </a>
                                @else
                                    <img src="{{ asset('storage/' . $banner->image) }}" class="d-block w-100" alt="{{ $banner->title ?? 'Banner' }}" style="max-height: 400px; object-fit: cover; border-radius: 12px;">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($banners->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Próximo</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif


        @if($requestOnlyCombined)
            <div class="alert alert-warning shadow-sm">
                Este produto está disponível apenas mediante solicitação. Fale com nossa equipe para prosseguir.
            </div>
        @else
            <form action="{{ route('cart.add', $product) }}" method="POST" id="book-calculator-form" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="name" value="{{ $product->name }}">
                <input type="hidden" name="price" id="final-price" value="{{ $initialPriceValue }}">
                <input type="hidden" name="details" id="final-details" value="{{ e($initialDetailsValue) }}">
                <input type="hidden" name="shipping_meta" id="shipping-meta" value="">

                <div class="eskenazi-grid">
                    <div class="eskenazi-card eskenazi-column--sticky">
                        <div class="text-center mb-4">
                            @if(!empty($product->image))
                                <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded shadow-sm" alt="{{ $product->name }}">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-book fa-3x text-muted"></i>
                                </div>
                            @endif
                        </div>
                        <h2 class="h5 fw-bold mb-3">{{ $product->name }}</h2>
                        @if(!empty($product->description))
                            <p class="text-muted">{{ $product->description }}</p>
                        @else
                            <p class="text-muted">Configure todas as características do livro e mantenha os mesmos parâmetros utilizados na matriz.</p>
                        @endif
                    </div>
                    <div class="eskenazi-card pricing-panel eskenazi-form">
                        <header>
                            <div>
                                <small>Calculadora oficial</small>
                                <h2>Selecione as características</h2>
                            </div>
                        </header>

                        <div class="mb-4">
                            <label class="form-label">{{ $quantityOption['label'] ?? 'Quantidade' }}</label>
                            @if(($quantityOption['type'] ?? 'select') === 'number')
                                <input
                                    type="number"
                                    class="form-control form-control-lg"
                                    id="quantity"
                                    name="options[quantity]"
                                    data-option-field="quantity"
                                    value="{{ $defaultQuantity }}"
                                    min="{{ $minQuantity }}"
                                    step="{{ $quantityOption['step'] ?? 1 }}"
                                    required
                                >
                            @else
                                <select
                                    class="form-select form-select-lg"
                                    id="quantity"
                                    name="options[quantity]"
                                    data-option-field="quantity"
                                >
                                    @foreach($availableQuantities as $qtyOption)
                                        <option value="{{ $qtyOption }}" @selected($qtyOption === $defaultQuantity)>
                                            {{ $qtyOption }} unidades
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <div class="quantity-warning" id="quantity-warning">
                                Ajustamos a tiragem mínima para 300 unidades por causa desta configuração de papel.
                            </div>
                        </div>

                        <div class="d-grid gap-4">
                            @foreach($nonQuantityOptions as $index => $opt)
                                @php
                                    $fieldId = $opt['name'] ?? 'opt_' . $index;
                                    $label = $opt['label'] ?? ucfirst(str_replace('_', ' ', $fieldId));
                                    $type = $opt['type'] ?? 'select';
                                    $choices = $opt['choices'] ?? [];
                                    $default = old("options.$fieldId", $opt['default'] ?? ($choices[0]['value'] ?? null));
                                @endphp
                                <div>
                                    <label class="form-label" for="{{ $fieldId }}">{{ $label }}</label>

                                    @if($type === 'select')
                                        <select
                                            class="form-select form-select-lg"
                                            id="{{ $fieldId }}"
                                            name="options[{{ $fieldId }}]"
                                            data-option-field="{{ $fieldId }}"
                                        >
                                            @foreach($choices as $choice)
                                                @php
                                                    $value = $choice['value'] ?? $choice['label'] ?? '';
                                                @endphp
                                                <option value="{{ $value }}" @selected($value == $default)>
                                                    {{ $choice['label'] ?? $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($type === 'number')
                                        <input
                                            type="number"
                                            class="form-control form-control-lg"
                                            id="{{ $fieldId }}"
                                            name="options[{{ $fieldId }}]"
                                            data-option-field="{{ $fieldId }}"
                                            value="{{ $default }}"
                                        >
                                    @else
                                        <input
                                            type="text"
                                            class="form-control form-control-lg"
                                            id="{{ $fieldId }}"
                                            name="options[{{ $fieldId }}]"
                                            data-option-field="{{ $fieldId }}"
                                            value="{{ $default }}"
                                        >
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="summary-card eskenazi-column--sticky" id="price-panel">
                        <h3>Resumo do Pedido</h3>
                        <div class="total-price" id="pricing-total">{{ $initialTotalDisplay }}</div>
                        <div class="unit-price">
                            <span id="pricing-unit">{{ $initialUnitDisplay }}</span> por unidade
                        </div>

                        <button type="submit" id="btnAddToCartButton" class="btn btn-primary btn-lg" disabled>
                            Adicionar ao carrinho
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection

@if(!$requestOnlyCombined)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const slug = @json($configSlug);
                const pricePanel = document.getElementById('price-panel');
                const totalEl = document.getElementById('pricing-total');
                const unitEl = document.getElementById('pricing-unit');
                const quantityInput = document.getElementById('quantity');
                const quantityWarningEl = document.getElementById('quantity-warning');
                const finalPriceInput = document.getElementById('final-price');
                const finalDetailsInput = document.getElementById('final-details');
                const shippingMetaInput = document.getElementById('shipping-meta');
                const submitBtn = document.getElementById('btnAddToCartButton');
                const optionFields = document.querySelectorAll('[data-option-field]');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const markupFactor = parseFloat(@json($markupFactor ?? 1)) || 1;
                let lastValidTotal = parseFloat(@json($initialPriceValue ?? 0)) || null;
                if (lastValidTotal && submitBtn) {
                    submitBtn.removeAttribute('disabled');
                }

                let debounce;
                let inflight = false;

                const currencyFormatter = new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                    minimumFractionDigits: 2
                });

                function gatherOptions() {
                    const opts = {};
                    optionFields.forEach((field) => {
                        if (!field.name) return;
                        const key = field.dataset.optionField;
                        if (!key) return;
                        let value = field.value;
                        
                        // Para selects, pegar o valor selecionado
                        if (field.tagName === 'SELECT') {
                            const selected = field.options[field.selectedIndex];
                            value = selected ? selected.value : '';
                        }
                        // Para inputs number, manter como número (será convertido no backend se necessário)
                        else if (field.type === 'number') {
                            value = field.value ? parseInt(field.value, 10) : field.value;
                        }
                        
                        // Mapeamento especial para apostila - folhas_miolo
                        if (slug === 'impressao-de-apostila') {
                            // folhas_miolo_1: backend converterá para "Miolo X folhas"
                            // folhas_miolo_2: backend converterá para "NÃO TEM MIOLO 2" se necessário
                            // Não precisa fazer nada aqui, o backend faz a conversão
                        }
                        
                        opts[key] = value;
                    });
                    return opts;
                }

                function applyQuantityRules(options) {
                    const needsHighRun = /Acima de 300pçs/i.test(options.papel_miolo ?? '');
                    if (needsHighRun && options.quantity < 300) {
                        options.quantity = 300;
                        if (quantityInput) {
                            const optionToSelect = Array.from(quantityInput.options).find(opt => parseInt(opt.value, 10) === 300);
                            if (optionToSelect) {
                                optionToSelect.selected = true;
                            }
                        }
                        quantityWarningEl?.classList.add('active');
                    } else {
                        quantityWarningEl?.classList.remove('active');
                    }
                }

                function requestPrice() {
                    const options = gatherOptions();
                    // Para apostila, quantidade mínima é 20, para outros produtos é 50
                    const minQuantity = slug === 'impressao-de-apostila' ? 20 : 50;
                    let quantity = Math.max(minQuantity, parseInt(options.quantity ?? minQuantity.toString(), 10) || minQuantity);
                    options.quantity = quantity;
                    
                    // Atualizar o input HTML para refletir a quantidade mínima
                    if (quantityInput && parseInt(quantityInput.value, 10) < minQuantity) {
                        quantityInput.value = minQuantity;
                        quantity = minQuantity;
                        options.quantity = minQuantity;
                    }
                    
                    applyQuantityRules(options);
                    quantity = options.quantity;

                    inflight = true;
                    submitBtn?.setAttribute('disabled', 'disabled');

                    const payload = {
                        product_slug: slug,
                        ...options
                    };

                    fetch('/api/product/validate-price', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify(payload)
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            inflight = false;
                            if (data.success && data.price) {
                                const baseTotal = parseFloat(data.price);
                                const adjustedTotal = baseTotal * markupFactor;
                                const unit = adjustedTotal / quantity;
                                const formattedTotal = currencyFormatter.format(adjustedTotal);
                                const formattedUnit = currencyFormatter.format(unit);

                                totalEl.textContent = formattedTotal;
                                unitEl.textContent = formattedUnit;
                                finalPriceInput.value = adjustedTotal;
                                const detailPayload = data.payload ?? {
                                    product_slug: slug,
                                    ...options,
                                };
                                finalDetailsInput.value = JSON.stringify(detailPayload);
                                if (shippingMetaInput) {
                                    if (data.meta) {
                                        shippingMetaInput.value = JSON.stringify(data.meta);
                                    } else {
                                        shippingMetaInput.value = '';
                                    }
                                }

                                lastValidTotal = adjustedTotal;
                                submitBtn?.removeAttribute('disabled');
                            } else {
                                throw new Error(data.error || 'Não foi possível validar o preço.');
                            }
                        })
                        .catch((error) => {
                            inflight = false;
                            console.error('Erro ao buscar preço:', error);
                            if (lastValidTotal) {
                                // Mantém último valor válido e permite adicionar ao carrinho
                                submitBtn?.removeAttribute('disabled');
                            } else {
                                finalPriceInput.value = '';
                                finalDetailsInput.value = '';
                                submitBtn?.setAttribute('disabled', 'disabled');
                            }
                        });
                }

                optionFields.forEach((field) => {
                    const handler = () => {
                        if (inflight) return;
                        if (debounce) clearTimeout(debounce);
                        debounce = setTimeout(requestPrice, 450);
                    };

                    // Para inputs, usar 'input' e 'change' para capturar digitação e seleção
                    if (field.tagName === 'INPUT') {
                        field.addEventListener('input', handler);
                        field.addEventListener('change', handler);
                        field.addEventListener('blur', handler); // Também capturar quando sair do campo
                    } else {
                        // Para selects, usar apenas 'change'
                        field.addEventListener('change', handler);
                    }
                });

                // Para apostila, garantir que quantidade inicial seja 20
                if (slug === 'impressao-de-apostila' && quantityInput) {
                    const currentValue = parseInt(quantityInput.value, 10) || 20;
                    if (currentValue < 20) {
                        quantityInput.value = 20;
                    }
                }
                requestPrice();
            });
        </script>
    @endpush
@endif
