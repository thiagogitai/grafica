@extends('layouts.admin')

@section('admin-breadcrumb')
    <span><i class="fas fa-angle-right"></i> Configurações</span>
@endsection

@section('admin-title', 'Configurações do Site')
@section('admin-subtitle', 'Personalize o conteúdo público, integrações, redes sociais e depoimentos.')

@php
    $socialDefaults = [
        'instagram' => ['label' => 'Instagram', 'icon' => 'fab fa-instagram'],
        'facebook' => ['label' => 'Facebook', 'icon' => 'fab fa-facebook'],
        'linkedin' => ['label' => 'LinkedIn', 'icon' => 'fab fa-linkedin'],
        'youtube' => ['label' => 'YouTube', 'icon' => 'fab fa-youtube'],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'fab fa-tiktok'],
        'pinterest' => ['label' => 'Pinterest', 'icon' => 'fab fa-pinterest'],
        'behance' => ['label' => 'Behance', 'icon' => 'fab fa-behance'],
        'twitter' => ['label' => 'Twitter / X', 'icon' => 'fab fa-x-twitter'],
    ];
    $socialLinks = old('social_links', $settings['social_links'] ?? []);
    if (!is_array($socialLinks)) {
        $decodedSocial = json_decode($socialLinks, true);
        $socialLinks = is_array($decodedSocial) ? $decodedSocial : [];
    }
    $testimonialsData = old('testimonials', $settings['testimonials'] ?? []);
    if (!is_array($testimonialsData)) {
        $decodedTestimonials = json_decode($testimonialsData, true);
        $testimonialsData = is_array($decodedTestimonials) ? $decodedTestimonials : [];
    }
    $testimonialCount = max(count($testimonialsData), 3);
@endphp

