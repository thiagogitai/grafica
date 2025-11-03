@extends('layouts.app')

@php
    $requestOnlyGlobal = $requestOnlyGlobal ?? ($requestOnlyMode ?? false);
    $requestOnlyProduct = $requestOnlyProduct ?? ($product->request_only ?? false);
    $requestOnlyCombined = $requestOnly ?? ($requestOnlyGlobal || $requestOnlyProduct);

    $productId = isset($product->id) ? (int)$product->id : null;
    $productSlug = $product->slug ?? null;

    $isBookProduct = $productId === 8 || $productSlug === 'impressao-de-livro';
    $bookFormOptions = [];
    $defaultQuantity = 1;

    if ($isBookProduct) {
        $defaultQuantity = 25;
        $bookFormOptions = [
            [
                'label' => '2- Formato do Miolo (Páginas)',
                'name' => 'formato_miolo_paginas',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    '105x148mm (A6)',
                    '118x175mm',
                    '115x183mm',
                    '137x210mm (formato otimizado offset)',
                    '138x210mm',
                    '140x210mm',
                    '148x210mm (A5)',
                    '155x230mm (formato otimizado digital)',
                    '158x230mm',
                    '160x230mm',
                    '170x240mm',
                    '170x230mm',
                    '190mmx230mm',
                    '205x205mm',
                    '205x275mm',
                    '210x280mm',
                    '210x297mm (A4)',
                    '230x230mm',
                    '230x300mm',
                    '250x300mm',
                    '260x260mm',
                    '280x300mm',
                    '300x280mm',
                ]),
            ],
            [
                'label' => '3- Papel CAPA',
                'name' => 'papel_capa',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Cartão Triplex 250gr',
                    'Couche Brilho 150gr',
                    'SEM CAPA',
                    'Cartão Triplex 300gr',
                    'Couche Fosco 150gr',
                    'Couche Fosco 170gr',
                    'Couche Fosco 210gr',
                    'Couche Brilho 170gr',
                    'Couche Brilho 210gr',
                    'Couche Brilho 250g Importado',
                ]),
            ],
            [
                'label' => '4- Cores CAPA',
                'name' => 'cores_capa',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    '4 cores Frente',
                    '4 cores FxV',
                    '1 Cor Frente (Preto)',
                    '1 Cor FxV (Preto)',
                    '4 cores Frente x 1 cor Preto Verso',
                    '4 cores Frente x 1 cor Pantone Verso',
                    '5 cores Frente x 1 cor Preto Verso',
                    '5 cores Frente x 1 cor Pantone Verso',
                ]),
            ],
            [
                'label' => '5 - Orelha da CAPA',
                'name' => 'orelha_capa',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'SEM ORELHA',
                    'COM Orelha de 8cm',
                    'COM Orelha de 7cm',
                    'COM Orelha de 6cm',
                    'COM Orelha de 5cm',
                    'COM Orelha de 9cm',
                    'COM Orelha de 10cm',
                    'COM Orelha de 11cm',
                    'COM Orelha de 12cm',
                    'COM Orelha de 13cm',
                    'COM Orelha de 14cm',
                ]),
            ],
            [
                'label' => '6- Acabamento CAPA',
                'name' => 'acabamento_capa',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Laminação FOSCA FRENTE (Acima de 240g)',
                    'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',
                    'Laminação BRILHO FRENTE (Acima de 240g)',
                    'Verniz UV TOTAL FRENTE (Acima de 240g)',
                    'Sem Acabamento',
                ]),
            ],
            [
                'label' => '7- Papel MIOLO',
                'name' => 'papel_miolo',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Offset 75gr',
                    'Pólen Natural 80g',
                    'Pólen Bold 70g',
                    'Pólen Bold 90g',
                    'Offset 90gr',
                    'Impressão Offset - >500unidades',
                    'Pólen Natural 70g',
                    'Offset 70gr',
                    'Offset 56gr',
                    'Offset 63gr',
                    'Offset 120gr',
                    'Couche Fosco 80gr (Offset - Acima de 300pçs)',
                    'Couche Fosco 90gr Importado (Digital - Abaixo de 300pçs)',
                    'Couche Fosco 115gr Importado (Digital - Abaixo de 300pçs)',
                    'Couche Fosco 150gr',
                    'Couche Fosco 170gr',
                    'Couche brilho 80gr (Offset - Acima de 300pçs)',
                    'Couche brilho 90gr importado (Digital - Abaixo de 300pçs)',
                    'Couche brilho 115gr Importado (Digital - Abaixo de 300pçs)',
                    'Couche brilho 150gr',
                    'Couche brilho 170gr',
                    'Couche brilho 210gr',
                    'Couche Fosco 90gr (Offset - Acima de 300pçs)',
                    'Couche Fosco 115gr (Offset - Acima de 300pçs)',
                    'Couche Brilho 90gr (Offset - Acima de 300pçs)',
                    'Couche Brilho 115gr (Offset - Acima de 300pçs)',
                ]),
            ],
            [
                'label' => '8- Cores MIOLO',
                'name' => 'cores_miolo',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    '4 cores frente e verso',
                    '1 cor frente e verso PRETO',
                ]),
            ],
            [
                'label' => '9- MIOLO Sangrado?',
                'name' => 'miolo_sangrado',
                'choices' => array_map(fn ($value) => ['value' => $value], ['NÃO', 'SIM']),
            ],
            [
                'label' => '10- Quantidade Paginas MIOLO',
                'name' => 'quantidade_paginas_miolo',
                'choices' => array_map(fn ($pages) => ['value' => "Miolo {$pages} páginas"], range(8, 1412, 4)),
            ],
            [
                'label' => '11- Acabamento MIOLO',
                'name' => 'acabamento_miolo',
                'choices' => array_map(fn ($value) => ['value' => $value], ['Dobrado']),
            ],
            [
                'label' => '12- Acabamento LIVRO',
                'name' => 'acabamento_livro',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Colado PUR',
                    'Costurado',
                    'Grampeado - 2 grampos',
                    'Espiral Plastico',
                    'Capa Dura Papelão 18 (1,8mm) + Cola PUR',
                    'Capa Dura Papelão 18  (1,8mm) + Costura - acima de 1.000un',
                    'Capa Dura Papelão 18 (1,8mm) + Espiral',
                    'Capa Dura Papelão 15 (2,2mm) + Cola PUR',
                    'Capa Dura Papelão 15 (2,2mm) + Costura - acima de 1.000 un.',
                    'Capa Dura Papelão 15 (2,2mm) + Espiral',
                ]),
            ],
            [
                'label' => '13- Guardas LIVRO',
                'name' => 'guardas_livro',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'SEM GUARDAS',
                    'offset 180g - sem impressão',
                    'offset 180g - Com Impressão 4x4 Escala',
                    'Vergê Madrepérola180g (Creme) - sem impressão',
                    'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala',
                    'Couche 170g + Laminação Fosca 1 lado - Com Impressão 4x4 Escala',
                ]),
            ],
            [
                'label' => '14- Extras',
                'name' => 'extras',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Nenhum',
                    'Shrink Individual',
                    'Shrink Coletivo c/ 5 peças',
                    'Shrink Coletivo c/ 10 peças',
                    'Shrink Coletivo c/ 20 peças',
                    'Shrink Coletivo c/ 30 peças',
                    'Shrink Coletivo c/ 50 peças',
                ]),
            ],
            [
                'label' => '15- Frete',
                'name' => 'frete',
                'choices' => array_map(fn ($value) => ['value' => $value], ['Incluso', 'Cliente Retira']),
            ],
            [
                'label' => '16- Verificação do Arquivo',
                'name' => 'verificacao_arquivo',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
                    'Digital On-Line - via Web-Approval ou PDF',
                    'Capa: Prova de Cor Digital | Miolo: Aprovação Virtual',
                    'Capa: Prova de Cor Digital | Miolo: Prova em Baixa (Plotter)',
                    'Capa+ Miolo: Prova de Cor Impressa no Papel - Xerox Igen',
                    'Prova de Cor Impressa Xerox Igen + Protótipo no Papel com Acabamentos (exceto UV)',
                ]),
            ],
            [
                'label' => '17- Prazo de Entrega',
                'name' => 'prazo_entrega',
                'choices' => array_map(fn ($value) => ['value' => $value], [
                    'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
                ]),
            ],
        ];
    }
