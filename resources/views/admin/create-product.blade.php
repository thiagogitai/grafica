@extends('layouts.admin')

@section('title', 'Criar Produto - Gráfica Todah Serviços Gráficos')
@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Produtos</span>
    <span><i class="fas fa-angle-right"></i> Criar</span>
@endsection
@section('admin-title', 'Cadastrar novo produto')
@section('admin-subtitle', 'Defina as informações básicas, escolha o template e ajuste o comportamento de orçamento.')

@section('admin-actions')
    <a href="{{ route('admin.products') }}" class="btn btn-dark">
        <i class="fas fa-arrow-left me-2"></i>Voltar para produtos
    </a>
@endsection

@section('admin-content')
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body row g-4">
            <div class="col-12 col-lg-6">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome do produto</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="mb-3">
                    <label for="template" class="form-label">Template de exibição</label>
                    <select class="form-select @error('template') is-invalid @enderror" id="template" name="template" required>
                        @foreach($templates as $value => $label)
                            <option value="{{ $value }}" {{ old('template', \App\Models\Product::TEMPLATE_STANDARD) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Escolha o layout/comportamento que será aplicado na vitrine do site.</small>
                </div>
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Descrição</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @php
                $selectedTemplate = old('template', \App\Models\Product::TEMPLATE_STANDARD);
                $priceFieldHidden = ($disablePriceEditor ?? false)
                    || \Illuminate\Support\Str::startsWith($selectedTemplate, \App\Models\Product::TEMPLATE_CONFIG_PREFIX)
                    || $selectedTemplate === \App\Models\Product::TEMPLATE_FLYER;
            @endphp
            @if(!$priceFieldHidden)
                <div class="col-12 col-md-4">
                    <label for="price" class="form-label">Preço base</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}">
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Valor inicial utilizado em produtos sem configuração automática.</small>
                </div>
            @else
                <input type="hidden" name="price" value="{{ old('price', 0) }}">
            @endif
            <div class="col-12 col-md-4">
                <label for="markup_percentage" class="form-label">Markup específico (%)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('markup_percentage') is-invalid @enderror" id="markup_percentage" name="markup_percentage" value="{{ old('markup_percentage', 0) }}">
                @error('markup_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Adicional aplicado sobre o preço base (além do markup global).</small>
            </div>
            <div class="col-12 col-md-4">
                <label for="image" class="form-label">Imagem do produto</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Formatos aceitos: JPG, PNG ou GIF.</small>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="request_only" name="request_only" value="1" {{ old('request_only') ? 'checked' : '' }}>
                    <label class="form-check-label" for="request_only">Ativar modo orçamento para este produto</label>
                    <small class="text-muted d-block">Quando ativo, o produto não exibe preços e direciona o cliente para solicitar orçamento.</small>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar produto</button>
        </div>
    </form>
@endsection