@section('admin-content')
    @if (session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
        @csrf
        @method('POST')

        <ul class="nav nav-pills admin-tabs flex-column flex-md-row gap-2 mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-hero-link" data-bs-toggle="pill" data-bs-target="#tab-hero" type="button" role="tab">
                    Conteúdo principal
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-contact-link" data-bs-toggle="pill" data-bs-target="#tab-contact" type="button" role="tab">
                    Contato & redes sociais
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-about-link" data-bs-toggle="pill" data-bs-target="#tab-about" type="button" role="tab">
                    Sobre & conteúdos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-testimonials-link" data-bs-toggle="pill" data-bs-target="#tab-testimonials" type="button" role="tab">
                    Depoimentos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-integrations-link" data-bs-toggle="pill" data-bs-target="#tab-integrations" type="button" role="tab">
                    Integrações
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabsContent">
            <div class="tab-pane fade show active" id="tab-hero" role="tabpanel" aria-labelledby="tab-hero-link">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Informações da página inicial</h5>
                    </div>
                    <div class="card-body row g-4">
                        <div class="col-12">
                            <label for="hero_title" class="form-label">Título principal</label>
                            <input type="text" class="form-control @error('hero_title') is-invalid @enderror" id="hero_title" name="hero_title" value="{{ old('hero_title', $settings['hero_title'] ?? '') }}" required>
                            @error('hero_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="hero_subtitle" class="form-label">Subtítulo</label>
                            <textarea class="form-control @error('hero_subtitle') is-invalid @enderror" id="hero_subtitle" name="hero_subtitle" rows="3" required>{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
                            @error('hero_subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Esse conteúdo aparece em destaque na página inicial do site.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-contact" role="tabpanel" aria-labelledby="tab-contact-link">
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Contato</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="request_only" name="request_only" value="1" {{ old('request_only', !empty($settings['request_only']) && $settings['request_only']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="request_only">Exibir produtos apenas para orçamento (oculta preços)</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="disable_price_editor" name="disable_price_editor" value="1" {{ old('disable_price_editor', !empty($settings['disable_price_editor']) && $settings['disable_price_editor']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="disable_price_editor">Desativar edição manual de preços</label>
                                    <small class="text-muted d-block">Quando ativo, os preços exibidos virão apenas dos templates/JSON configurados.</small>
                                </div>
                                <div class="mt-3">
                                    <label for="footer_text" class="form-label">Texto do rodapé</label>
                                    <textarea class="form-control @error('footer_text') is-invalid @enderror" id="footer_text" name="footer_text" rows="3">{{ old('footer_text', $settings['footer_text'] ?? '') }}</textarea>
                                    @error('footer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">Use para slogan, direitos autorais ou informações adicionais no rodapé.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Redes sociais</h5>
                                <small class="text-muted">Links exibidos no topo e rodapé.</small>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach($socialDefaults as $key => $meta)
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label" for="social_{{ $key }}">{{ $meta['label'] }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="{{ $meta['icon'] }}"></i></span>
                                                <input type="text" id="social_{{ $key }}" name="social_links[{{ $key }}]" class="form-control @error('social_links.' . $key) is-invalid @enderror" placeholder="https://..." value="{{ old('social_links.' . $key, $socialLinks[$key] ?? '') }}">
                                                @error('social_links.' . $key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-3">Deixe em branco para ocultar uma rede social.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-about" role="tabpanel" aria-labelledby="tab-about-link">
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Seção "Sobre nós"</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="about_title" class="form-label">Título</label>
                                    <input type="text" class="form-control @error('about_title') is-invalid @enderror" id="about_title" name="about_title" value="{{ old('about_title', $settings['about_title'] ?? '') }}" required>
                                    @error('about_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="about_description" class="form-label">Descrição</label>
                                    <textarea class="form-control @error('about_description') is-invalid @enderror" id="about_description" name="about_description" rows="5" required>{{ old('about_description', $settings['about_description'] ?? '') }}</textarea>
                                    @error('about_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Recursos (destaques)</h5>
                            </div>
                            <div class="card-body">
                                <label for="features" class="form-label">Lista de recursos (JSON)</label>
                                <textarea class="form-control @error('features') is-invalid @enderror" id="features" name="features" rows="8">{{ old('features', $settings['features'] ?? '') }}</textarea>
                                @error('features')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Formato sugerido: <code>[{"icon": "fas fa-award", "title": "...", "text": "..."}]</code>.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-testimonials" role="tabpanel" aria-labelledby="tab-testimonials-link">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Depoimentos</h5>
                        <small class="text-muted">Inclua nome, cargo, mensagem e faça upload da foto.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            @for($i = 0; $i < $testimonialCount; $i++)
                                @php $testimonial = $testimonialsData[$i] ?? []; @endphp
                                <div class="col-12 col-lg-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <h6 class="fw-semibold mb-3">Depoimento #{{ $i + 1 }}</h6>
                                        <div class="mb-3">
                                            <label for="testimonial_name_{{ $i }}" class="form-label">Nome</label>
                                            <input type="text" id="testimonial_name_{{ $i }}" name="testimonials[{{ $i }}][name]" class="form-control @error('testimonials.' . $i . '.name') is-invalid @enderror" value="{{ old('testimonials.' . $i . '.name', $testimonial['name'] ?? '') }}">
                                            @error('testimonials.' . $i . '.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="testimonial_role_{{ $i }}" class="form-label">Cargo / Empresa</label>
                                            <input type="text" id="testimonial_role_{{ $i }}" name="testimonials[{{ $i }}][role]" class="form-control @error('testimonials.' . $i . '.role') is-invalid @enderror" value="{{ old('testimonials.' . $i . '.role', $testimonial['role'] ?? '') }}">
                                            @error('testimonials.' . $i . '.role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="testimonial_image_{{ $i }}" class="form-label">Foto do cliente</label>
                                            @php
                                                $storedImage = old('testimonials.' . $i . '.existing_image', $testimonial['image'] ?? '');
                                            @endphp
                                            <input type="hidden" name="testimonials[{{ $i }}][existing_image]" value="{{ $storedImage }}">
                                            @if($storedImage)
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <img src="{{ filter_var($storedImage, FILTER_VALIDATE_URL) ? $storedImage : asset('storage/' . ltrim($storedImage, '/')) }}" alt="Foto atual do cliente" class="rounded-circle border" width="60" height="60">
                                                    <span class="text-muted small">Foto atual</span>
                                                </div>
                                            @endif
                                            <input type="file" id="testimonial_image_{{ $i }}" name="testimonials[{{ $i }}][image_file]" class="form-control @error('testimonials.' . $i . '.image_file') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                                            @error('testimonials.' . $i . '.image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            <small class="text-muted d-block mt-1">Formatos aceitos: JPG, PNG ou WEBP (máx. 2MB).</small>
                                        </div>
                                        <div>
                                            <label for="testimonial_text_{{ $i }}" class="form-label">Depoimento</label>
                                            <textarea id="testimonial_text_{{ $i }}" name="testimonials[{{ $i }}][text]" rows="3" class="form-control @error('testimonials.' . $i . '.text') is-invalid @enderror">{{ old('testimonials.' . $i . '.text', $testimonial['text'] ?? '') }}</textarea>
                                            @error('testimonials.' . $i . '.text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <small class="text-muted d-block mt-3">Campos em branco são ignorados. Para adicionar mais depoimentos, preencha os slots vazios.</small>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-integrations" role="tabpanel" aria-labelledby="tab-integrations-link">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Integrações</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small mb-3">Mercado Pago</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label for="mercadopago_public_key" class="form-label">Public Key</label>
                                <input type="text" class="form-control @error('mercadopago_public_key') is-invalid @enderror" id="mercadopago_public_key" name="mercadopago_public_key" value="{{ old('mercadopago_public_key', $settings['mercadopago_public_key'] ?? '') }}">
                                @error('mercadopago_public_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="mercadopago_access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control @error('mercadopago_access_token') is-invalid @enderror" id="mercadopago_access_token" name="mercadopago_access_token" value="{{ old('mercadopago_access_token', $settings['mercadopago_access_token'] ?? '') }}">
                                @error('mercadopago_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Use suas credenciais de produção do Mercado Pago.</small>
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted small mb-3">Loggi (transporte)</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="loggi_api_token" class="form-label">API Token</label>
                                <input type="text" class="form-control @error('loggi_api_token') is-invalid @enderror" id="loggi_api_token" name="loggi_api_token" value="{{ old('loggi_api_token', $settings['loggi_api_token'] ?? '') }}">
                                @error('loggi_api_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="loggi_account_id" class="form-label">Account ID / Código da conta</label>
                                <input type="text" class="form-control @error('loggi_account_id') is-invalid @enderror" id="loggi_account_id" name="loggi_account_id" value="{{ old('loggi_account_id', $settings['loggi_account_id'] ?? '') }}">
                                @error('loggi_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Credenciais fornecidas pela Loggi para cálculo e rastreio.</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-uppercase text-muted small mb-3">Frete / Orçamento</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="shipping_provider" class="form-label">Modo de frete</label>
                                <select class="form-select @error('shipping_provider') is-invalid @enderror" id="shipping_provider" name="shipping_provider">
                                    <option value="orcamento" {{ old('shipping_provider', $settings['shipping_provider'] ?? 'orcamento') === 'orcamento' ? 'selected' : '' }}>Somente orçamento (calcular depois)</option>
                                    <option value="correios" {{ old('shipping_provider', $settings['shipping_provider'] ?? 'orcamento') === 'correios' ? 'selected' : '' }}>Correios (contrato)</option>
                                    <option value="loggi" {{ old('shipping_provider', $settings['shipping_provider'] ?? 'orcamento') === 'loggi' ? 'selected' : '' }}>Loggi</option>
                                    <option value="melhorenvio" {{ old('shipping_provider', $settings['shipping_provider'] ?? 'orcamento') === 'melhorenvio' ? 'selected' : '' }}>Melhor Envio</option>
                                </select>
                                @error('shipping_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted d-block">Escolha se o frete será calculado via API ou mantido como orçamento.</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <label for="correios_user" class="form-label">Correios: Usuário</label>
                                <input type="text" class="form-control @error('correios_user') is-invalid @enderror" id="correios_user" name="correios_user" value="{{ old('correios_user', $settings['correios_user'] ?? '') }}">
                                @error('correios_user')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="correios_password" class="form-label">Correios: Senha</label>
                                <input type="text" class="form-control @error('correios_password') is-invalid @enderror" id="correios_password" name="correios_password" value="{{ old('correios_password', $settings['correios_password'] ?? '') }}">
                                @error('correios_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="correios_contract" class="form-label">Correios: Cód./Contrato</label>
                                <input type="text" class="form-control @error('correios_contract') is-invalid @enderror" id="correios_contract" name="correios_contract" value="{{ old('correios_contract', $settings['correios_contract'] ?? '') }}">
                                @error('correios_contract')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="melhorenvio_token" class="form-label">Melhor Envio: Token</label>
                                <input type="text" class="form-control @error('melhorenvio_token') is-invalid @enderror" id="melhorenvio_token" name="melhorenvio_token" value="{{ old('melhorenvio_token', $settings['melhorenvio_token'] ?? '') }}">
                                @error('melhorenvio_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="melhorenvio_secret" class="form-label">Melhor Envio: Secret</label>
                                <input type="text" class="form-control @error('melhorenvio_secret') is-invalid @enderror" id="melhorenvio_secret" name="melhorenvio_secret" value="{{ old('melhorenvio_secret', $settings['melhorenvio_secret'] ?? '') }}">
                                @error('melhorenvio_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-4">Salvar configurações</button>
        </div>
    </form>
@endsection




