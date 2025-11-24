# 📊 Análise Completa do Sistema - Gráfica Online

## 🎯 Visão Geral

Sistema de e-commerce para gráfica online desenvolvido em **Laravel 12** (PHP 8.2+) que integra com a API da Gráfica Eskenazi para validação de preços em tempo real. O sistema permite que clientes configurem produtos de impressão (livros, revistas, panfletos, etc.) e obtenham preços validados diretamente da matriz.

---

## 🏗️ Arquitetura do Sistema

### Stack Tecnológica

**Backend:**
- **Framework:** Laravel 12.0
- **PHP:** 8.2+
- **Banco de Dados:** MySQL/PostgreSQL (via Eloquent ORM)
- **Autenticação:** Laravel UI (Breeze padrão)

**Frontend:**
- **CSS Framework:** Tailwind CSS 4.0
- **JavaScript:** Vanilla JS + Axios
- **Build Tool:** Vite 7.0
- **UI Components:** Bootstrap 5.3 + Font Awesome 7.1

**Integrações:**
- **API Externa:** Gráfica Eskenazi (lojagraficaeskenazi.com.br)
- **Pagamento:** Mercado Pago SDK 3.7
- **WhatsApp:** Evolution API (via EvolutionWhatsapp service)
- **PDF Processing:** Spatie PDF-to-Image 1.2

**Scripts Auxiliares:**
- **Python 3:** Scripts de scraping com Selenium
- **ChromeDriver:** Para automação de navegador

---

## 📁 Estrutura de Diretórios

```
grafica1/
├── app/
│   ├── Http/Controllers/      # Controladores principais
│   │   ├── HomeController.php           # Página inicial e produtos
│   │   ├── ProductPriceController.php   # Validação de preços
│   │   ├── ApiPricingProxyController.php # Proxy para API externa
│   │   ├── CartController.php          # Carrinho de compras
│   │   ├── CheckoutController.php      # Finalização de pedidos
│   │   ├── AdminController.php        # Painel administrativo
│   │   └── ...
│   ├── Models/                 # Modelos Eloquent
│   │   ├── Product.php
│   │   ├── Order.php
│   │   ├── Category.php
│   │   ├── Setting.php
│   │   └── ...
│   ├── Services/               # Serviços de negócio
│   │   ├── PricingService.php          # Integração com API Eskenazi
│   │   ├── ProductConfig.php           # Carregamento de configurações
│   │   ├── Pricing.php                 # Cálculo de markup
│   │   └── ...
│   └── Console/Commands/       # Comandos Artisan
│       ├── CreateAdminUser.php
│       └── ...
├── resources/
│   ├── data/products/          # Configurações JSON dos produtos
│   │   ├── impressao-de-livro.json
│   │   ├── impressao-de-livro-field-keys.json
│   │   ├── impressao-de-revista.json
│   │   └── ...
│   ├── views/                  # Templates Blade
│   │   ├── products/
│   │   │   ├── livro.blade.php         # Template especial para livros
│   │   │   └── default.blade.php
│   │   ├── cart.blade.php
│   │   ├── checkout.blade.php
│   │   └── ...
│   └── js/                     # JavaScript frontend
│       └── app.js
├── routes/
│   └── web.php                 # Rotas da aplicação
├── database/
│   └── migrations/             # Migrações do banco
├── scrapper/                   # Scripts Python de scraping
│   ├── scrape_tempo_real.py
│   ├── mapear_keys_todos_produtos.py
│   └── ...
└── scripts/                     # Scripts PHP auxiliares
    ├── generate-field-keys.php
    └── verify-field-keys.php
```

---

## 🔑 Funcionalidades Principais

### 1. Sistema de Produtos

**Tipos de Templates:**
- `standard`: Produto simples com preço fixo
- `config:auto`: Produto configurável com auto-detecção de JSON
- `config:{slug}`: Produto configurável com slug específico
- `flyer`: Template especial para flyers

**Produtos Suportados:**
1. ✅ Impressão de Livro
2. ✅ Impressão de Apostila
3. ✅ Impressão de Revista
4. ✅ Impressão de Tabloide
5. ✅ Impressão de Panfleto
6. ✅ Impressão de Jornal de Bairro
7. ✅ Impressão de Guia de Bairro
8. ✅ Impressão Online de Livretos Personalizados
9. ✅ Impressão de Flyer

