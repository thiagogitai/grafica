@extends('layouts.app')

@section('title', 'Gráfica Todah Serviços Gráficos')

@section('body-class', 'home-page')

@section('content')

@php
    $requestOnlyGlobal = $requestOnlyGlobal ?? ($settings['request_only'] ?? false);
@endphp

<!-- Hero Section -->
<section class="hero-section text-center bg-light d-flex align-items-center justify-content-center">
    <div class="container">
        <h1 class="display-4 fw-bold">{{ $settings['hero_title'] ?? 'A sua gráfica online' }}</h1>
        <p class="lead text-muted">{{ $settings['hero_subtitle'] ?? 'Tudo o que precisa para o seu negócio. Rápido. Simples. A um preço justo.' }}</p>
        @if(!empty($settings['whatsapp_number']))
            <a class="btn btn-success mt-3" href="https://wa.me/{{ preg_replace('/\D/','',$settings['whatsapp_number']) }}" target="_blank">
                <i class="fab fa-whatsapp me-2"></i> Fale no WhatsApp
            </a>
        @endif
    </div>
    </section>

<!-- Product Categories -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Navegue por categoria</h2>
        </div>
        <div class="row g-4">
            @foreach($categories->take(4) as $category)
            <div class="col-lg-3 col-md-4 col-sm-6">
                <a href="#" class="card text-decoration-none text-dark category-card">
                    @if($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}" class="card-img-top" alt="{{ $category->name }}">
                    @else
                        <img src="https://source.unsplash.com/250x150/?graphic-design,{{ urlencode($category->name) }}" class="card-img-top" alt="{{ $category->name }}">
                    @endif
                    <div class="card-body text-center">
                        <h5 class="card-title">{{ $category->name }}</h5>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    </section>

