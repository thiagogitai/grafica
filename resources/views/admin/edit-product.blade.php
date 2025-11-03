@extends('layouts.admin')

@section('title', 'Editar Produto - Grafica Todah Serviços Fotográficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Produtos</span>
    <span><i class="fas fa-angle-right"></i> Editar</span>
@endsection
@section('admin-title', 'Editar produto')
@section('admin-subtitle', 'Atualize informações, template e comportamento do produto selecionado.')

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
            <div class="col-12 col-lg-6">
                <label for="name" class="form-label">Nome do produto</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-lg-6">
                <label for="template" class="form-label">Template de exibição</label>
                <select class="form-select @error('template') is-invalid @enderror" id="template" name="template" required>
                    @foreach($templates as $value => $label)
                        <option value="{{ $value }}" {{ old('template', $product->template) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Controle o layout e regras exibidas na vitrine.</small>
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Descrição</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $product->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @php
                $selectedTemplate = old('template', $product->template);
                $priceFieldHidden = ($disablePriceEditor ?? false)
                    || \Illuminate\Support\Str::startsWith($selectedTemplate, \App\Models\Product::TEMPLATE_CONFIG_PREFIX)
                    || $selectedTemplate === \App\Models\Product::TEMPLATE_FLYER;
            @endphp
            @if(!$priceFieldHidden)
                <div class="col-12 col-md-4">
                    <label for="price" class="form-label">Preço base</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}">
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Valor base utilizado quando não houver configuração automática.</small>
                </div>
            @else
                <input type="hidden" name="price" value="{{ old('price', $product->price) }}">
            @endif
            <div class="col-12 col-md-4">
                <label for="markup_percentage" class="form-label">Markup específico (%)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('markup_percentage') is-invalid @enderror" id="markup_percentage" name="markup_percentage" value="{{ old('markup_percentage', $product->markup_percentage) }}">
                @error('markup_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="image" class="form-label">Imagem do produto</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($product->image)
                    <small class="text-muted d-block mt-2">Imagem atual:</small>
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-thumbnail mt-1" style="max-width: 160px;">
                @endif
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="request_only" name="request_only" value="1" {{ old('request_only', $product->request_only) ? 'checked' : '' }}>
                    <label class="form-check-label" for="request_only">Ativar modo orçamento para este produto</label>
                    <small class="text-muted d-block">Quando ativo, oculta preços e direciona o cliente para solicitar orçamento.</small>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
    </form>
@endsection
