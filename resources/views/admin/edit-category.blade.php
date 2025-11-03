@extends('layouts.admin')

@section('title', 'Editar Categoria - Grafica Todah Serviços Fotográficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Categorias</span>
    <span><i class="fas fa-angle-right"></i> Editar</span>
@endsection
@section('admin-title', 'Editar categoria')
@section('admin-subtitle', 'Atualize nome, descrição e imagem da categoria selecionada.')

@section('admin-actions')
    <a href="{{ route('admin.categories') }}" class="btn btn-dark">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
@endsection

@section('admin-content')
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body row g-4">
            <div class="col-12 col-lg-6">
                <label for="name" class="form-label">Nome</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-lg-6">
                <label for="image" class="form-label">Imagem (opcional)</label>
                <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($category->image)
                    <small class="text-muted d-block mt-2">Imagem atual:</small>
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="img-thumbnail mt-1" style="max-width: 160px;">
                @endif
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Descrição</label>
                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $category->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.categories') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
    </form>
@endsection
