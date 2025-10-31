@extends('layouts.app')

@section('title', 'Pagamento Recusado - Gráfica')

@section('content')
<div class="text-center">
    <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
    <h1 class="mt-3">Pagamento Recusado</h1>
    <p class="lead">Houve um problema com seu pagamento. Tente novamente.</p>
    <a href="{{ route('cart.index') }}" class="btn btn-primary">Voltar ao Carrinho</a>
</div>
@endsection
