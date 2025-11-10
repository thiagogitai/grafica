"""
Testa combinação problemática diretamente e compara payloads
"""

import requests
import json

# Combinação que existe na matriz mas não funciona na API
valores = {
    'formato_miolo_paginas': '158x230mm',
    'papel_capa': 'Couche Brilho 210gr',
    'cores_capa': '5 cores Frente x 1 cor Preto Verso',
    'orelha_capa': 'COM Orelha de 9cm',
    'acabamento_capa': 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',
    'papel_miolo': 'Impressão Offset - >500unidades',
    'cores_miolo': '4 cores frente e verso',
    'miolo_sangrado': 'SIM',
    'quantidade_paginas_miolo': 'Miolo 944 páginas',
    'acabamento_miolo': 'Dobrado',
    'acabamento_livro': 'Capa Dura Papelão 18 (1,8mm) + Cola PUR',
    'guardas_livro': 'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala',
    'extras': 'Shrink Coletivo c/ 50 peças',
    'frete': 'Incluso',
    'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
    'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
}

# Carregar mapeamento
with open('mapeamento_keys_todos_produtos.json', 'r', encoding='utf-8') as f:
    mapeamento = json.load(f)

keysMap = mapeamento['mapeamento_por_produto']['impressao-de-livro']

ordemSelects = [
    'formato_miolo_paginas',
    'papel_capa',
    'cores_capa',
    'orelha_capa',
    'acabamento_capa',
    'papel_miolo',
    'cores_miolo',
    'miolo_sangrado',
    'quantidade_paginas_miolo',
    'acabamento_miolo',
    'acabamento_livro',
    'guardas_livro',
    'extras',
    'frete',
    'verificacao_arquivo',
    'prazo_entrega',
]

print("=" * 70)
print("TESTANDO COMBINACAO PROBLEMATICA")
print("=" * 70)
print()

# Construir opções
options = []
problemas = []

for campo in ordemSelects:
    if campo not in valores:
        continue
    
    valor = valores[campo]
    valorTrimmed = valor.strip()
    valorComEspaco = valorTrimmed + ' '
    
    key = None
    valorFinal = None
    
    if valorComEspaco in keysMap:
        key = keysMap[valorComEspaco]
        valorFinal = valorComEspaco
    elif valor in keysMap:
        key = keysMap[valor]
        valorFinal = valor
    elif valorTrimmed in keysMap:
        if valorComEspaco in keysMap:
            key = keysMap[valorComEspaco]
            valorFinal = valorComEspaco
        else:
            key = keysMap[valorTrimmed]
            valorFinal = valorTrimmed
    else:
        problemas.append(f"{campo}: '{valor}'")
        continue
    
    options.append({'Key': key, 'Value': valorFinal})

print(f"Opcoes construidas: {len(options)}")
if problemas:
    print(f"Problemas: {len(problemas)}")
    for p in problemas:
        print(f"  - {p}")
print()

# Testar diferentes variações
testes = [
    {
        'nome': 'Teste 1 - Quantidade 50',
        'q1': '50',
        'options': options
    },
    {
        'nome': 'Teste 2 - Quantidade 100',
        'q1': '100',
        'options': options
    },
    {
        'nome': 'Teste 3 - Verificar ordem das opções',
        'q1': '50',
        'options': options  # Mesma ordem
    }
]

url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing'

for teste in testes:
    print(f"\n{teste['nome']}...")
    
    payload = {
        'pricingParameters': {
            'KitParameters': None,
            'Q1': teste['q1'],
            'Options': teste['options']
        }
    }
    
    try:
        response = requests.post(
            url,
            json=payload,
            headers={
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('ErrorMessage'):
                print(f"  ERRO: {data['ErrorMessage']}")
            elif data.get('Cost') and data['Cost'] != '0':
                preco = float(data['Cost'].replace(',', '.'))
                print(f"  SUCESSO! Preco: R$ {preco:,.2f}")
            else:
                print(f"  ERRO: Preco zero ou vazio")
        else:
            print(f"  ERRO HTTP: {response.status_code}")
    except Exception as e:
        print(f"  EXCECAO: {e}")

print("\n" + "=" * 70)
print("CONCLUSAO")
print("=" * 70)
print()
print("Se todos os testes falharam, a combinacao pode ser invalida")
print("mesmo que apareca na matriz. Pode haver:")
print("1. Dependencias entre opcoes (algumas nao funcionam juntas)")
print("2. Regras de negocio na API que nao aparecem na interface")
print("3. Problema de mapeamento (keys incorretas para essa combinacao)")

