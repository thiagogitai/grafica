# Segurança do Proxy da API

## Proteções Implementadas

### 1. **Headers Realistas**
- User-Agent rotacionado (diferentes navegadores)
- Headers completos como um navegador real
- Referer e Origin corretos

### 2. **Rate Limiting**
- Mínimo de 1 segundo entre requisições para o mesmo produto
- Evita requisições muito frequentes

### 3. **Delays Aleatórios**
- Delay de 0.5-2 segundos entre requisições
- Simula comportamento humano

### 4. **Cache de Keys**
- Keys em cache por 24 horas
- Reduz número de requisições necessárias

## Como Funciona

1. **Primeira vez**: Descobre Keys via scraping ou arquivo
2. **Cache**: Keys ficam em cache por 24h
3. **Requisições**: Usa API com headers realistas e rate limiting

## Vantagens vs Scraping

✅ **Mais rápido** - Sem abrir navegador completo
✅ **Menos recursos** - Usa menos CPU/memória do servidor deles
✅ **Mais discreto** - Parece uma requisição AJAX normal
✅ **Mesma origem** - Requisições vêm do nosso servidor (não do navegador do usuário)

## Desvantagens

⚠️ **Pode ser detectado** se fizer muitas requisições muito rápido
⚠️ **Depende da API** - Se mudarem a API, precisamos ajustar

## Recomendações

1. **Não fazer muitas requisições por segundo**
2. **Manter cache atualizado** (executar script de mapeamento periodicamente)
3. **Monitorar logs** para ver se há bloqueios
4. **Usar rate limiting** adequado

## Alternativa: Scraping Completo

Se preferir não usar a API diretamente, podemos voltar ao scraping completo (mais lento, mas mais "invisível").

