@extends('layouts.admin')

@section('title', 'Nova Categoria - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Categorias</span>
    <span><i class="fas fa-angle-right"></i> Nova</span>
@endsection
@section('admin-title', 'Criar nova categoria')
@section('admin-subtitle', 'Defina nome, descrição e imagem para organizar o catálogo.')

@section('admin-actions')
    <a href="{{ route('admin.categories') }}" class="btn btn-dark">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
@endsection

@section('admin-content')
    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body row g-4">
            <div class="col-12 col-lg-6">
                <label for="name" class="form-label">Nome</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-lg-6">
                <label for="image" class="form-label">Imagem (opcional)</label>
                <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Descrição</label>
                <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.categories') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar categoria</button>
        </div>
    </form>
@endsection
