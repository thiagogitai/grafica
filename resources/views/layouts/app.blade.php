 <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gráfica Todah - Gráfica Online')</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <header>
        <!-- Top bar -->
        <div class="top-bar bg-light border-bottom py-2 small">
            <div class="container d-flex justify-content-between align-items-center">
                <div class="d-flex gap-3">
                    <a href="#" class="text-muted">Atendimento ao Cliente</a>
                </div>
                <div class="d-flex align-items-center">
                    @php $wa = \App\Models\Setting::get('whatsapp_number'); @endphp
                    @if(!empty($wa))
                        <a class="nav-link me-3 text-success" href="https://wa.me/{{ preg_replace('/\\D/','',$wa) }}" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                        </a>
                        <span class="text-muted mx-1">|</span>
                    @endif
                    @auth
                        @if(auth()->user()->is_admin)
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-cog me-1"></i>Admin
                            </a>
                            <span class="text-muted mx-1">|</span>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link p-0">
                                <i class="fas fa-sign-out-alt me-1"></i>Sair
                            </button>
                        </form>
                    @else
                        <a class="nav-link" href="{{ url('/login') }}">
                            <i class="fas fa-sign-in-alt me-1"></i>Entrar
                        </a>
                        <span class="text-muted mx-1">/</span>
                        <a class="nav-link" href="{{ url('/register') }}">
                            <i class="fas fa-user-plus me-1"></i>Cadastrar
                        </a>
                    @endauth
                </div>
            </div>
        </div>

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
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            @if(session('cart') && count(session('cart')) > 0)
                                <span class="badge bg-warning text-dark rounded-pill position-absolute top-0 start-100 translate-middle">{{ count(session('cart')) }}</span>
                            @endif
                        </a>
                    </li>
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
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5>COMO FUNCIONA</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none small">Processo de Compra</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Prazos de Entrega</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Opções de Pagamento</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>SOBRE NÓS</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none small">Quem Somos</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Sustentabilidade</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Trabalhe Conosco</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>SERVIÇO AO CLIENTE</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none small">Contato</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Perguntas Frequentes</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Política de Devolução</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>AVISOS LEGAIS</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none small">Termos e Condições</a></li>
                        <li><a href="#" class="text-white text-decoration-none small">Aviso de Privacidade</a></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-4 align-items-center">
                <div class="col-md-6">
                    <h5>Siga-nos</h5>
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook fa-2x"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram fa-2x"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin fa-2x"></i></a>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 small">&copy; 2025 Gráfica Todah. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
    @php $waDynamic = \App\Models\Setting::get('whatsapp_number'); @endphp
    @if(!empty($waDynamic))
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var el = document.querySelector('a.whatsapp-float');
        if (el) { el.setAttribute('href', 'https://wa.me/{{ preg_replace('/\\D/',',$wa) }} preg_replace('/\\D/','',$waDynamic) }}'); }
    });
    </script>
    @endif

    @php $wa = \\App\\Models\\Setting::get('whatsapp_number'); @endphp\n    @if(!empty($wa))\n    <a href="https://wa.me/{{ preg_replace('/\\D/',',$wa) }}"" class="whatsapp-float" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>\n    @endif
</body>
</html>


