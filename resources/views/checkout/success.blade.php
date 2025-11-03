@extends('layouts.app')

@section('title', 'Pagamento Aprovado - Gráfica')

@section('content')
<div class="text-center">
    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
    <h1 class="mt-3">Pagamento Aprovado!</h1>
    <p class="lead">Seu pedido foi processado com sucesso.</p>
    <a href="{{ route('home') }}" class="btn btn-primary">Continuar Comprando</a>
    @auth
        <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-primary ms-2">Acompanhar meu pedido</a>
    @endauth
</div>
@endsection
