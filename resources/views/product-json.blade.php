@extends('layouts.app')

@section('title', ($config['title_override'] ?? $product->name) . ' - Gráfica')

@section('content')
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-6">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded-4 shadow" alt="{{ $product->name }}">
            @else
                <div class="bg-light rounded-4 d-flex align-items-center justify-content-center" style="height:400px">
                    <i class="fas fa-image fa-3x text-muted"></i>
                </div>
            @endif
        </div>
        <div class="col-lg-6">
            <h1 class="fw-bold mb-3">{{ $config['title_override'] ?? $product->name }}</h1>
            <p class="text-muted">{{ $product->description }}</p>

            @php $actionUpload = (bool)($config['redirect_to_upload'] ?? false); @endphp
            <form method="{{ $actionUpload ? 'GET' : 'POST' }}" action="{{ $actionUpload ? route('upload.create', $product) : route('cart.add', $product) }}" id="cfg-form">
                @csrf
                <input type="hidden" name="quantity" id="quantity" value="1" />

                @foreach(($config['options'] ?? []) as $opt)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $opt['label'] ?? $opt['name'] }}</label>
                        <select class="form-select" name="{{ $opt['name'] }}" data-opt="{{ $opt['name'] }}" required>
                            <option value="">Selecione</option>
                            @foreach(($opt['choices'] ?? []) as $choice)
                                <option value="{{ $choice['value'] }}" data-add="{{ $choice['add'] ?? 0 }}">{{ $choice['label'] ?? $choice['value'] }} @if(($choice['add'] ?? 0)>0) (+ R$ {{ number_format($choice['add'],2,',','.') }}) @endif</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                    <div>
                        <small class="text-muted">Total</small>
                        <div class="fs-4 fw-bold" id="total-price">R$ {{ number_format($product->price,2,',','.') }}</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-secondary" onclick="chgQty(-1)">-</button>
                        <span class="mx-3" id="qty-label">1</span>
                        <button type="button" class="btn btn-outline-secondary" onclick="chgQty(1)">+</button>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        {{ $actionUpload ? 'Avançar para Envio da Arte' : 'Adicionar ao Carrinho' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const basePrice = {{ isset($config['base_price']) && is_numeric($config['base_price']) ? (float)$config['base_price'] : (float)$product->price }};
function calcTotal(){
  let add = 0;
  document.querySelectorAll('select[data-opt]').forEach(sel => {
    const opt = sel.options[sel.selectedIndex];
    const inc = parseFloat(opt?.getAttribute('data-add') || '0');
    add += isNaN(inc) ? 0 : inc;
  });
  const qty = parseInt(document.getElementById('quantity').value || '1');
  const total = (basePrice + add) * (isNaN(qty) ? 1 : qty);
  document.getElementById('total-price').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
  document.getElementById('qty-label').textContent = qty;
}
function chgQty(delta){
  const el = document.getElementById('quantity');
  let v = parseInt(el.value || '1');
  v = Math.max(1, v + delta);
  el.value = v;
  calcTotal();
}
document.querySelectorAll('select[data-opt]').forEach(sel => sel.addEventListener('change', calcTotal));
calcTotal();
</script>
@endpush
@endsection

