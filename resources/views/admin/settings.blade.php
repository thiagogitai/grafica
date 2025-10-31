@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Configurações do Site</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('POST') {{-- Laravel usa POST para updateSettings, mas o método HTTP é POST --}}

        <div class="card mb-3">
            <div class="card-header">
                Informações da Página Principal (Hero Section)
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="hero_title">Título Principal</label>
                    <input type="text" class="form-control" id="hero_title" name="hero_title" value="{{ old('hero_title', $settings['hero_title'] ?? '') }}" required>
                    @error('hero_title')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="hero_subtitle">Subtítulo Principal</label>
                    <textarea class="form-control" id="hero_subtitle" name="hero_subtitle" rows="3" required>{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
                    @error('hero_subtitle')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Configurações de Contato e Preço
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="whatsapp_number">Número do WhatsApp (com DDD, sem espaços ou caracteres especiais)</label>
                    <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" required>
                    @error('whatsapp_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="price_percentage">Porcentagem de Acréscimo nos Preços (0-100)</label>
                    <input type="number" class="form-control" id="price_percentage" name="price_percentage" value="{{ old('price_percentage', $settings['price_percentage'] ?? '') }}" min="0" max="100" step="0.01" required>
                    @error('price_percentage')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Seção "Sobre Nós"
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="about_title">Título "Sobre Nós"</label>
                    <input type="text" class="form-control" id="about_title" name="about_title" value="{{ old('about_title', $settings['about_title'] ?? '') }}" required>
                    @error('about_title')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="about_description">Descrição "Sobre Nós"</label>
                    <textarea class="form-control" id="about_description" name="about_description" rows="5" required>{{ old('about_description', $settings['about_description'] ?? '') }}</textarea>
                    @error('about_description')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Recursos (JSON)
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="features">Recursos (JSON Array de Objetos com 'icon' e 'text')</label>
                    <textarea class="form-control" id="features" name="features" rows="5" required>{{ old('features', $settings['features'] ?? '') }}</textarea>
                    @error('features')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Depoimentos (JSON)
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="testimonials">Depoimentos (JSON Array de Objetos com 'name', 'text' e 'image')</label>
                    <textarea class="form-control" id="testimonials" name="testimonials" rows="5" required>{{ old('testimonials', $settings['testimonials'] ?? '') }}</textarea>
                    @error('testimonials')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
    </form>
</div>
@endsection
