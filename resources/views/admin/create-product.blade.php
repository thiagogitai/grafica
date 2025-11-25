@extends('layouts.admin')

@section('title', 'Criar Produto - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Produtos</span>
    <span><i class="fas fa-angle-right"></i> Criar</span>
@endsection
@section('admin-title', 'Cadastrar novo produto')
@section('admin-subtitle', 'Defina apenas imagem e markup; template e preço são automáticos.')

@section('admin-actions')
    <a href="{{ route('admin.products') }}" class="btn btn-dark">
        <i class="fas fa-arrow-left me-2"></i>Voltar para produtos
    </a>
@endsection

@section('admin-content')
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body row g-4">
            <input type="hidden" name="price" value="0">
            <div class="col-12 col-md-6">
                <label for="markup_percentage" class="form-label">Markup específico (%)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('markup_percentage') is-invalid @enderror" id="markup_percentage" name="markup_percentage" value="{{ old('markup_percentage', 0) }}">
                @error('markup_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Adicional aplicado sobre o preço base (além do markup global).</small>
            </div>
            <div class="col-12 col-md-6">
                <label for="image" class="form-label">Imagem do produto</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Formatos aceitos: JPG, PNG ou GIF.</small>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar produto</button>
        </div>
    </form>
@endsection
