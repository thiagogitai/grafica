@extends('layouts.app')

@section('content')
@php
    $requestOnlyGlobal = $requestOnlyGlobal ?? ($requestOnlyMode ?? false);
    $requestOnlyProduct = $requestOnlyProduct ?? ($product->request_only ?? false);
    $requestOnlyCombined = $requestOnly ?? ($requestOnlyGlobal || $requestOnlyProduct);
@endphp
<div class="container my-5">
    @if(!$requestOnlyCombined)
    <form method="POST" action="{{ route('cart.add.flyer', $product) }}" id="customization-form">
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
                        <button type="submit" class="btn btn-primary btn-lg" data-action="add-to-cart">
                            <i class="fas fa-shopping-cart me-2"></i>Adicionar ao Carrinho
                        </button>
                        <div class="alert alert-danger d-none" id="cart-add-error"></div>
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-success small"><i class="fas fa-truck me-2"></i>Frete grátis para todo Brasil!</p>
                        <p class="text-danger small mt-2">*Prazo para a análise do arquivo é de 1 dia útil</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="modal fade" id="artworkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar arte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="artwork-upload-form"
                      method="POST"
                      enctype="multipart/form-data"
                      data-upload-url="{{ route('cart.attach.artwork', ['cartItemId' => 'PLACEHOLDER']) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small">Envie os arquivos da sua arte agora ou feche esta janela para enviar depois pelo carrinho.</p>
                        <div class="mb-3">
                            <label for="file_front_modal" class="form-label fw-bold">Arquivo (frente)</label>
                            <input type="file" class="form-control" id="file_front_modal" name="file_front" accept="application/pdf" required>
                            <small class="text-muted">Formato aceito: PDF. Tamanho máximo: 50 MB.</small>
                        </div>
                        @if(!empty($product->is_duplex))
                        <div class="mb-3">
                            <label for="file_back_modal" class="form-label fw-bold">Arquivo (verso)</label>
                            <input type="file" class="form-control" id="file_back_modal" name="file_back" accept="application/pdf">
                        </div>
                        @endif
                        <div class="alert alert-warning small">
                            Confira margens e sangrias antes de enviar para evitar cortes na impressão.
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="margin_confirmation_modal" name="margin_confirmation" value="1" required>
                            <label class="form-check-label" for="margin_confirmation_modal">
                                Confirmo que revisei as margens de segurança da minha arte.
                            </label>
                        </div>
                        <div class="alert alert-danger d-none" id="artwork-error"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Enviar depois</button>
                        <button type="submit" class="btn btn-primary" data-action="upload-artwork">
                            <span class="default-label"><i class="fas fa-upload me-2"></i>Enviar arte</span>
                            <span class="loading-label d-none">Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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

        const quantityValue = parseInt(String(opts.quantity || '').replace(/\D/g, ''), 10) || 1;

        // Etapa 3: Calcular total
        if (basePrice > 0) {
            total = basePrice + finishingCost + fileFormatCost + fileCheckCost;
            unitPrice = total / Math.max(quantityValue, 1);
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

    const customizationForm = document.getElementById('customization-form');
    const cartErrorAlert = document.getElementById('cart-add-error');
    const uploadModalEl = document.getElementById('artworkModal');
    const uploadForm = document.getElementById('artwork-upload-form');
    const uploadErrorAlert = document.getElementById('artwork-error');
    const uploadUrlTemplate = uploadForm ? uploadForm.getAttribute('data-upload-url') : '';
    const modalInstance = (uploadModalEl && uploadForm && window.bootstrap && window.bootstrap.Modal)
        ? new window.bootstrap.Modal(uploadModalEl)
        : null;
    let currentCartItemId = null;

    function toggleButtonLoading(button, isLoading) {
        if (!button) return;
        button.disabled = !!isLoading;
        const loadingLabel = button.querySelector('.loading-label');
        const defaultLabel = button.querySelector('.default-label');
        if (loadingLabel && defaultLabel) {
            loadingLabel.classList.toggle('d-none', !isLoading);
            defaultLabel.classList.toggle('d-none', !!isLoading);
        }
    }

    function showCartError(message) {
        if (!cartErrorAlert) return;
        cartErrorAlert.textContent = message;
        cartErrorAlert.classList.remove('d-none');
    }

    function clearCartError() {
        if (!cartErrorAlert) return;
        cartErrorAlert.textContent = '';
        cartErrorAlert.classList.add('d-none');
    }

    function showUploadError(message) {
        if (!uploadErrorAlert) return;
        uploadErrorAlert.textContent = message;
        uploadErrorAlert.classList.remove('d-none');
    }

    function clearUploadError() {
        if (!uploadErrorAlert) return;
        uploadErrorAlert.textContent = '';
        uploadErrorAlert.classList.add('d-none');
    }

    function extractFirstError(errors) {
        if (!errors || typeof errors !== 'object') {
            return null;
        }
        for (const key in errors) {
            if (Object.prototype.hasOwnProperty.call(errors, key)) {
                const item = errors[key];
                if (Array.isArray(item) && item.length > 0) {
                    return item[0];
                }
                if (typeof item === 'string' && item.trim() !== '') {
                    return item;
                }
            }
        }
        return null;
    }

    function buildUploadUrl(cartItemId) {
        if (!uploadUrlTemplate) return '';
        return uploadUrlTemplate.replace('PLACEHOLDER', encodeURIComponent(cartItemId));
    }

    function resetUploadForm() {
        if (!uploadForm) return;
        uploadForm.reset();
        clearUploadError();
        const uploadButton = uploadForm.querySelector('[data-action="upload-artwork"]');
        toggleButtonLoading(uploadButton, false);
    }

    if (customizationForm && modalInstance && uploadForm && window.fetch) {
        const addButton = customizationForm.querySelector('[data-action="add-to-cart"]');

        customizationForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearCartError();
            calculatePrice();

            if (!finalDetailsInput.value || !finalPriceInput.value) {
                showCartError('Selecione quantidade e configuracoes validas antes de adicionar.');
                return;
            }

            toggleButtonLoading(addButton, true);

            try {
                const formData = new FormData(customizationForm);
                const response = await fetch(customizationForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('json');
                const payload = isJson ? await response.json() : null;

                if (!response.ok || !payload?.success) {
                    const message = payload?.message
                        || extractFirstError(payload?.errors)
                        || 'Nao foi possivel adicionar ao carrinho.';
                    showCartError(message);
                    return;
                }

                currentCartItemId = payload.cart_item_id || null;
                if (!currentCartItemId) {
                    showCartError('Item incluido, mas nao foi possivel iniciar o envio da arte. Verifique o carrinho.');
                    return;
                }

                resetUploadForm();
                uploadForm.action = buildUploadUrl(currentCartItemId);
                modalInstance.show();
            } catch (error) {
                showCartError('Ocorreu um erro ao adicionar ao carrinho. Tente novamente.');
            } finally {
                toggleButtonLoading(addButton, false);
            }
        });

        uploadForm.addEventListener('submit', async function (event) {
            if (!currentCartItemId) {
                return;
            }
            event.preventDefault();
            clearUploadError();
            const uploadButton = uploadForm.querySelector('[data-action="upload-artwork"]');
            toggleButtonLoading(uploadButton, true);

            try {
                const formData = new FormData(uploadForm);
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('json');
                const payload = isJson ? await response.json() : null;

                if (!response.ok || !payload?.success) {
                    const message = payload?.message
                        || extractFirstError(payload?.errors)
                        || 'Nao foi possivel enviar a arte.';
                    showUploadError(message);
                    toggleButtonLoading(uploadButton, false);
                    return;
                }

                const redirectUrl = payload.redirect_url || '{{ route('cart.index') }}';
                window.location.href = redirectUrl;
            } catch (error) {
                showUploadError('Ocorreu um erro ao enviar a arte. Tente novamente.');
                toggleButtonLoading(uploadButton, false);
            }
        });

        uploadModalEl.addEventListener('hidden.bs.modal', function () {
            resetUploadForm();
            currentCartItemId = null;
        });
    }
});
</script>
@endpush
@endif

