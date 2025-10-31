@extends('layouts.app')

@section('body-class', 'cart-page')

@section('title', 'Carrinho - Gráfica')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Carrinho de Compras</h1>
        </div>

        @if(empty($cart))
            <div class="text-center">
                <div class="alert alert-info mb-4">Seu carrinho está vazio.</div>
                <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Continuar Comprando</a>
            </div>
        @else
            <div class="row">
                <div class="col-lg-8">
                    @foreach($cart as $id => $item)
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        @if(isset($item['image']) && $item['image'])
                                            <img src="{{ asset('storage/' . $item['image']) }}" class="img-fluid rounded" alt="{{ $item['name'] }}">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 100px;">
                                                <i class="fas fa-image text-muted fa-2x"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-5">
                                        <h5 class="card-title">{{ $item['name'] }}</h5>
                                        
                                        {{-- Exibe as opções de personalização --}}
                                        @if(!empty($item['options']))
                                            @php
                                                $labelMap = [
                                                    'size' => 'Formato',
                                                    'paper' => 'Papel',
                                                    'color' => 'Cores',
                                                    'finishing' => 'Acabamento',
                                                    'file_format' => 'Formato do arquivo',
                                                    'file_check' => 'Verificação do arquivo',
                                                ];
                                            @endphp
                                            <ul class="list-unstyled small text-muted mb-2">
                                                @foreach($item['options'] as $key => $value)
                                                    @if($value && $key != 'quantity') {{-- A quantidade já é exibida separadamente --}}
                                                        @php
                                                            $label = $labelMap[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                                        @endphp
                                                        <li>
                                                            <strong>{{ $label }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif

                                        {{-- Exibe os links dos arquivos de arte --}}
                                        @if(!empty($item['artwork']))
                                            <div class="mt-2">
                                                <strong class="small text-muted">Arquivos:</strong>
                                                <ul class="list-unstyled small">
                                                    @foreach($item['artwork'] as $type => $path)
                                                        <li>
                                                            <a href="{{ asset('storage/' . $path) }}" target="_blank">
                                                                <i class="fas fa-download me-1"></i>
                                                                Ver arquivo ({{ $type }})
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <p class="card-text text-muted fw-bold">R$ {{ number_format($item['price'], 2, ',', '.') }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-0"><strong>Quantidade:</strong> {{ $item['quantity'] }}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <form method="POST" action="{{ route('cart.remove', $id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-dark btn-sm w-100">Remover</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Resumo do Pedido</h5>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total:</span>
                                <strong style="color: #000; font-size: 2rem;">R$ {{ number_format($total, 2, ',', '.') }}</strong>
                            </div>
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg rounded-pill py-3 w-100" style="background-color: #FF750F; color: #000; border-color: #FF750F; font-size: 1.25rem;">Finalizar Compra</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
