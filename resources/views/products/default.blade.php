@extends('layouts.app')

@section('title', $product->name ?? 'Produto indisponível')

@section('content')
    @include('products.partials.template-disabled', [
        'product' => $product ?? null,
    ])
@endsection

