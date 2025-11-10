<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <p class="text-uppercase text-warning fw-bold small mb-2">
                        Template em atualização
                    </p>
                    <h1 class="h3 fw-bold mb-3">
                        Estamos migrando este produto para a nova experiência
                    </h1>
                    <p class="text-muted mb-4">
                        Replicaremos em breve o mesmo fluxo e a mesma API oficial já ativa na calculadora de
                        <strong>Impressão de Livro</strong>. Enquanto essa migração não termina, este template fica
                        temporariamente indisponível para evitar diferenças de preço em relação ao site matriz.
                    </p>
                    @isset($product)
                        <p class="mb-1">
                            <strong>Produto:</strong> {{ $product->name }}
                        </p>
                    @endisset
                    <p class="text-muted mb-4">
                        Precisa de orçamento imediato? Fale com nossa equipe para aplicarmos manualmente os mesmos valores
                        da matriz ou acompanhe o catálogo principal para usar um produto já migrado.
                    </p>
                    <a href="{{ url('/') }}" class="btn btn-outline-secondary px-4">
                        Voltar ao catálogo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
