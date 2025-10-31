@extends('layouts.app')

@section('title', 'Pagamento Pendente - Gráfica')

@section('content')
<div class="text-center">
    <i class="fas fa-clock text-warning" style="font-size: 5rem;"></i>
    <h1 class="mt-3">Pagamento Pendente</h1>
    <p class="lead">Seu pagamento está sendo processado. Você receberá uma confirmação em breve.</p>
    <a href="{{ route('home') }}" class="btn btn-primary">Voltar à Loja</a>
</div>
@endsection
