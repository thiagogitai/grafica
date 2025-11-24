@extends('layouts.admin')

@section('title', 'Criar Banner - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> <a href="{{ route('admin.banners.index') }}">Banners</a> <i class="fas fa-angle-right"></i> Criar</span>
@endsection
@section('admin-title', 'Criar Novo Banner')
@section('admin-subtitle', 'Adicione um novo banner para exibir nas páginas de produtos.')

@section('admin-content')
    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informações do Banner</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="title" class="form-label">Título (opcional)</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="image" class="form-label">Imagem <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" required>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, WEBP (máx. 5MB). Recomendado: largura mínima de 1200px.</small>
                    </div>

                    <div class="col-12">
                        <label for="link" class="form-label">Link (opcional)</label>
                        <input type="url" class="form-control @error('link') is-invalid @enderror" id="link" name="link" value="{{ old('link') }}" placeholder="https://...">
                        @error('link')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Se preenchido, o banner será clicável e redirecionará para este link.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="order" class="form-label">Ordem de exibição</label>
                        <input type="number" class="form-control @error('order') is-invalid @enderror" id="order" name="order" value="{{ old('order', 0) }}" min="0">
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Banners com menor número aparecem primeiro.</small>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Banner ativo</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-dark">
                <i class="fas fa-save me-2"></i>Salvar Banner
            </button>
        </div>
    </form>
@endsection

