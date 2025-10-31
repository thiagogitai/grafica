@extends('layouts.admin')

@section('title', 'Gerenciar Produtos - Gráfica Todah')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-box me-2 text-primary"></i>Gerenciar Produtos
        </h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary rounded-pill px-4">
            <i class="fas fa-plus me-2"></i>Adicionar Novo Produto
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-pill" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 fw-bold py-3 px-4">ID</th>
                            <th class="border-0 fw-bold py-3">Nome</th>
                            <th class="border-0 fw-bold py-3">Preço</th>
                            <th class="border-0 fw-bold py-3">Imagem</th>
                            <th class="border-0 fw-bold py-3 px-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="py-3 px-4">{{ $product->id }}</td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="mb-0">{{ $product->name }}</h6>
                                            <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="fw-bold text-primary">R$ {{ number_format($product->price, 2, ',', '.') }}</span>
                                </td>
                                <td class="py-3">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este produto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                        <h5>Nenhum produto encontrado</h5>
                                        <p>Comece adicionando seu primeiro produto.</p>
                                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary rounded-pill">
                                            <i class="fas fa-plus me-2"></i>Adicionar Produto
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($products->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