### 2. Sistema de Validação de Preços

**Fluxo de Validação:**

```
Frontend (JavaScript)
    ↓
POST /api/product/validate-price
    ↓
ProductPriceController::validatePrice()
    ↓
┌─────────────────────────────────┐
│ 1. PricingService (API Oficial)  │ ← Tenta primeiro
└─────────────────────────────────┘
    ↓ (se falhar)
┌─────────────────────────────────┐
│ 2. ApiPricingProxyController     │ ← Fallback
│    (Proxy com descoberta de Keys)│
└─────────────────────────────────┘
    ↓
Retorna preço validado
```

**Características:**
- ✅ Validação dupla (frontend + backend)
- ✅ Cache de 5 minutos para otimização
- ✅ Rate limiting (2-4s entre requisições)
- ✅ Fallback automático entre métodos
- ✅ Logs detalhados para debugging

### 3. Sistema de Mapeamento de Keys

**Problema Resolvido:**
A API da Eskenazi requer "Keys" específicas para cada opção (ex: `A1`, `A2`, `B3`), não valores diretos.

**Solução Implementada:**
1. **Arquivos de Mapeamento:** `{slug}-field-keys.json`
   - Contém mapeamento `valor → Key`
   - Inclui ordem dos campos (index)
   - Define chave de quantidade (Q1, Q2, etc.)

2. **Descoberta Automática:**
   - Scripts Python fazem scraping do site
   - Extraem Keys reais das requisições
   - Geram arquivos JSON automaticamente

3. **Cache Inteligente:**
   - Cache de 24h para mapeamentos
   - Fallback para arquivo completo
   - Descoberta via scraping se necessário

### 4. Sistema de Carrinho e Checkout

**Carrinho:**
- Armazenado em sessão PHP
- Suporta múltiplos produtos
- Calcula totais com markup
- Integração com cálculo de frete

**Checkout:**
- Validação de dados do cliente
- Cálculo de frete (Correios)
- Integração com Mercado Pago
- Envio de notificação via WhatsApp
- Criação de pedido no banco

**Modo "Solicitar Orçamento":**
- Produtos podem ser marcados como `request_only`
- Desabilita compra direta
- Gera apenas solicitação de orçamento

### 5. Sistema de Markup

**Dois Níveis de Markup:**
1. **Global:** Configurável em Settings (`price_percentage`)
2. **Por Produto:** Campo `markup_percentage` no produto

**Cálculo:**
```php
$factor = (1 + produto_markup/100) * (1 + global_markup/100)
$preco_final = $preco_base * $factor
```

### 6. Painel Administrativo

**Funcionalidades:**
- ✅ Gerenciamento de produtos
- ✅ Gerenciamento de categorias
- ✅ Gerenciamento de pedidos
- ✅ Configurações gerais (Settings)
- ✅ Controle de markup
- ✅ Edição de conteúdo da homepage

**Acesso:**
- Middleware `admin` protege rotas
- Comando artisan para criar admin:
  ```bash
  php artisan admin:create email@exemplo.com senha123
  ```

---

## 🔄 Fluxos Principais

### Fluxo 1: Visualização de Produto

```
1. Usuário acessa /product/{id}
   ↓
2. HomeController::show()
   ↓
3. Detecta tipo de template
   ↓
4. Carrega configuração JSON
   ├─ ProductConfig::loadForProduct()
   ├─ Tenta catálogo remoto (RemoteCatalog)
   └─ Fallback para arquivo local
   ↓
5. Renderiza view apropriada
   ├─ products/livro.blade.php (para livros)
   └─ products/default.blade.php (padrão)
   ↓
6. Frontend carrega opções do JSON
   ↓
7. Usuário seleciona opções
   ↓
8. JavaScript valida preço em tempo real
```

### Fluxo 2: Validação de Preço

```
1. JavaScript envia opções selecionadas
   POST /api/product/validate-price
   ↓
2. ProductPriceController::validatePrice()
   ↓
3. Tenta PricingService (API oficial)
   ├─ Carrega field-keys.json
   ├─ Constrói payload com Keys
   ├─ Chama API Eskenazi
   └─ Extrai preço da resposta
   ↓
4. Se falhar, tenta Proxy
   ├─ Carrega mapeamento de Keys
   ├─ Mapeia opções → Keys
   ├─ Chama API Eskenazi
   └─ Retorna preço
   ↓
5. Aplica markup
   ↓
6. Retorna preço formatado
```

