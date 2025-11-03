@extends('layouts.app')

@section('content')
@php
    $requestOnlyGlobal = $requestOnlyGlobal ?? ($requestOnlyMode ?? false);
    $requestOnlyProduct = $requestOnlyProduct ?? ($product->request_only ?? false);
    $requestOnlyCombined = $requestOnly ?? ($requestOnlyGlobal || $requestOnlyProduct);
@endphp
<div class="container my-5">
    @if(!$requestOnlyCombined)
    <form action="{{ route('cart.add.flyer') }}" method="POST" id="flyer-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="name" value="{{ $product->name }}">
        <input type="hidden" name="price" id="final-price" value="">
        <input type="hidden" name="details" id="final-details" value="">

        <div class="row">
            <!-- Coluna da Esquerda: Imagem e Gabaritos -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 2rem;">
                    <img src="{{ asset('images/folder.png') }}" alt="{{ $product->name }}" class="img-fluid rounded shadow-sm mb-4">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#gabaritoModal">
                            <i class="fas fa-download me-2"></i> Baixe o gabarito do produto
                        </button>
                        <a href="mailto:contato@exemplo.com" class="btn btn-outline-secondary">
                            <i class="fas fa-envelope me-2"></i> Envie suas dúvidas
                        </a>
                    </div>
                </div>
            </div>

            <!-- Coluna do Meio: Calculadora -->
            <div class="col-lg-5">
                <div class="mb-4">
                    <h1 class="display-5 fw-bold">{{ $product->name }}</h1>
                    <p class="lead text-muted">{{ $product->description }}</p>
                </div>

                <div id="calculator" class="d-grid gap-3">
                    <div>
                        <label for="quantity" class="form-label fw-bold">1- Quantidade</label>
                        <select id="quantity" name="options[quantity]" class="form-select"></select>
                    </div>
                    <div>
                        <label for="size" class="form-label fw-bold">2- Formato</label>
                        <select id="size" name="options[size]" class="form-select" disabled></select>
                    </div>
                    <div>
                        <label for="paper" class="form-label fw-bold">3- Papel</label>
                        <select id="paper" name="options[paper]" class="form-select" disabled></select>
                    </div>
                    <div>
                        <label for="color" class="form-label fw-bold">4- Cores</label>
                        <select id="color" name="options[color]" class="form-select" disabled></select>
                    </div>
                    <div>
                        <label for="finishing" class="form-label fw-bold">5- Acabamento</label>
                        <select id="finishing" name="options[finishing]" class="form-select">
                           <option value="0">Cantos Retos</option>
                           <option value="0">Cantos Retos + Laminação FOSCO Bopp F/V (acima de 200g)</option>
                           <option value="0">Cantos Retos + Laminação BRILHO Bopp F/V (Acima de 200g)</option>
                        </select>
                    </div>
                    <div>
                        <label for="file_format" class="form-label fw-bold">6- Formato do Arquivo</label>
                        <select id="file_format" name="options[file_format]" class="form-select">
                           <option value="0">Arquivo PDF (fechado para impressão) (Grátis)</option>
                           <option value="80">Arquvo CDR / INDD / AI / JPG / PNG (aberto) (R$ 80,00)</option>
                        </select>
                    </div>
                    <div>
                        <label for="file_check" class="form-label fw-bold">7- Verificação do Arquivo</label>
                        <select id="file_check" name="options[file_check]" class="form-select">
                           <option value="0">Digital On-Line (Grátis)</option>
                           <option value="150">Prova de Cor Impressa - SOMENTE para São Paulo (R$ 150,00)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Coluna da Direita: Preço e Ações -->
            <div class="col-lg-3">
                <div class="sticky-top bg-light p-4 rounded-3 shadow-sm" style="top: 2rem;">
                    <div class="text-center mb-3">
                        <p class="text-muted">Valor desse pedido:</p>
                        <p id="total-price" class="h2 fw-bold text-primary my-2">R$ 0,00</p>
                        <p class="text-muted small">valor de cada unidade: <span id="unit-price">R$ 0,00</span></p>
                    </div>
                    <div class="d-grid gap-3">
                        <div>
                            <label for="file-upload" class="form-label small">Faça upload da sua arte:</label>
                            <input id="file-upload" name="artwork" type="file" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>Adicionar ao carrinho
                        </button>
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
            <strong>Orçamento sob medida:</strong> compartilhe os detalhes do seu flyer e retornaremos com uma proposta personalizada.
        </div>
        @if(!empty($globalWhatsappLink))
            <a href="{{ $globalWhatsappLink }}" class="btn btn-primary btn-lg" target="_blank">
                <i class="fab fa-whatsapp me-2"></i> Solicitar orçamento agora
            </a>
        @endif
    @endif
</div>

