#!/bin/bash
# Script simples para testar preços de livro via API do próprio site

cd /www/wwwroot/grafica

echo "Testando preços de impressao-de-livro via API do site..."
echo ""

# Teste 1: Configuração básica
echo "TESTE 1: Configuração básica"
curl -X POST http://localhost/api/product/validate-price \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(php artisan tinker --execute='echo csrf_token();')" \
  -d '{
    "product_slug": "impressao-de-livro",
    "quantity": 50,
    "formato": "A4",
    "papel_capa": "Cartão Triplex 250gr",
    "cores_capa": "4 cores Frente",
    "orelha_capa": "SEM ORELHA",
    "acabamento_capa": "Laminação FOSCA FRENTE (Acima de 240g)",
    "papel_miolo": "Couche brilho 90gr",
    "cores_miolo": "4 cores frente e verso",
    "miolo_sangrado": "NÃO",
    "quantidade_paginas_miolo": "Miolo 8 páginas",
    "acabamento_miolo": "Dobrado",
    "acabamento_livro": "Grampeado - 2 grampos",
    "guardas_livro": "SEM GUARDAS",
    "extras": "Nenhum",
    "frete": "Incluso",
    "verificacao_arquivo": "Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)",
    "prazo_entrega": "Padrão: 10 dias úteis de Produção + tempo de FRETE*"
  }' | jq '.'

echo ""
echo "=========================================="
echo ""