@endphp

@section('body-class', 'product-page')

@section('title', $product->name . ' - Gráfica Todah Serviços Gráficos')

@section('content')
<div class="container my-5">
    @if(!$requestOnlyCombined)
    <form method="GET" action="{{ route('upload.create', $product) }}" id="customization-form">

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
                    <h1 class="display-5 fw-bold">{{ $product->name }}</h1>
                    <p class="lead text-muted">{{ $product->description }}</p>
                </div>

                <div id="calculator" class="d-grid gap-3">
                    @if($isBookProduct)
                        <div>
                            <label class="form-label fw-bold">1- Quantidade</label>
                            <input
                                type="number"
                                class="form-control form-control-lg"
                                id="quantity"
                                name="quantity"
                                value="{{ old('quantity', $defaultQuantity) }}"
                                min="1"
                                step="1"
                            >
                        </div>

                        @foreach($bookFormOptions as $option)
                            @php
                                $choices = $option['choices'];
                                $defaultValue = $choices[0]['value'] ?? '';
                                $selectedValue = old("options.{$option['name']}", $defaultValue);
                            @endphp
                            <div>
                                <label class="form-label fw-bold">{{ $option['label'] }}</label>
                                <select
                                    class="form-select"
                                    name="options[{{ $option['name'] }}]"
                                    id="{{ $option['name'] }}"
                                >
                                    @foreach($choices as $choice)
                                        @php
                                            $value = $choice['value'] ?? '';
                                            $label = $choice['label'] ?? $value;
                                            $additional = $choice['add'] ?? 0;
                                        @endphp
                                        <option value="{{ $value }}" data-add="{{ $additional }}" {{ $selectedValue === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    @else
                        <div>
                            <label class="form-label fw-bold">Quantidade</label>
                            <div class="quantity-selector d-flex align-items-center">
                                <button type="button" class="btn btn-outline-secondary rounded-pill me-3" onclick="window.decrementQty()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control form-control-lg text-center rounded-pill" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="100" style="width: 100px;" readonly>
                                <button type="button" class="btn btn-outline-secondary rounded-pill ms-3" onclick="window.incrementQty()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-bold">Tipo de Capa</label>
                            <select class="form-select" name="capa_tipo" required>
                                <option value="brochura" {{ old('capa_tipo','brochura')==='brochura' ? 'selected' : '' }}>Brochura (R$ 0,00)</option>
                                <option value="capa_dura" {{ old('capa_tipo')==='capa_dura' ? 'selected' : '' }}>Capa Dura (+ R$ 15,00)</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label fw-bold">Número de Páginas</label>
                            <select class="form-select" name="paginas" required>
                                <option value="50" {{ old('paginas','50')==='50' ? 'selected' : '' }}>50 páginas (R$ 0,00)</option>
                                <option value="100" {{ old('paginas')==='100' ? 'selected' : '' }}>100 páginas (+ R$ 10,00)</option>
                                <option value="200" {{ old('paginas')==='200' ? 'selected' : '' }}>200 páginas (+ R$ 20,00)</option>
                                <option value="300" {{ old('paginas')==='300' ? 'selected' : '' }}>300 páginas (+ R$ 30,00)</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label fw-bold">Tipo de Papel</label>
                            <select class="form-select" name="papel_tipo" required>
                                <option value="offset_75" {{ old('papel_tipo','offset_75')==='offset_75' ? 'selected' : '' }}>Offset 75g (R$ 0,00)</option>
                                <option value="offset_90" {{ old('papel_tipo')==='offset_90' ? 'selected' : '' }}>Offset 90g (+ R$ 5,00)</option>
                                <option value="couche_120" {{ old('papel_tipo')==='couche_120' ? 'selected' : '' }}>Couché 120g (+ R$ 10,00)</option>
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Coluna da Direita: Preço e Ações -->
            <div class="col-lg-3">
                <div class="sticky-top bg-light p-4 rounded-3 shadow-sm" style="top: 2rem;">
                    @php
                        $usesConfigTemplate = isset($product) && method_exists($product, 'usesConfigTemplate') ? $product->usesConfigTemplate() : false;
                        $isFlyerTemplate = isset($product) && ($product->template ?? null) === \App\Models\Product::TEMPLATE_FLYER;
                        $usesDynamicPricing = $usesConfigTemplate || $isFlyerTemplate;
                        $staticPrice = 'R$ ' . number_format($product->price, 2, ',', '.');
                    @endphp
                    <div class="text-center mb-3 {{ $usesDynamicPricing ? 'js-price-container d-none' : '' }}">
                        <p class="text-muted">Valor desse pedido:</p>
                        <p id="total-price" class="h2 fw-bold text-primary my-2">{{ $usesDynamicPricing ? '' : $staticPrice }}</p>
                        <p class="text-muted small">valor de cada unidade: <span id="unit-price">{{ $usesDynamicPricing ? '' : $staticPrice }}</span></p>
                    </div>
                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-upload me-2"></i>Avançar para Envio da Arte
                        </button>
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-success small"><i class="fas fa-truck me-2"></i>Entrega em até 7 dias</p>
                        <p class="text-danger small mt-2">*Prazo para a análise do arquivo é de 1 dia útil</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @else
        <div class="alert alert-warning bg-white border-0 shadow-sm mb-4">
            <strong>Orçamento sob medida:</strong> informe seus requisitos e nossa equipe enviará uma proposta personalizada.
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
@php
    $templateIsDynamic = $usesDynamicPricing ?? (
        isset($product)
        && method_exists($product, 'usesConfigTemplate')
        && $product->usesConfigTemplate()
    );
    if (!$templateIsDynamic && isset($product) && ($product->template ?? null) === \App\Models\Product::TEMPLATE_FLYER) {
        $templateIsDynamic = true;
    }
@endphp
document.addEventListener('DOMContentLoaded', function () {
    const basePrice = {{ $templateIsDynamic ? 0 : $product->price }};
    const isBookProduct = {{ json_encode($isBookProduct) }};

    const totalPriceEl = document.getElementById('total-price');
    const unitPriceEl = document.getElementById('unit-price');
    const quantityInput = document.getElementById('quantity');
    const priceContainer = document.querySelector('.js-price-container');

    function formatCurrency(value) {
        if (isNaN(value) || value === null || value === 0) return 'R$ 0,00';
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    if (isBookProduct) {
        function getBookQuantity() {
            if (!quantityInput) {
                return {{ (int) $defaultQuantity }};
            }
            const parsed = parseInt(quantityInput.value, 10);
            return Number.isNaN(parsed) || parsed < 1 ? {{ (int) $defaultQuantity }} : parsed;
        }

        function getAdditionalAmount() {
            let total = 0;
            document.querySelectorAll('#calculator select').forEach(select => {
                const selectedOption = select.selectedOptions && select.selectedOptions[0];
                if (!selectedOption) {
                    return;
                }
                const add = parseFloat(selectedOption.getAttribute('data-add') || '0');
                if (!Number.isNaN(add)) {
                    total += add;
                }
            });
            return total;
        }

        function updateBookPrice() {
            const quantity = getBookQuantity();
            if (quantityInput) {
                quantityInput.value = quantity;
            }
            const additionalPrice = getAdditionalAmount();
            const totalPrice = (basePrice + additionalPrice) * quantity;
            const unitPrice = totalPrice / Math.max(quantity, 1);

            totalPriceEl.textContent = formatCurrency(totalPrice);
            unitPriceEl.textContent = formatCurrency(unitPrice);

            if (priceContainer) {
                priceContainer.classList.toggle('d-none', totalPrice <= 0);
            }
        }

        document.querySelectorAll('#calculator select').forEach(select => {
            select.addEventListener('change', updateBookPrice);
        });

        if (quantityInput) {
            quantityInput.addEventListener('change', updateBookPrice);
            quantityInput.addEventListener('input', updateBookPrice);
        }

        updateBookPrice();
    } else {
        const capaSelect = document.querySelector('select[name="capa_tipo"]');
        const paginasSelect = document.querySelector('select[name="paginas"]');
        const papelSelect = document.querySelector('select[name="papel_tipo"]');

        function getDefaultQuantity() {
            const parsed = parseInt(quantityInput?.value ?? '1', 10);
            return Number.isNaN(parsed) || parsed < 1 ? 1 : parsed;
        }

        function updateDefaultPrice() {
            const quantity = getDefaultQuantity();
            if (quantityInput) {
                quantityInput.value = quantity;
            }

            let additionalPrice = 0;

            if (capaSelect?.value === 'capa_dura') additionalPrice += 15;

            switch (paginasSelect?.value) {
                case '100':
                    additionalPrice += 10;
                    break;
                case '200':
                    additionalPrice += 20;
                    break;
                case '300':
                    additionalPrice += 30;
                    break;
                default:
                    break;
            }

            if (papelSelect?.value === 'offset_90') additionalPrice += 5;
            else if (papelSelect?.value === 'couche_120') additionalPrice += 10;

            const totalPrice = (basePrice + additionalPrice) * quantity;
            const unitPrice = totalPrice / Math.max(quantity, 1);

            totalPriceEl.textContent = formatCurrency(totalPrice);
            unitPriceEl.textContent = formatCurrency(unitPrice);

            if (priceContainer) {
                priceContainer.classList.toggle('d-none', totalPrice <= 0);
            }
        }

        window.incrementQty = function () {
            if (!quantityInput) return;
            const currentValue = getDefaultQuantity();
            if (currentValue < 100) {
                quantityInput.value = currentValue + 1;
                updateDefaultPrice();
            }
        };

        window.decrementQty = function () {
            if (!quantityInput) return;
            const currentValue = getDefaultQuantity();
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
                updateDefaultPrice();
            }
        };

        [capaSelect, paginasSelect, papelSelect].forEach(select => {
            if (select) {
                select.addEventListener('change', updateDefaultPrice);
            }
        });

        updateDefaultPrice();
    }
});
</script>
@endpush
@endif
@endsection