### Fluxo 3: Adicionar ao Carrinho

```
1. Usuário clica "Adicionar ao Carrinho"
   ↓
2. Validação de preço (se necessário)
   ↓
3. POST /cart/add/{product}
   ↓
4. CartController::add()
   ├─ Valida dados
   ├─ Calcula preço com markup
   ├─ Adiciona à sessão
   └─ Redireciona para carrinho
   ↓
5. Carrinho exibe itens
   ↓
6. Usuário pode:
   ├─ Atualizar quantidade
   ├─ Remover itens
   ├─ Adicionar arte (upload)
   └─ Calcular frete
```

### Fluxo 4: Finalizar Pedido

```
1. Usuário acessa checkout
   ↓
2. Preenche dados de entrega
   ↓
3. POST /checkout/process
   ↓
4. CheckoutController::process()
   ├─ Valida dados
   ├─ Calcula totais
   ├─ Cria Order no banco
   ├─ Processa pagamento (Mercado Pago)
   └─ Envia notificação WhatsApp
   ↓
5. Redireciona para página de sucesso
```

---

## 🗄️ Estrutura do Banco de Dados

### Tabelas Principais

**users**
- Autenticação padrão Laravel
- Campo `is_admin` para controle de acesso

**products**
- `name`, `description`, `price`
- `template`: Tipo de template
- `category_id`: Relação com categorias
- `markup_percentage`: Markup específico
- `request_only`: Se é apenas orçamento
- `width`, `height`, `is_duplex`: Dimensões

**categories**
- Organização de produtos

**orders**
- `user_id`, `total`, `status`
- `items`: JSON com itens do pedido
- `shipping_address`, `payment_method`

**settings**
- Configurações globais
- `price_percentage`: Markup global
- `hero_title`, `hero_subtitle`: Conteúdo homepage
- `whatsapp_number`: Número para contato

**customer_profiles**
- Perfis de clientes
- Dados de entrega padrão

**order_feedbacks**
- Avaliações de pedidos

---

## 📦 Arquivos de Configuração JSON

### Estrutura de um Produto JSON

```json
{
  "slug": "impressao-de-livro",
  "name": "Impressão de Livro",
  "options": [
    {
      "name": "formato_miolo_paginas",
      "label": "Formato do Miolo",
      "type": "select",
      "default": "118x175mm",
      "choices": [
        {
          "value": "118x175mm",
          "label": "118x175mm"
        }
      ]
    }
  ]
}
```

### Estrutura de Field Keys JSON

```json
{
  "quantity_key": "Q1",
  "fields": {
    "formato_miolo_paginas": {
      "key": "A1",
      "index": 0,
      "default": "118x175mm"
    },
    "papel_capa": {
      "key": "A2",
      "index": 1,
      "default": "Couche Brilho 150gr "
    }
  }
}
```

**Importante:** Alguns valores têm espaço no final (ex: `"Couche Brilho 150gr "`). O sistema preserva esses espaços para compatibilidade com a API.

---

## 🐍 Scripts Python

### Scripts Principais

**mapear_keys_todos_produtos.py**
- Mapeia Keys de todos os produtos
- Gera `mapeamento_keys_todos_produtos.json`
- Usa Selenium para scraping

**scrape_tempo_real.py**
- Scraping em tempo real para validação
- Usado quando cache não está disponível
- Headless Chrome para velocidade

**gerar_tudo_automatico.py**
- Gera JSONs de configuração automaticamente
- Analisa produtos no site
- Cria arquivos em `resources/data/products/`

### Estrutura de Scraping

```python
1. Abre navegador (Chrome headless)
2. Acessa página do produto
3. Extrai opções dos selects
4. Captura requisições de pricing
5. Extrai Keys das requisições
6. Gera mapeamento JSON
```

---

## 🔐 Segurança

### Implementações

1. **Autenticação:**
   - Laravel Auth padrão
   - Middleware `auth` para rotas protegidas
   - Middleware `admin` para área administrativa