<!-- Featured Products -->
<section id="produtos" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Produtos mais vendidos</h2>
            <p class="text-muted">Os favoritos dos nossos clientes</p>
        </div>
        <div class="row g-4">
            @forelse($products->take(4) as $product)
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 product-card">
                        @php
                            $productImage = '';
                            if ($product->templateType() === \App\Models\Product::TEMPLATE_FLYER || preg_match('/impressão em papel a4/i', $product->name)) {
                                $productImage = 'folder.png';
                            } elseif (preg_match('/cartão/i', $product->name)) {
                                $productImage = 'cartao.png';
                            } elseif (preg_match('/livro/i', $product->name)) {
                                $productImage = 'livro.png';
                            } elseif (preg_match('/banner/i', $product->name)) {
                                $productImage = 'banner.png';
                            }
                            $usesConfigTemplate = method_exists($product, 'usesConfigTemplate') ? $product->usesConfigTemplate() : false;
                            $isFlyerTemplate = $product->templateType() === \App\Models\Product::TEMPLATE_FLYER;
                            $shouldHidePrice = ($requestOnlyGlobal ?? false) || $product->request_only || $usesConfigTemplate || $isFlyerTemplate;
                        @endphp
                        @if($productImage)
                            <img src="{{ asset($productImage) }}" class="card-img-top" alt="{{ $product->name }}">
                        @elseif($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="card-img-top" alt="{{ $product->name }}">
                        @else
                            <img src="https://source.unsplash.com/300x200/?{{ urlencode($product->name) }}" class="card-img-top" alt="{{ $product->name }}">
                        @endif
                        <div class="card-body d-flex flex-column">
                            @if($product->templateType() === \App\Models\Product::TEMPLATE_FLYER || preg_match('/impressão em papel a4/i', $product->name))
                                <h5 class="card-title fw-bold">impressão de flyer/panfleto</h5>
                                <p class="card-text small text-muted">{{ Str::limit($product->description, 80) }}</p>
                                <div class="mt-auto">
                                    @if($shouldHidePrice)
                                        <p class="text-muted small mb-2"> Solicite um orçamento e receba os valores personalizados.</p>
                                    @else
                                        <span class="h5 text-primary fw-bold price-tag">A partir de...</span>
                                    @endif
                                    <div class="d-grid mt-2">
                                        <a href="{{ route('product.show', $product) }}" class="btn btn-primary">
                                            {{ $shouldHidePrice ? 'Ver detalhes' : 'Ver opções' }}
                                        </a>
                                    </div>
                                </div>
                            @else
                                <h5 class="card-title fw-bold">{{ $product->name }}</h5>
                                <p class="card-text small text-muted">{{ Str::limit($product->description, 80) }}</p>
                                <div class="mt-auto">
                                    @if($shouldHidePrice)
                                        <p class="text-muted small mb-2">Solicite um orçamento personalizado com nossa equipe.</p>
                                        <div class="d-grid">
                                            <a href="{{ route('product.show', $product) }}" class="btn btn-outline-primary">Ver detalhes</a>
                                        </div>
                                    @else
                                        <span class="h5 text-primary fw-bold price-tag">R$ {{ number_format($product->price, 2, ',', '.') }}</span>
                                        <div class="d-grid mt-2">
                                            <a href="{{ route('product.show', $product) }}" class="btn btn-primary">Ver produto</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">Nenhum produto disponível no momento.</div>
                </div>
            @endforelse
        </div>
    </div>
    </section>

<!-- Why Choose Us (dynamic) -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ $settings['about_title'] ?? 'Por que escolher a Gráfica?' }}</h2>
        </div>
        @php $features = $settings['features'] ?? []; @endphp
        @if(!empty($features))
            <div class="row g-4 text-center">
                @foreach($features as $f)
                    <div class="col-md-3">
                        <div class="feature-icon mb-3">
                            @if(!empty($f['icon']))
                                <i class="{{ $f['icon'] }} fa-3x text-primary"></i>
                            @else
                                <i class="fas fa-check fa-3x text-primary"></i>
                            @endif
                        </div>
                        @if(!empty($f['title']))
                            <h5 class="fw-bold">{{ $f['title'] }}</h5>
                        @endif
                        @if(!empty($f['text']))
                            <p class="text-muted">{{ $f['text'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-award fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Qualidade Superior</h5>
                    <p class="text-muted">Impressão de alta definição para resultados profissionais.</p>
                </div>
                <div class="col-md-3">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-truck fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Entrega Rápida</h5>
                    <p class="text-muted">Receba seus produtos onde e quando precisar.</p>
                </div>
                <div class="col-md-3">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-headset fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Apoio ao Cliente</h5>
                    <p class="text-muted">Nossa equipe está sempre pronta para ajudar.</p>
                </div>
                <div class="col-md-3">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-check-circle fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Satisfação Garantida</h5>
                    <p class="text-muted">Compromisso com a sua satisfação total.</p>
                </div>
            </div>
        @endif
    </div>
    </section>

<!-- Testimonials (dynamic) -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">O que dizem os nossos clientes</h2>
        </div>
        @php $testimonials = $settings['testimonials'] ?? []; @endphp
        <div class="row">
            @forelse($testimonials as $t)
                @php
                    $avatar = $t['image'] ?? null;
                    if ($avatar) {
                        $avatar = filter_var($avatar, FILTER_VALIDATE_URL) ? $avatar : asset('storage/' . ltrim($avatar, '/'));
                    } else {
                        $avatar = 'https://via.placeholder.com/50';
                    }
                @endphp
                <div class="col-md-4">
                    <div class="card testimonial-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ $avatar }}" class="rounded-circle me-3" alt="{{ $t['name'] ?? 'Cliente' }}" width="50" height="50">
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $t['name'] ?? '' }}</h6>
                                    <div class="text-warning">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text">{{ $t['text'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted text-center">Sem depoimentos no momento.</p></div>
            @endforelse
        </div>
    </div>
    </section>

@endsection
