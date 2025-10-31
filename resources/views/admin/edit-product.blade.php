@extends('layouts.admin')

@section('title', 'Editar Produto - Gráfica Todah')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-primary text-white py-4">
                    <h2 class="mb-0 text-center">
                        <i class="fas fa-edit me-2"></i>Editar Produto
                    </h2>
                </div>

                <div class="card-body p-5">
                    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            <div class="col-12">
                                <label for="name" class="form-label fw-bold">Nome do Produto *</label>
                                <input type="text" class="form-control form-control-lg rounded-pill" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label fw-bold">Descrição *</label>
                                <textarea class="form-control rounded-4" id="description" name="description" rows="4" required>{{ old('description', $product->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="price" class="form-label fw-bold">Preço (R$) *</label>
                                <input type="number" class="form-control form-control-lg rounded-pill" id="price" name="price" step="0.01" min="0" value="{{ old('price', $product->price) }}" required>
                                @error('price')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="image" class="form-label fw-bold">Imagem do Produto</label>
                                <input type="file" class="form-control form-control-lg rounded-pill" id="image" name="image" accept="image/*">
                                <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB</div>
                                @error('image')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror

                                @if($product->image)
                                    <div class="mt-3">
                                        <label class="form-label">Imagem Atual:</label>
                                        <div>
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-width: 200px;">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5">
                                <i class="fas fa-save me-2"></i>Atualizar Produto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
