@extends('layouts.app')

@section('title', 'Checkout - Gráfica')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                @php
                    $shippingForm = $shipping ?? [];
                    $services = [
                        'PAC' => 'PAC (econômico)',
                        'SEDEX' => 'SEDEX (expresso)',
                        'MINI_ENVIOS' => 'Mini Envios',
                    ];
                @endphp
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
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control" name="postal_code" value="{{ old('postal_code', $shippingForm['postal_code'] ?? '') }}" required>
                        @error('postal_code')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="street" value="{{ old('street', $shippingForm['street'] ?? '') }}" required>
                            @error('street')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="number" value="{{ old('number', $shippingForm['number'] ?? '') }}">
                            @error('number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control" name="complement" value="{{ old('complement', $shippingForm['complement'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control" name="district" value="{{ old('district', $shippingForm['district'] ?? '') }}">
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-8">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="city" value="{{ old('city', $shippingForm['city'] ?? '') }}" required>
                            @error('city')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UF</label>
                            <input type="text" class="form-control text-uppercase" name="state" maxlength="2" value="{{ old('state', $shippingForm['state'] ?? '') }}" required>
                            @error('state')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label">Serviço de frete</label>
                        <select class="form-select" name="service" required>
                            @foreach($services as $value => $label)
                                <option value="{{ $value }}" @selected(old('service', $shippingForm['service'] ?? 'PAC') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('service')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Endereço completo</label>
                        <textarea class="form-control" rows="3" readonly>{{ $shippingAddress ?? 'Preencha o formulário acima para carregar automaticamente.' }}</textarea>
                        <small class="text-muted">Este resumo é usado para impressão na etiqueta e também será enviado para nossa equipe.</small>
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