2. **Validação:**
   - Validação de preços no backend (não confia apenas no frontend)
   - Sanitização de inputs
   - Validação de quantidade mínima

3. **Rate Limiting:**
   - Delay entre requisições à API externa (2-4s)
   - Cache para reduzir chamadas
   - User-Agent rotativo para parecer natural

4. **Proteção de Dados:**
   - Dados sensíveis em `.env`
   - Sessões seguras
   - CSRF protection (Laravel padrão)

---

## ⚡ Performance

### Otimizações

1. **Cache:**
   - Cache de preços (5 minutos)
   - Cache de mapeamentos (24 horas)
   - Cache de configurações

2. **Lazy Loading:**
   - Carregamento sob demanda de configurações
   - Fallback para arquivos locais

3. **Rate Limiting:**
   - Evita sobrecarga da API externa
   - Delays aleatórios entre requisições

4. **Build Assets:**
   - Vite para build otimizado
   - Minificação de JS/CSS
   - Code splitting

---

## 🐛 Pontos de Atenção

### Problemas Conhecidos

1. **Espaços em Valores:**
   - Alguns valores da API têm espaço no final
   - Sistema preserva esses espaços
   - Logs ajudam a identificar problemas

2. **Ordem dos Campos:**
   - API requer ordem específica de opções
   - Sistema usa `index` nos field-keys.json
   - Produtos complexos (livro, revista) têm ordem hardcoded

3. **Python 3.13 no Windows:**
   - Problema conhecido com Selenium
   - Workaround implementado
   - Linux funciona normalmente

4. **ChromeDriver:**
   - Precisa estar atualizado
   - Scripts de atualização incluídos

### Melhorias Sugeridas

1. **Testes:**
   - Adicionar testes automatizados
   - Testes de integração com API
   - Testes de scraping

2. **Monitoramento:**
   - Logs estruturados
   - Alertas para falhas de API
   - Métricas de performance

3. **Documentação:**
   - API documentation
   - Guias de uso para admin
   - Documentação de scripts Python

---

## 📝 Comandos Úteis

### Artisan

```bash
# Criar usuário admin
php artisan admin:create email@exemplo.com senha123

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Migrar banco
php artisan migrate

# Gerar configs automáticos
php artisan product:auto-config
```

### Python

```bash
# Mapear Keys de todos os produtos
python mapear_keys_todos_produtos.py

# Gerar JSONs automáticos
python scrapper/gerar_tudo_automatico.py

# Testar scraping
python scrapper/scrape_tempo_real.py
```

---

## 🚀 Deploy

### Requisitos

- PHP 8.2+
- Composer
- Node.js + npm
- Python 3 + Selenium
- Chrome/Chromium + ChromeDriver
- MySQL/PostgreSQL
- Servidor web (Apache/Nginx)

### Passos

1. Clone repositório
2. `composer install`
3. `npm install && npm run build`
4. Configure `.env`
5. `php artisan key:generate`
6. `php artisan migrate`
7. Configure permissões (`storage/`, `bootstrap/cache/`)
8. Configure servidor web

---

## 📊 Métricas do Sistema

- **Produtos Suportados:** 9
- **Templates Especiais:** 2 (livro, flyer)
- **APIs Integradas:** 2 (Eskenazi, Mercado Pago)
- **Scripts Python:** 60+
- **Arquivos de Configuração:** 18 JSONs
- **Controllers:** 12
- **Services:** 6
- **Models:** 7

---

## ✅ Status Atual

- ✅ Sistema funcional
- ✅ Validação de preços implementada
- ✅ Integração com API externa
- ✅ Painel administrativo completo
- ✅ Carrinho e checkout funcionais
- ✅ Scripts de scraping operacionais
- ⚠️ Testes automatizados pendentes
- ⚠️ Documentação de API pendente

---

## 📚 Documentação Adicional

- `README.md` - Documentação principal
- `COMO_USAR.md` - Guia de uso
- `RESUMO_FINAL.md` - Resumo de implementação
- `DEPLOY_SERVIDOR.md` - Instruções de deploy
- `API_PROXY_SEGURANCA.md` - Segurança do proxy

---

**Última atualização:** 2025-01-XX
**Versão do Sistema:** 1.0.0
**Framework:** Laravel 12.0

