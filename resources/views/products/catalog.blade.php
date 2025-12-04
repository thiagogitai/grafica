@extends('layouts.app')

@section('title', 'Catálogo de Produtos')

@section('content')
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Produtos</h1>
                <p class="text-muted mb-0">Veja todos os produtos e clique para configurar.</p>
            </div>
            <form class="d-flex" method="GET" action="{{ route('products.catalog') }}">
                <input type="search" name="q" value="{{ $search }}" class="form-control me-2" placeholder="Buscar produto">
                <button class="btn btn-primary">Buscar</button>
            </form>
        </div>

        @if($products->isEmpty())
            <div class="alert alert-info">Nenhum produto encontrado.</div>
        @else
            <div class="row g-4">
                @foreach($products as $product)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" class="card-img-top" alt="{{ $product->name }}">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $product->name }}</h5>
                                <p class="card-text text-muted flex-grow-1">{{ Str::limit($product->description, 120) }}</p>
                                <a href="{{ route('product.show', $product) }}" class="btn btn-outline-primary mt-auto">Ver detalhes</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
