@extends('layouts.app')

@section('title', $product->name ?? 'Produto')

@section('content')
<div class="container py-4 py-lg-5">
    @if(isset($banners) && $banners->count() > 0)
        <div class="banners-section mb-4">
            <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($banners as $index => $banner)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            @if($banner->link)
                                <a href="{{ $banner->link }}" target="_blank">
                                    <img src="{{ asset('storage/' . $banner->image) }}" class="d-block w-100" alt="{{ $banner->title ?? 'Banner' }}" style="max-height: 400px; object-fit: cover; border-radius: 12px;">
                                </a>
                            @else
                                <img src="{{ asset('storage/' . $banner->image) }}" class="d-block w-100" alt="{{ $banner->title ?? 'Banner' }}" style="max-height: 400px; object-fit: cover; border-radius: 12px;">
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($banners->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Próximo</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->description }}</p>
            
            @if($requestOnly ?? false)
                <div class="alert alert-warning">
                    Este produto está disponível apenas mediante solicitação. Fale com nossa equipe para prosseguir.
                </div>
            @else
                <p>Configuração do produto será exibida aqui.</p>
            @endif
        </div>
    </div>
</div>
@endsection

