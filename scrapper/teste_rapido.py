import json
import time
import sys
import os

# Ajustar path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

try:
    from scrape_tempo_real import scrape_preco_tempo_real
    print("OK - Modulo TEMPO REAL importado")
except Exception as e:
    print(f"ERRO ao importar modulo: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)

# Teste
print("\nIniciando teste de velocidade...")
print("Aguardando resposta do site...")
print("(Isso pode levar alguns segundos)\n")

inicio = time.time()
try:
    # Testar com opções completas (como o site padrão)
    opcoes_teste = {
        'quantity': 50,
        'papel_capa': 'Cartão Triplex 250gr',
        'cores_capa': '4 cores FxV',
        'orelha_capa': 'SEM ORELHA',
        'acabamento_capa': 'Laminação FOSCA FRENTE (Acima de 240g)',
        'papel_miolo': 'Offset 75gr',
        'cores_miolo': '4 cores frente e verso',
        'miolo_sangrado': 'NÃO',
        'quantidade_paginas_miolo': 'Miolo 8 páginas',
        'acabamento_miolo': 'Dobrado',
        'acabamento_livro': 'Colado PUR',
        'guardas_livro': 'SEM GUARDAS',
        'extras': 'Nenhum'
    }
    preco = scrape_preco_tempo_real(opcoes_teste, 50)
    tempo = time.time() - inicio
    
    print(f"\n{'='*50}")
    print(f"RESULTADO:")
    print(f"{'='*50}")
    print(f"Preco encontrado: R$ {preco if preco else 'N/A'}")
    print(f"Tempo total: {tempo:.2f} segundos")
    print(f"Meta: 5 segundos")
    print(f"{'='*50}")
    
    if preco:
        if tempo <= 5:
            print("SUCESSO - Dentro da meta de 5 segundos!")
        else:
            print(f"ACIMA DA META - {tempo - 5:.2f}s acima (mas funcionou!)")
    else:
        print("ERRO - Preco nao foi encontrado")
        print("Verifique se o Chrome esta instalado e o Selenium esta configurado")
        
except Exception as e:
    tempo = time.time() - inicio
    print(f"\nERRO durante execucao: {e}")
    print(f"Tempo antes do erro: {tempo:.2f}s")
    import traceback
    traceback.print_exc()

