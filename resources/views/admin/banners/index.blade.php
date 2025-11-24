@extends('layouts.admin')

@section('title', 'Gerenciar Banners - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Banners</span>
@endsection
@section('admin-title', 'Gerenciar Banners')
@section('admin-subtitle', 'Gerencie os banners exibidos no topo das páginas de produtos.')

@section('admin-actions')
    <a href="{{ route('admin.banners.create') }}" class="btn btn-dark">
        <i class="fas fa-plus me-2"></i>Novo banner
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
            <h5 class="mb-0">Banners cadastrados</h5>
            <span class="badge bg-light text-dark">{{ $banners->count() }} itens</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Imagem</th>
                        <th>Título</th>
                        <th>Link</th>
                        <th>Ordem</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $banner)
                        <tr>
                            <td>
                                <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?? 'Banner' }}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $banner->title ?? 'Sem título' }}</div>
                                @if($banner->description)
                                    <div class="text-muted small">{{ Str::limit($banner->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="_blank" class="text-decoration-none">
                                        <i class="fas fa-external-link-alt me-1"></i>{{ Str::limit($banner->link, 30) }}
                                    </a>
                                @else
                                    <span class="text-muted">Sem link</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $banner->order }}</span>
                            </td>
                            <td>
                                @if($banner->is_active)
                                    <span class="badge bg-success">Ativo</span>
                                @else
                                    <span class="badge bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este banner?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-image fa-2x mb-2 d-block"></i>
                                Nenhum banner cadastrado. <a href="{{ route('admin.banners.create') }}">Criar primeiro banner</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

