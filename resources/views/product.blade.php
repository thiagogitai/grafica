@extends('layouts.app')

@section('body-class', 'product-page')

@section('title', $product->name . ' - Gráfica Todah')

@section('content')
<div class="container py-5">
    <div class="row g-5">
        <!-- Imagem do Produto -->
        <div class="col-lg-6">
            <div class="position-relative">
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded-4 shadow-lg" alt="{{ $product->name }}">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center rounded-4 shadow-lg" style="height: 500px;">
                        <i class="fas fa-book fa-5x text-muted"></i>
                    </div>
                @endif
            </div>
        </div>

        <!-- Detalhes do Produto -->
        <div class="col-lg-6">
            <div class="product-details">
                <h1 class="display-5 fw-bold mb-3">{{ $product->name }}</h1>
                <p class="lead text-muted mb-4 fs-5">{{ $product->description }}</p>

                <div class="price-section mb-4 p-4 bg-light rounded-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted">Preço</span>
                            <h2 class="text-primary fw-bold mb-0">R$ {{ number_format($product->price, 2, ',', '.') }}</h2>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">a partir de</small>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Personalização -->
                <form method="GET" action="{{ route('upload.create', $product) }}" id="customization-form">

                    <!-- Tipo de Capa -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Tipo de Capa</label>
                        <select class="form-select form-select-lg rounded-pill" name="capa_tipo" required>
                            <option value="brochura" {{ old('capa_tipo','brochura')==='brochura' ? 'selected' : '' }}>Brochura (R$ 0,00)</option>
                            <option value="capa_dura" {{ old('capa_tipo')==='capa_dura' ? 'selected' : '' }}>Capa Dura (+ R$ 15,00)</option>
                        </select>
                    </div>

                    <!-- Número de Páginas -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Número de Páginas</label>
                        <select class="form-select form-select-lg rounded-pill" name="paginas" required>
                            <option value="50" {{ old('paginas','50')==='50' ? 'selected' : '' }}>50 páginas (R$ 0,00)</option>
                            <option value="100" {{ old('paginas')==='100' ? 'selected' : '' }}>100 páginas (+ R$ 10,00)</option>
                            <option value="200" {{ old('paginas')==='200' ? 'selected' : '' }}>200 páginas (+ R$ 20,00)</option>
                            <option value="300" {{ old('paginas')==='300' ? 'selected' : '' }}>300 páginas (+ R$ 30,00)</option>
                        </select>
                    </div>

                    <!-- Tipo de Papel -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Tipo de Papel</label>
                        <select class="form-select form-select-lg rounded-pill" name="papel_tipo" required>
                            <option value="offset_75" {{ old('papel_tipo','offset_75')==='offset_75' ? 'selected' : '' }}>Offset 75g (R$ 0,00)</option>
                            <option value="offset_90" {{ old('papel_tipo')==='offset_90' ? 'selected' : '' }}>Offset 90g (+ R$ 5,00)</option>
                            <option value="couche_120" {{ old('papel_tipo')==='couche_120' ? 'selected' : '' }}>Couchê 120g (+ R$ 10,00)</option>
                        </select>
                    </div>

                    <!-- Quantidade -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Quantidade</label>
                        <div class="quantity-selector d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary rounded-pill me-3" onclick="decrementQty()">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-control form-control-lg text-center rounded-pill" id="quantity" name="quantity" value="1" min="1" max="100" style="width: 100px;" readonly>
                            <button type="button" class="btn btn-outline-secondary rounded-pill ms-3" onclick="incrementQty()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Preço Total -->
                    <div class="total-price mb-4 p-4 bg-primary bg-opacity-10 rounded-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5">Total:</span>
                            <span class="fw-bold fs-3 text-primary" id="total-price">R$ {{ number_format($product->price, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3">
                            <i class="fas fa-file-upload me-2"></i>Avançar para Envio da Arte
                        </button>
                    </div>
                </form>

                <!-- Informações Adicionais -->
                <div class="mt-5">
                    <h5 class="fw-bold mb-3">Informações do Produto</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-truck text-primary me-2"></i>
                                <small>Entrega em até 7 dias</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-primary me-2"></i>
                                <small>Garantia de qualidade</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-undo text-primary me-2"></i>
                                <small>Devolução grátis</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-award text-primary me-2"></i>
                                <small>Impressão profissional</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function incrementQty() {
    const qtyInput = document.getElementById('quantity');
    const currentValue = parseInt(qtyInput.value);
    if (currentValue < 100) {
        qtyInput.value = currentValue + 1;
        updateTotalPrice();
    }
}

function decrementQty() {
    const qtyInput = document.getElementById('quantity');
    const currentValue = parseInt(qtyInput.value);
    if (currentValue > 1) {
        qtyInput.value = currentValue - 1;
        updateTotalPrice();
    }
}

function updateTotalPrice() {
    const basePrice = {{ $product->price }};
    const quantity = parseInt(document.getElementById('quantity').value);

    // Adicionar lógica para calcular preço baseado nas opções selecionadas
    let additionalPrice = 0;

    const capaTipo = document.querySelector('select[name="capa_tipo"]').value;
    if (capaTipo === 'capa_dura') additionalPrice += 15;

    const paginas = document.querySelector('select[name="paginas"]').value;
    if (paginas === '100') additionalPrice += 10;
    else if (paginas === '200') additionalPrice += 20;
    else if (paginas === '300') additionalPrice += 30;

    const papelTipo = document.querySelector('select[name="papel_tipo"]').value;
    if (papelTipo === 'offset_90') additionalPrice += 5;
    else if (papelTipo === 'couche_120') additionalPrice += 10;

    const totalPrice = (basePrice + additionalPrice) * quantity;
    document.getElementById('total-price').textContent = 'R$ ' + totalPrice.toFixed(2).replace('.', ',');
}

// Atualizar preço quando opções mudarem
document.querySelectorAll('select').forEach(select => {
    select.addEventListener('change', updateTotalPrice);
});

document.addEventListener('DOMContentLoaded', function () {
    // Garante atualização de preço com valores iniciais
    document.querySelectorAll('form#customization-form select').forEach(function(sel){
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    });
    updateTotalPrice();
});
</script>
@endpush
@endsection
