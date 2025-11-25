@extends('layouts.admin')

@section('title', 'Editar Produto - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Produtos</span>
    <span><i class="fas fa-angle-right"></i> Editar</span>
@endsection
@section('admin-title', 'Editar produto')
@section('admin-subtitle', 'Atualize apenas imagem e markup; template e preço são automáticos.')

@section('admin-actions')
    <a href="{{ route('admin.products') }}" class="btn btn-dark">
        <i class="fas fa-arrow-left me-2"></i>Voltar para produtos
    </a>
@endsection

@section('admin-content')
    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body row g-4">
            <input type="hidden" name="price" value="0">
            <div class="col-12 col-md-6">
                <label for="markup_percentage" class="form-label">Markup específico (%)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('markup_percentage') is-invalid @enderror" id="markup_percentage" name="markup_percentage" value="{{ old('markup_percentage', $product->markup_percentage) }}">
                @error('markup_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-6">
                <label for="image" class="form-label">Imagem do produto</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($product->image)
                    <small class="text-muted d-block mt-2">Imagem atual:</small>
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-thumbnail mt-1" style="max-width: 160px;">
                @endif
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
    </form>
@endsection
