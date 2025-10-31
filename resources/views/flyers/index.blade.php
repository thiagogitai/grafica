@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Checkout Personalizado para {{ $product->name }}</h1>

    <form action="{{ route('cart.add') }}" method="POST" id="flyer-checkout-form">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        <div class="form-group">
            <label for="quantity">Quantidade</label>
            <select name="quantity" id="quantity" class="form-control">
                @foreach($prices as $quantity => $sizes)
                    <option value="{{ $quantity }}">{{ $quantity }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="size">Tamanho</label>
            <select name="size" id="size" class="form-control">
                <!-- Options will be populated by JS -->
            </select>
        </div>

        <div class="form-group">
            <label for="paper_type">Tipo de Papel</label>
            <select name="paper_type" id="paper_type" class="form-control">
                <!-- Options will be populated by JS -->
            </select>
        </div>

        <div class="form-group">
            <label for="printing">Impressão</label>
            <select name="printing" id="printing" class="form-control">
                <!-- Options will be populated by JS -->
            </select>
        </div>

        <div class="form-group">
            <h3>Preço Final: R$ <span id="final-price">--</span></h3>
            <input type="hidden" name="price" id="price-input">
        </div>

        <button type="submit" class="btn btn-primary">Adicionar ao Carrinho</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const prices = @json($prices);
    
    const quantitySelect = document.getElementById('quantity');
    const sizeSelect = document.getElementById('size');
    const paperTypeSelect = document.getElementById('paper_type');
    const printingSelect = document.getElementById('printing');
    const finalPriceSpan = document.getElementById('final-price');
    const priceInput = document.getElementById('price-input');

    function updateSizes() {
        const selectedQuantity = quantitySelect.value;
        const sizes = Object.keys(prices[selectedQuantity]);
        
        sizeSelect.innerHTML = '';
        sizes.forEach(size => {
            const option = document.createElement('option');
            option.value = size;
            option.textContent = size;
            sizeSelect.appendChild(option);
        });
        updatePaperTypes();
    }

    function updatePaperTypes() {
        const selectedQuantity = quantitySelect.value;
        const selectedSize = sizeSelect.value;
        const paperTypes = Object.keys(prices[selectedQuantity][selectedSize]);

        paperTypeSelect.innerHTML = '';
        paperTypes.forEach(paperType => {
            const option = document.createElement('option');
            option.value = paperType;
            option.textContent = paperType;
            paperTypeSelect.appendChild(option);
        });
        updatePrintings();
    }

    function updatePrintings() {
        const selectedQuantity = quantitySelect.value;
        const selectedSize = sizeSelect.value;
        const selectedPaperType = paperTypeSelect.value;
        const printings = Object.keys(prices[selectedQuantity][selectedSize][selectedPaperType]);

        printingSelect.innerHTML = '';
        printings.forEach(printing => {
            const option = document.createElement('option');
            option.value = printing;
            option.textContent = printing;
            printingSelect.appendChild(option);
        });
        updatePrice();
    }

    function updatePrice() {
        const selectedQuantity = quantitySelect.value;
        const selectedSize = sizeSelect.value;
        const selectedPaperType = paperTypeSelect.value;
        const selectedPrinting = printingSelect.value;

        if (selectedQuantity && selectedSize && selectedPaperType && selectedPrinting) {
            const price = prices[selectedQuantity][selectedSize][selectedPaperType][selectedPrinting];
            finalPriceSpan.textContent = price.toFixed(2).replace('.', ',');
            priceInput.value = price;
        } else {
            finalPriceSpan.textContent = '--';
            priceInput.value = '';
        }
    }

    quantitySelect.addEventListener('change', updateSizes);
    sizeSelect.addEventListener('change', updatePaperTypes);
    paperTypeSelect.addEventListener('change', updatePrintings);
    printingSelect.addEventListener('change', updatePrice);

    // Initial population
    updateSizes();
});
</script>
@endsection