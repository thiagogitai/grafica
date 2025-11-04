import json
import time
import sys
import os

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

try:
    from scrape_ultra_rapido import scrape_preco_ultra_rapido
    print("OK - Modulo ULTRA RAPIDO importado")
except Exception as e:
    print(f"ERRO ao importar: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)

print("\n" + "="*70)
print("TESTE ULTRA RAPIDO - Alvo: 5 segundos")
print("="*70)
print("\nAguardando resposta...\n")

inicio = time.time()
try:
    preco = scrape_preco_ultra_rapido({'quantity': 50}, 50)
    tempo = time.time() - inicio
    
    print("="*70)
    print("RESULTADO:")
    print("="*70)
    print(f"Preco: R$ {preco if preco else 'N/A'}")
    print(f"Tempo: {tempo:.2f} segundos")
    print(f"Meta: 5.00 segundos")
    print("="*70)
    
    if preco:
        if tempo <= 5:
            print("\nSUCESSO! DENTRO DA META DE 5 SEGUNDOS!")
        else:
            diff = tempo - 5
            print(f"\nAcima da meta: +{diff:.2f}s")
            print(f"Mas funcionou! ({((5/tempo)*100):.1f}% da velocidade ideal)")
    else:
        print("\nERRO - Preco nao encontrado")
        
except Exception as e:
    tempo = time.time() - inicio
    print(f"\nERRO: {e}")
    print(f"Tempo antes do erro: {tempo:.2f}s")
    import traceback
    traceback.print_exc()

