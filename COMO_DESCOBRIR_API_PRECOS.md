# Como Descobrir a API de Preços do Site Matriz

## Método 1: Monitorar Requisições de Rede (Mais Fácil)

1. **Abra o DevTools** (F12 no navegador)
2. **Vá na aba Network** (Rede)
3. **Filtre por XHR ou Fetch**
4. **Altere as opções** no formulário do site matriz
5. **Observe as requisições** que aparecem

### O que procurar:
- URLs que contenham: `pricing`, `price`, `calculate`, `calc`, `api`, `preco`, `valor`
- Requisições POST ou GET que enviam dados das opções
- Respostas JSON com preços

### Exemplo de API que pode aparecer:
```
POST https://www.lojagraficaeskenazi.com.br/api/pricing
Body: {
  "quantity": 100,
  "formato": "210x297mm (A4)",
  "papel_capa": "...",
  ...
}
Response: {
  "price": 1234.56,
  "formatted": "R$ 1.234,56"
}
```

## Método 2: Inspecionar JavaScript

1. **Abra o DevTools** (F12)
2. **Vá em Sources > Page**
3. **Procure por arquivos `.js`** relacionados a cálculo
4. **Procure por funções**: `calculatePrice`, `calcPrice`, `getPrice`, `calcularPreco`
5. **Procure por variáveis**: `priceMatrix`, `precos`, `pricingData`

## Método 3: Usar o Script Python

Execute o script `descobrir_api_precos.py`:

```bash
python descobrir_api_precos.py
```

Este script:
- Abre o site matriz
- Monitora requisições de rede
- Procura por funções JavaScript de cálculo
- Procura por variáveis globais com preços
- Lista todas as APIs encontradas

## Vantagens de Usar a API (se existir)

✅ **Muito mais rápido** - sem precisar abrir navegador
✅ **Mais confiável** - não depende de scraping
✅ **Menos recursos** - não precisa Chrome/Selenium
✅ **Sempre atualizado** - pega preços direto da fonte

## Desvantagens

❌ **Pode não existir** - site pode calcular tudo no frontend
❌ **Pode ser protegida** - requer autenticação ou tokens
❌ **Pode mudar** - URL ou formato podem mudar

## Como Usar a API (se encontrada)

1. Identificar a URL da API
2. Identificar o formato dos dados (JSON)
3. Fazer requisição HTTP diretamente do PHP/Python
4. Retornar o preço sem precisar de Selenium

### Exemplo de implementação:

```php
// Em ProductPriceController.php
public function obterPrecoViaAPI($opcoes) {
    $response = Http::post('https://site-matriz.com/api/pricing', [
        'options' => $opcoes
    ]);
    
    return $response->json()['price'];
}
```

