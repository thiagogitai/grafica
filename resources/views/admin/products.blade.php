@extends('layouts.admin')

@section('title', 'Gerenciar Produtos - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Produtos</span>
@endsection
@section('admin-title', 'Gerenciar Produtos')
@section('admin-subtitle', 'Acompanhe o catálogo, ajuste templates, marcações e disponibilidade dos produtos.')

@section('admin-actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-dark">
        <i class="fas fa-plus me-2"></i>Novo produto
    </a>
@endsection

@section('admin-content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Produtos cadastrados</h5>
            <span class="badge bg-light text-dark">{{ $products->total() }} itens</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Template</th>
                        <th>Modo</th>
                        <th>Markup</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="text-muted small">{{ Str::limit($product->description, 80) }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $product->effectiveTemplateLabel() }}
                                </span>
                            </td>
                            <td>
                                @if($product->request_only)
                                    <span class="badge bg-warning text-dark">Orçamento</span>
                                @else
                                    <span class="badge bg-success text-white">Venda direta</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ number_format($product->markup_percentage ?? 0, 2, ',', '.') }}%</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-dark">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Deseja excluir este produto?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Nenhum produto encontrado. <a href="{{ route('admin.products.create') }}" class="fw-semibold">Adicionar produto</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $products->links() }}
        </div>
    </div>
@endsection
