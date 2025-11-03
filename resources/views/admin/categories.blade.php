@extends('layouts.admin')

@section('title', 'Categorias - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Categorias</span>
@endsection
@section('admin-title', 'Categorias de produtos')
@section('admin-subtitle', 'Organize o catálogo criando, editando e excluindo categorias.')

@section('admin-actions')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-dark">
        <i class="fas fa-plus me-2"></i>Nova categoria
    </a>
@endsection

@section('admin-content')
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Categorias cadastradas</h5>
            <span class="badge bg-light text-dark">{{ $categories->total() }} itens</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-dark">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Deseja excluir esta categoria?')" class="d-inline">
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
                            <td colspan="3" class="text-center py-4 text-muted">Nenhuma categoria cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $categories->links() }}
        </div>
    </div>
@endsection
