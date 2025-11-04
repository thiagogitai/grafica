@extends('layouts.app')

@section('body-class', 'product-page')

@section('title', ($config['title_override'] ?? $product->name) . ' - Gráfica Todah Serviços Gráficos')

@php
    $requestOnlyGlobal = $requestOnlyGlobal ?? ($requestOnlyMode ?? false);
    $requestOnlyProduct = $requestOnlyProduct ?? ($product->request_only ?? false);
    $requestOnlyCombined = $requestOnly ?? ($requestOnlyGlobal || $requestOnlyProduct);

    $configOptions = $config['options'] ?? [];
    $quantityOptionConfig = null;
    foreach ($configOptions as $opt) {
        if (($opt['name'] ?? null) === 'quantity') {
            $quantityOptionConfig = $opt;
            break;
        }
    }
@endphp

@section('content')
<div class="container my-5">
    @if(!$requestOnlyCombined)
    <form action="{{ route('cart.add', $product) }}" method="POST" id="customization-form">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="name" value="{{ $product->name }}">
        <input type="hidden" name="price" id="final-price" value="">
        <input type="hidden" name="details" id="final-details" value="">

        <div class="row">
            <!-- Coluna da Esquerda: Imagem -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 2rem;">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded shadow-sm" alt="{{ $product->name }}">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center rounded shadow-sm" style="height: 400px;">
                            <i class="fas fa-book fa-4x text-muted"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Coluna do Meio: Formulário -->
            <div class="col-lg-5">
                <div class="mb-4">
                    <h1 class="display-5 fw-bold">{{ $config['title_override'] ?? $product->name }}</h1>
                    <p class="lead text-muted">{{ $product->description }}</p>
                </div>

                <div id="calculator" class="d-grid gap-3">
                    @if($quantityOptionConfig)
                        @php
                            $qtyDefault = old('options.quantity', $quantityOptionConfig['default'] ?? 1);
                        @endphp
                        <div>
                            <label class="form-label fw-bold">{{ $quantityOptionConfig['label'] ?? 'Quantidade' }}</label>
                            <input
                                type="number"
                                class="form-control form-control-lg"
                                id="quantity"
                                name="options[quantity]"
                                data-option-field="quantity"
                                value="{{ $qtyDefault }}"
                                @if(isset($quantityOptionConfig['min'])) min="{{ $quantityOptionConfig['min'] }}" @endif
                                @if(isset($quantityOptionConfig['max'])) max="{{ $quantityOptionConfig['max'] }}" @endif
                                @if(isset($quantityOptionConfig['step'])) step="{{ $quantityOptionConfig['step'] }}" @endif
                            >
                        </div>
                    @else
                        @php
                            $defaultQty = old('options.quantity', 1);
                        @endphp
                        <div>
                            <label for="quantity" class="form-label fw-bold">Quantidade</label>
                            <select id="quantity" name="options[quantity]" class="form-select" data-option-field="quantity">
                                @foreach([1,2,5,10,20,50,100] as $qtyOption)
                                    <option value="{{ $qtyOption }}" {{ (int)$defaultQty === $qtyOption ? 'selected' : '' }}>{{ $qtyOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @foreach($configOptions as $index => $opt)
                        @php
                            $name = $opt['name'] ?? ('option_' . $index);
                            $label = $opt['label'] ?? ucfirst(str_replace('_', ' ', $name));
                            $type = $opt['type'] ?? 'select';
                            $fieldName = "options[{$name}]";
                            $fieldId = $name;
                        @endphp
                        @continue($name === 'quantity')
                        <div>
                            <label for="{{ $fieldId }}" class="form-label fw-bold">{{ $label }}</label>
                            @if($type === 'number')
                                @php
                                    $value = old("options.$name", $opt['default'] ?? '');
                                @endphp
                                <input
                                    type="number"
                                    class="form-control form-control-lg"
                                    id="{{ $fieldId }}"
                                    name="{{ $fieldName }}"
                                    data-option-field="{{ $name }}"
                                    value="{{ $value }}"
                                    @if(isset($opt['min'])) min="{{ $opt['min'] }}" @endif
                                    @if(isset($opt['max'])) max="{{ $opt['max'] }}" @endif
                                    @if(isset($opt['step'])) step="{{ $opt['step'] }}" @endif
                                >
                            @else
                                @php
                                    $choices = $opt['choices'] ?? [];
                                    $defaultValue = old("options.$name", $opt['default'] ?? ($choices[0]['value'] ?? ''));
                                @endphp
                                <select
                                    id="{{ $fieldId }}"
                                    name="{{ $fieldName }}"
                                    class="form-select"
                                    data-option-field="{{ $name }}"
                                >
                                    @foreach($choices as $choice)
                                        @php
                                            $value = $choice['value'] ?? '';
                                            $choiceLabel = $choice['label'] ?? $value;
                                            $add = $choice['add'] ?? 0;
                                        @endphp
                                        <option value="{{ $value }}" data-add="{{ $add }}" {{ $defaultValue === $value ? 'selected' : '' }}>
                                            {{ $choiceLabel }}
                                            @if(!empty($add))
                                                (+ R$ {{ number_format($add, 2, ',', '.') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Coluna da Direita: Preço e Ações -->
            <div class="col-lg-3">
                <div class="sticky-top bg-light p-4 rounded-3 shadow-sm" style="top: 2rem;">
                    <div class="text-center mb-3 js-price-container d-none">
                        <p class="text-muted">Valor desse pedido:</p>
                        <p id="total-price" class="h2 fw-bold text-primary my-2"></p>
                        <p class="text-muted small">valor de cada unidade: <span id="unit-price"></span></p>
                    </div>
                    <div class="d-grid gap-3">
                        @if(in_array($configSlug ?? '', ['impressao-de-livro', 'impressao-de-panfleto', 'impressao-de-apostila', 'impressao-online-de-livretos-personalizados', 'impressao-de-revista', 'impressao-de-tabloide', 'impressao-de-jornal-de-bairro', 'impressao-de-guia-de-bairro']))
                        <button type="button" class="btn btn-outline-primary btn-lg" id="btn-ver-preco">
                            <i class="fas fa-calculator me-2"></i>
                            <span>VER PREÇO</span>
                        </button>
                        @endif
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" @if(in_array($configSlug ?? '', ['impressao-de-livro', 'impressao-de-panfleto', 'impressao-de-apostila', 'impressao-online-de-livretos-personalizados', 'impressao-de-revista', 'impressao-de-tabloide', 'impressao-de-jornal-de-bairro', 'impressao-de-guia-de-bairro'])) disabled @endif>
                            <i class="fas fa-shopping-cart me-2"></i>
                            <span id="submit-text">@if(in_array($configSlug ?? '', ['impressao-de-livro', 'impressao-de-panfleto', 'impressao-de-apostila', 'impressao-online-de-livretos-personalizados', 'impressao-de-revista', 'impressao-de-tabloide', 'impressao-de-jornal-de-bairro', 'impressao-de-guia-de-bairro']))Clique em "VER PREÇO" primeiro@else Adicionar ao Carrinho @endif</span>
                        </button>
                        <div class="alert alert-info @if(!in_array($configSlug ?? '', ['impressao-de-livro', 'impressao-de-panfleto', 'impressao-de-apostila', 'impressao-online-de-livretos-personalizados', 'impressao-de-revista', 'impressao-de-tabloide', 'impressao-de-jornal-de-bairro', 'impressao-de-guia-de-bairro'])) d-none @endif" id="price-validation-status">
                            <small><i class="fas fa-info-circle me-2"></i>Clique em "VER PREÇO" para calcular o valor</small>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-success small"><i class="fas fa-truck me-2"></i>Frete grátis para todo Brasil!</p>
                        <p class="text-danger small mt-2">*Prazo para a análise do arquivo é de 1 dia útil</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @else
        <div class="alert alert-warning bg-white border-0 shadow-sm mb-4">
            <strong>Orçamento sob medida:</strong> compartilhe os detalhes e retornaremos com uma proposta personalizada.
        </div>
        @if(!empty($globalWhatsappLink))
            <a href="{{ $globalWhatsappLink }}" class="btn btn-primary btn-lg" target="_blank">
                <i class="fab fa-whatsapp me-2"></i> Solicitar orçamento agora
            </a>
        @endif
    @endif
</div>

@if(!$requestOnlyCombined)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePrice = {{ isset($config['base_price']) && is_numeric($config['base_price']) ? (float)$config['base_price'] : 0 }};
    const configSlug = '{{ $configSlug }}';

    // Elementos de UI
    const totalPriceEl = document.getElementById('total-price');
    const unitPriceEl = document.getElementById('unit-price');
    const finalPriceInput = document.getElementById('final-price');
    const finalDetailsInput = document.getElementById('final-details');
    const priceContainer = document.querySelector('.js-price-container');

    function formatCurrency(value) {
        if (isNaN(value) || value === null || value === 0) return "R$ 0,00";
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function getOptions() {
        const options = {};
        document.querySelectorAll('#calculator [data-option-field]').forEach(field => {
            const key = field.getAttribute('data-option-field');
            if (!key) {
                return;
            }
            options[key] = field.value;
        });
        return options;
    }

    // Lista de produtos que precisam validação dupla
    const produtosComValidacao = [
        'impressao-de-livro',
        'impressao-de-panfleto',
        'impressao-de-apostila',
        'impressao-online-de-livretos-personalizados',
        'impressao-de-revista',
        'impressao-de-tabloide',
        'impressao-de-jornal-de-bairro',
        'impressao-de-guia-de-bairro'
    ];

    const precisaValidacao = produtosComValidacao.includes(configSlug);
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const validationStatus = document.getElementById('price-validation-status');
    let precoValidado = false;
    let precoValidadoValue = 0;

    function validatePriceAndEnableButton(opts, quantity) {
        if (!precisaValidacao) {
            // Produtos sem validação: habilitar botão imediatamente
            if (submitBtn) submitBtn.disabled = false;
            if (submitText) submitText.textContent = 'Avançar para Envio da Arte';
            return;
        }

        // Garantir quantidade mínima de 50
        if (quantity < 50) {
            quantity = 50;
            opts.quantity = 50;
            const qtyField = document.querySelector('[data-option-field="quantity"]');
            if (qtyField) qtyField.value = 50;
        }

        // Desabilitar botão e mostrar status
        if (submitBtn) submitBtn.disabled = true;
        if (submitText) submitText.textContent = 'Validando preço...';
        if (validationStatus) {
            validationStatus.classList.remove('d-none');
            validationStatus.classList.remove('alert-danger', 'alert-success');
            validationStatus.classList.add('alert-info');
            validationStatus.innerHTML = '<small><i class="fas fa-spinner fa-spin me-2"></i>Validando preço final no checkout...</small>';
        }

        // Fazer validação dupla - sempre forçar validação no site (bypass cache)
        fetch('/api/product/validate-price', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                ...opts,
                product_slug: configSlug,
                force_validation: true, // Forçar validação sempre no site
                _force: true // Duplo check
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Resposta da validação:', data);
            
            if (data.success && data.validated && data.price > 0) {
                precoValidado = true;
                precoValidadoValue = data.price;
                
                // Atualizar preço exibido
                if (totalPriceEl) totalPriceEl.textContent = formatCurrency(data.price);
                if (unitPriceEl) unitPriceEl.textContent = formatCurrency(data.price / Math.max(quantity, 1));
                if (finalPriceInput) finalPriceInput.value = data.price;
                if (finalDetailsInput) finalDetailsInput.value = JSON.stringify(opts);
                
                // Garantir que o container de preço está visível
                if (priceContainer) {
                    priceContainer.classList.remove('d-none');
                }
                
                // Habilitar botão
                if (submitBtn) submitBtn.disabled = false;
                if (submitText) submitText.textContent = 'Adicionar ao Carrinho';
                if (validationStatus) {
                    validationStatus.classList.remove('alert-info');
                    validationStatus.classList.add('alert-success');
                    validationStatus.innerHTML = '<small><i class="fas fa-check-circle me-2"></i>Preço validado: ' + formatCurrency(data.price) + '</small>';
                }
            } else {
                precoValidado = false;
                
                // Mostrar mensagem de erro mas manter o container visível
                if (priceContainer) {
                    priceContainer.classList.remove('d-none');
                }
                
                if (totalPriceEl) totalPriceEl.textContent = 'Erro ao calcular';
                if (unitPriceEl) unitPriceEl.textContent = 'R$ 0,00';
                
                if (submitBtn) submitBtn.disabled = true;
                if (submitText) submitText.textContent = 'Erro na validação';
                if (validationStatus) {
                    validationStatus.classList.remove('alert-info');
                    validationStatus.classList.add('alert-danger');
                    const errorMsg = data.error || 'Erro ao validar preço. Tente novamente.';
                    validationStatus.innerHTML = '<small><i class="fas fa-exclamation-circle me-2"></i>' + errorMsg + '</small>';
                }
            }
        })
        .catch(error => {
            console.error('Erro na validação:', error);
            precoValidado = false;
            
            // Manter container visível mesmo em caso de erro
            if (priceContainer) {
                priceContainer.classList.remove('d-none');
            }
            
            if (totalPriceEl) totalPriceEl.textContent = 'Erro ao calcular';
            if (unitPriceEl) unitPriceEl.textContent = 'R$ 0,00';
            
            if (submitBtn) submitBtn.disabled = true;
            if (submitText) submitText.textContent = 'Erro na validação';
            if (validationStatus) {
                validationStatus.classList.remove('alert-info');
                validationStatus.classList.add('alert-danger');
                validationStatus.innerHTML = '<small><i class="fas fa-exclamation-circle me-2"></i>Erro ao validar preço. Verifique o console para mais detalhes.</small>';
            }
        });
    }

    function calculatePrice() {
        const opts = getOptions();
        const quantity = Math.max(1, parseInt(opts.quantity ?? '1', 10) || 1);

        // Verificar se precisa validação (incluindo livro)
        if (precisaValidacao || configSlug === 'impressao-de-livro') {
            // Não validar automaticamente - aguardar botão VER PREÇO
            if (totalPriceEl) totalPriceEl.textContent = 'R$ 0,00';
            if (unitPriceEl) unitPriceEl.textContent = 'R$ 0,00';
            if (priceContainer) priceContainer.classList.remove('d-none');
            if (submitBtn) submitBtn.disabled = true;
            if (submitText) submitText.textContent = 'Clique em "VER PREÇO" primeiro';
            if (validationStatus) {
                validationStatus.classList.remove('d-none');
                validationStatus.classList.remove('alert-danger', 'alert-success');
                validationStatus.classList.add('alert-info');
                validationStatus.innerHTML = '<small><i class="fas fa-info-circle me-2"></i>Clique em "VER PREÇO" para calcular o valor</small>';
            }
            return;
        }

        // Verificar se é flyer para usar cálculo especial
        if (configSlug === 'impressao-de-flyer') {
            // Carregar preços do flyer dinamicamente
            fetch('/precos_flyer.json')
                .then(response => response.json())
                .then(prices => {
                    let basePrice = 0;
                    if (opts.quantity && opts.size && opts.paper && opts.color) {
                        try {
                            basePrice = prices[opts.quantity][opts.size][opts.paper][opts.color];
                        } catch (e) {
                            basePrice = 0;
                        }
                    }

                    // Custos adicionais
                    const finishingCost = parseFloat(opts.finishing || '0') || 0;
                    const fileFormatCost = parseFloat(opts.file_format || '0') || 0;
                    const fileCheckCost = parseFloat(opts.file_check || '0') || 0;

                    const totalPrice = (basePrice + finishingCost + fileFormatCost + fileCheckCost) * quantity;
                    const unitPrice = totalPrice / Math.max(quantity, 1);

                    totalPriceEl.textContent = formatCurrency(totalPrice);
                    unitPriceEl.textContent = formatCurrency(unitPrice);
                    finalPriceInput.value = totalPrice;

                    if (totalPrice > 0) {
                        finalDetailsInput.value = JSON.stringify(opts);
                    } else {
                        finalDetailsInput.value = '';
                    }

                    if (priceContainer) {
                        priceContainer.classList.toggle('d-none', totalPrice <= 0);
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar preços:', error);
                    // Fallback para cálculo padrão
                    let additionalPrice = 0;
                    document.querySelectorAll('#calculator select[data-option-field]').forEach(select => {
                        const selected = select.options[select.selectedIndex];
                        const add = parseFloat(selected?.getAttribute('data-add') || '0');
                        if (!isNaN(add)) {
                            additionalPrice += add;
                        }
                    });

                    const totalPrice = (basePrice + additionalPrice) * quantity;
                    const unitPrice = totalPrice / Math.max(quantity, 1);

                    totalPriceEl.textContent = formatCurrency(totalPrice);
                    unitPriceEl.textContent = formatCurrency(unitPrice);
                    finalPriceInput.value = totalPrice;

                    if (totalPrice > 0) {
                        finalDetailsInput.value = JSON.stringify(opts);
                    } else {
                        finalDetailsInput.value = '';
                    }

                    if (priceContainer) {
                        priceContainer.classList.toggle('d-none', totalPrice <= 0);
                    }
                });
        } else {
            // Cálculo padrão para outros produtos
            let additionalPrice = 0;
            document.querySelectorAll('#calculator select[data-option-field]').forEach(select => {
                const selected = select.options[select.selectedIndex];
                const add = parseFloat(selected?.getAttribute('data-add') || '0');
                if (!isNaN(add)) {
                    additionalPrice += add;
                }
            });

            const totalPrice = (basePrice + additionalPrice) * quantity;
            const unitPrice = totalPrice / Math.max(quantity, 1);

            totalPriceEl.textContent = formatCurrency(totalPrice);
            unitPriceEl.textContent = formatCurrency(unitPrice);
            finalPriceInput.value = totalPrice;

            if (totalPrice > 0) {
                finalDetailsInput.value = JSON.stringify(opts);
            } else {
                finalDetailsInput.value = '';
            }

            if (priceContainer) {
                priceContainer.classList.toggle('d-none', totalPrice <= 0);
            }
        }
    }

    const optionFields = document.querySelectorAll('#calculator [data-option-field]');
    optionFields.forEach(field => {
        field.addEventListener('change', function() {
            // Resetar validação quando uma opção mudar
            precoValidado = false;
            precoValidadoValue = 0;
            if (submitBtn && precisaValidacao) {
                submitBtn.disabled = true;
                submitText.textContent = 'Clique em "VER PREÇO" primeiro';
            }
            if (validationStatus && precisaValidacao) {
                validationStatus.classList.remove('alert-success', 'alert-danger');
                validationStatus.classList.add('alert-info');
                validationStatus.innerHTML = '<small><i class="fas fa-info-circle me-2"></i>Clique em "VER PREÇO" para calcular o valor</small>';
            }
            calculatePrice();
        });
        if (field.tagName === 'INPUT') {
            field.addEventListener('input', function() {
                // Resetar validação quando uma opção mudar
                precoValidado = false;
                precoValidadoValue = 0;
                if (submitBtn && precisaValidacao) {
                    submitBtn.disabled = true;
                    submitText.textContent = 'Clique em "VER PREÇO" primeiro';
                }
                calculatePrice();
            });
        }
    });

    // Botão VER PREÇO
    const btnVerPreco = document.getElementById('btn-ver-preco');
    if (btnVerPreco && precisaValidacao) {
        btnVerPreco.addEventListener('click', function() {
            // Sempre pegar as opções atuais dos campos (não usar cache)
            const opts = getOptions();
            const quantity = Math.max(1, parseInt(opts.quantity || '50', 10) || 50);
            
            console.log('Opções capturadas para validação:', opts);
            console.log('Quantidade capturada:', quantity);
            
            validatePriceAndEnableButton(opts, quantity);
        });
    }

    calculatePrice();
});
</script>
@endpush
@endif
@endsection
