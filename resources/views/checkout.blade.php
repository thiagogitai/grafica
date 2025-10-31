@extends('layouts.app')

@section('title', 'Checkout - Gráfica')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <h2 class="mb-4">Dados para contato e entrega</h2>
                <form method="POST" action="{{ route('checkout.process') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $user->name ?? '') }}" required>
                        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $user->email ?? '') }}" required>
                        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $user->phone ?? '') }}" required>
                        @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Endereço de entrega</label>
                        <textarea class="form-control" name="address" rows="3" required>{{ old('address') }}</textarea>
                        @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Método de pagamento</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">Selecione</option>
                            <option value="pix" {{ old('payment_method')==='pix'?'selected':'' }}>PIX</option>
                            <option value="credit_card" {{ old('payment_method')==='credit_card'?'selected':'' }}>Cartão de crédito</option>
                            <option value="bank_transfer" {{ old('payment_method')==='bank_transfer'?'selected':'' }}>Transferência bancária</option>
                        </select>
                        @error('payment_method')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Finalizar Pedido</button>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Resumo do Carrinho</h5>
                        @php $sum = 0; @endphp
                        @if(!empty($cart))
                            <ul class="list-group list-group-flush mb-3">
                                @foreach($cart as $id => $item)
                                    @php $line = ($item['price'] ?? 0) * ($item['quantity'] ?? 1); $sum += $line; @endphp
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">{{ $item['name'] }}</div>
                                            <small class="text-muted">Qtd: {{ $item['quantity'] }}</small>
                                        </div>
                                        <div>R$ {{ number_format($line, 2, ',', '.') }}</div>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Subtotal</span>
                                <span>R$ {{ number_format($sum, 2, ',', '.') }}</span>
                            </div>
                        @else
                            <p class="text-muted mb-0">Seu carrinho está vazio.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>
@endsection

