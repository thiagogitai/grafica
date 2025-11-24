 <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gráfica Todah Serviços Gráficos')</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    @include('partials.vite-assets')
    @stack('styles')
    <style>
        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 3px #999;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .whatsapp-float:hover {
            background-color: #128C7E;
        }
    </style>
</head>
<body class="@yield('body-class')">
    @php
        $socialIconMap = [
            'instagram' => ['label' => 'Instagram', 'icon' => 'fab fa-instagram'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'fab fa-facebook'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'fab fa-linkedin'],
            'youtube' => ['label' => 'YouTube', 'icon' => 'fab fa-youtube'],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'fab fa-tiktok'],
            'pinterest' => ['label' => 'Pinterest', 'icon' => 'fab fa-pinterest'],
            'behance' => ['label' => 'Behance', 'icon' => 'fab fa-behance'],
            'twitter' => ['label' => 'Twitter / X', 'icon' => 'fab fa-x-twitter'],
            'email' => ['label' => 'E-mail', 'icon' => 'fas fa-envelope'],
            'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'fab fa-whatsapp'],
        ];
    @endphp

    <header>
        <!-- Top bar -->
        <div class="top-bar bg-light border-bottom py-2 small">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted d-none d-sm-inline">Atendimento ao cliente</span>
                    @if(!empty($globalSocialLinks))
                        <div class="d-flex align-items-center gap-2">
                            @foreach($socialIconMap as $network => $meta)
                                @php $url = $globalSocialLinks[$network] ?? null; @endphp
                                @if(!empty($url))
                                    <a href="{{ $url }}" class="text-muted" target="_blank" aria-label="{{ $meta['label'] }}">
                                        <i class="{{ $meta['icon'] }}"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-end">
                    @if(!empty($globalWhatsappLink))
                        <a class="nav-link text-success" href="{{ $globalWhatsappLink }}" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    @endif
                    @auth
                        <div class="d-flex align-items-center gap-3">
                            <a class="nav-link" href="{{ route('customer.dashboard') }}">
                                <i class="fas fa-user-circle me-1"></i>Minha conta
                            </a>
                            @if(auth()->user()->is_admin)
                                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-cog me-1"></i>Admin
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link p-0">
                                    <i class="fas fa-sign-out-alt me-1"></i>Sair
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-3">
                            <a class="nav-link" href="{{ url('/login') }}">
                                <i class="fas fa-sign-in-alt me-1"></i>Entrar
                            </a>
                            <a class="nav-link" href="{{ url('/register') }}">
                                <i class="fas fa-user-plus me-1"></i>Cadastrar
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        @if($requestOnlyMode)
            <div class="bg-warning-subtle border-bottom py-2">
                <div class="container text-center small text-muted">
                    Estamos operando no modo orçamento. Solicite um orçamento pelo WhatsApp ou formulário de contato.
                </div>
            </div>
        @endif

        <!-- Main Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                @include('components.logo')
                <div class="d-flex flex-grow-1 justify-content-center">
                    <form class="d-flex w-75" method="GET" action="#">
                        <input class="form-control me-2" type="search" name="search" placeholder="O que você procura?" aria-label="Search">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <ul class="navbar-nav">
                    @if(!$requestOnlyMode)
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                                <i class="fas fa-shopping-cart fa-lg"></i>
                                @if(session('cart') && count(session('cart')) > 0)
                                    <span class="badge bg-warning text-dark rounded-pill position-absolute top-0 start-100 translate-middle">{{ count(session('cart')) }}</span>
                                @endif
                            </a>
                        </li>
                    @elseif(!empty($globalWhatsappLink))
                        <li class="nav-item">
                            <a class="btn btn-outline-primary ms-lg-3" href="{{ $globalWhatsappLink }}" target="_blank">
                                <i class="fab fa-whatsapp me-1"></i> Solicitar orçamento
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </nav>

        <!-- Category Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-top">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#categoryNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="categoryNav">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Todos os produtos
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <!-- Categories will be populated dynamically -->
                                <li><a class="dropdown-item" href="#">Cartões de Visita</a></li>
                                <li><a class="dropdown-item" href="#">Folhetos e Flyers</a></li>
                                <li><a class="dropdown-item" href="#">Banners</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#">Os Mais Vendidos</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Novidades</a></li>
                        <li class="nav-item"><a class="nav-link text-danger" href="#">Promoções</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-0" role="alert" style="background-color: #FFD700; color: #000; border-color: #FFD700;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <main class="py-4">
        @yield('content')
    </main>

    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6">
                    <h5 class="text-uppercase fw-bold mb-3">Gráfica Todah Serviços Gráficos</h5>
                    <p class="mb-3 small">{{ $globalFooterText ?: 'Atualize este texto nas configurações para reforçar a mensagem da sua marca.' }}</p>
                    @if(!empty($globalWhatsappLink))
                        <a class="btn btn-outline-light btn-sm" href="{{ $globalWhatsappLink }}" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i> Fale conosco
                        </a>
                    @endif
                </div>
                <div class="col-md-6 text-md-end">
                    @if(!empty($globalSocialLinks))
                        <h6 class="text-uppercase fw-bold mb-3">Redes sociais</h6>
                        <div class="d-flex justify-content-md-end gap-3">
                            @foreach($socialIconMap as $network => $meta)
                                @php $url = $globalSocialLinks[$network] ?? null; @endphp
                                @if(!empty($url))
                                    <a href="{{ $url }}" class="text-white-50 fs-4" target="_blank" aria-label="{{ $meta['label'] }}">
                                        <i class="{{ $meta['icon'] }}"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <p class="mb-0 small mt-3">&copy; 2025 Gráfica Todah Serviços Gráficos. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
    @if(!empty($globalWhatsappLink))
        <a href="{{ $globalWhatsappLink }}" class="whatsapp-float" target="_blank" title="Fale conosco no WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    @endif
</body>
</html>