<!-- Modal de Gabaritos -->
<div class="modal fade" id="gabaritoModal" tabindex="-1" aria-labelledby="gabaritoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="gabaritoModalLabel">Gabaritos para Panfleto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Formato</th>
              <th class="text-center">AI</th>
              <th class="text-center">PDF</th>
              <th class="text-center">EPS</th>
            </tr>
          </thead>
          <tbody>
            <tr>
                <td>A4 - 210mm x 297mm</td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">AI</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">PDF</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">EPS</a></td>
            </tr>
            <tr>
                <td>A5 - 148mm x 210mm</td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">AI</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">PDF</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">EPS</a></td>
            </tr>
            <tr>
                <td>A6 - 105mm x 148mm</td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">AI</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">PDF</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">EPS</a></td>
            </tr>
            <tr>
                <td>DL - 100mm x 210mm</td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">AI</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">PDF</a></td>
                <td class="text-center"><a href="#" class="btn btn-sm btn-outline-danger">EPS</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@if(!$requestOnlyCombined)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const prices = @json($prices);

    // Selects
    const quantitySelect = document.getElementById('quantity');
    const sizeSelect = document.getElementById('size');
    const paperSelect = document.getElementById('paper');
    const colorSelect = document.getElementById('color');
    const finishingSelect = document.getElementById('finishing');
    const fileFormatSelect = document.getElementById('file_format');
    const fileCheckSelect = document.getElementById('file_check');
    
    // Elementos de UI
    const totalPriceEl = document.getElementById('total-price');
    const unitPriceEl = document.getElementById('unit-price');
    const finalPriceInput = document.getElementById('final-price');
    const finalDetailsInput = document.getElementById('final-details');

    function formatCurrency(value) {
        if (isNaN(value) || value === null || value === 0) return "R$ 0,00";
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function populateSelect(select, options) {
        const currentValue = select.value;
        select.innerHTML = '';

        const normalizedOptions = (options || []).map(option => {
            if (typeof option === 'string') {
                return { value: option, label: option.trim() || option };
            }
            if (option && typeof option === 'object') {
                const value = option.value ?? '';
                const label = option.label ?? value;
                return { value, label };
            }
            return { value: '', label: '' };
        }).filter(entry => entry.value !== '');

        if (normalizedOptions.length === 0) {
            select.value = '';
            return '';
        }

        const selectedEntry = normalizedOptions.find(entry => entry.value === currentValue) ?? normalizedOptions[0];

        normalizedOptions.forEach(({ value, label }) => {
            const isSelected = value === selectedEntry.value ? ' selected' : '';
            select.innerHTML += `<option value="${value}"${isSelected}>${label}</option>`;
        });

        select.value = selectedEntry.value;
        return selectedEntry.value;
    }

    function getOptions() {
        return {
            quantity: quantitySelect.value,
            size: sizeSelect.value,
            paper: paperSelect.value,
            color: colorSelect.value,
            finishing: finishingSelect.options[finishingSelect.selectedIndex].text,
            file_format: fileFormatSelect.options[fileFormatSelect.selectedIndex].text,
            file_check: fileCheckSelect.options[fileCheckSelect.selectedIndex].text,
        };
    }

    function calculatePrice() {
        const opts = getOptions();
        let basePrice = 0;
        let total = 0;
        let unitPrice = 0;

        // Etapa 1: Obter preço base do JSON
        if (opts.quantity && opts.size && opts.paper && opts.color) {
            try {
                basePrice = prices[opts.quantity][opts.size][opts.paper][opts.color];
            } catch (e) {
                basePrice = 0; // Combinação não encontrada
            }
        }

        // Etapa 2: Obter custos adicionais
        const finishingCost = parseFloat(finishingSelect.value) || 0;
        const fileFormatCost = parseFloat(fileFormatSelect.value) || 0;
        const fileCheckCost = parseFloat(fileCheckSelect.value) || 0;

        // Etapa 3: Calcular total
        if (basePrice > 0) {
            total = basePrice + finishingCost + fileFormatCost + fileCheckCost;
            unitPrice = total / parseInt(opts.quantity);
        } else {
            total = 0;
            unitPrice = 0;
        }

        // Etapa 4: Atualizar a UI e os campos do formulário
        totalPriceEl.textContent = formatCurrency(total);
        unitPriceEl.textContent = formatCurrency(unitPrice);
        finalPriceInput.value = total;

        if (total > 0) {
            finalDetailsInput.value = JSON.stringify(opts);
        } else {
            finalDetailsInput.value = '';
        }
    }

    function updateDependentSelects() {
        const quantity = populateSelect(quantitySelect, Object.keys(prices || {}));
        quantitySelect.disabled = !quantity;

        if (quantity) {
            const sizes = Object.keys(prices[quantity] || {});
            const selectedSize = populateSelect(sizeSelect, sizes);
            sizeSelect.disabled = sizes.length === 0;

            if (selectedSize) {
                const papers = Object.keys(prices[quantity]?.[selectedSize] || {});
                const selectedPaper = populateSelect(paperSelect, papers);
                paperSelect.disabled = papers.length === 0;

                if (selectedPaper) {
                    const colors = Object.keys(prices[quantity]?.[selectedSize]?.[selectedPaper] || {});
                    const selectedColor = populateSelect(colorSelect, colors);
                    colorSelect.disabled = colors.length === 0;
                    if (!selectedColor) {
                        colorSelect.value = '';
                    }
                } else {
                    colorSelect.disabled = true;
                    colorSelect.innerHTML = '';
                    colorSelect.value = '';
                }
            } else {
                paperSelect.disabled = true;
                paperSelect.innerHTML = '';
                paperSelect.value = '';
                colorSelect.disabled = true;
                colorSelect.innerHTML = '';
                colorSelect.value = '';
            }
        } else {
            sizeSelect.disabled = true;
            sizeSelect.innerHTML = '';
            sizeSelect.value = '';
            paperSelect.disabled = true;
            paperSelect.innerHTML = '';
            paperSelect.value = '';
            colorSelect.disabled = true;
            colorSelect.innerHTML = '';
            colorSelect.value = '';
        }

        calculatePrice(); // Recalcula o preço sempre que um select dependente muda
    }

    // Adiciona os listeners
    [quantitySelect, sizeSelect, paperSelect, colorSelect, finishingSelect, fileFormatSelect, fileCheckSelect].forEach(select => {
        select.addEventListener('change', calculatePrice);
    });
    [quantitySelect, sizeSelect, paperSelect].forEach(select => {
        select.addEventListener('change', updateDependentSelects);
    });

    // Popula o select inicial de quantidade e inicia a cadeia de eventos
    populateSelect(quantitySelect, Object.keys(prices));
    updateDependentSelects();
});
</script>
@endpush
@endif

