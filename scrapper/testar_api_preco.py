"""
Script para testar se existe uma API de cálculo de preços
"""
import requests
import json
import time

def testar_endpoints_api():
    """
    Testa endpoints comuns de API de cálculo de preços
    """
    base_url = "https://www.lojagraficaeskenazi.com.br"
    
    # Endpoints comuns para testar
    endpoints_teste = [
        "/api/calculate-price",
        "/api/price/calculate",
        "/api/product/calculate",
        "/calculate-price",
        "/price/calculate",
        "/api/livro/price",
        "/api/calculate",
    ]
    
    # Payload de teste
    payload_teste = {
        "product": "impressao-de-livro",
        "quantity": "100",
        "formato_miolo_paginas": "155x230mm (formato otimizado digital)",
        "papel_capa": "Cartão Triplex 250gr",
        "cores_capa": "4 cores FxV",
        "orelha_capa": "SEM ORELHA",
        "acabamento_capa": "Laminação FOSCA FRENTE (Acima de 240g)",
        "papel_miolo": "Offset 75gr",
        "cores_miolo": "4 cores frente e verso",
        "miolo_sangrado": "SIM",
        "quantidade_paginas_miolo": "Miolo 32 páginas",
        "acabamento_miolo": "Dobrado",
        "acabamento_livro": "Colado PUR",
        "guardas_livro": "SEM GUARDAS",
        "extras": "Nenhum",
    }
    
    print("="*70)
    print("TESTANDO POSSIVEIS ENDPOINTS DE API")
    print("="*70)
    
    resultados = []
    
    for endpoint in endpoints_teste:
        url = base_url + endpoint
        print(f"\nTestando: {url}")
        
        # Testar GET
        try:
            response = requests.get(url, params=payload_teste, timeout=5)
            if response.status_code == 200:
                print(f"  OK - GET (200) - Resposta: {response.text[:200]}")
                resultados.append({
                    'endpoint': endpoint,
                    'method': 'GET',
                    'status': response.status_code,
                    'response': response.text[:500]
                })
            else:
                print(f"  X GET {response.status_code}")
        except Exception as e:
            print(f"  X GET Erro: {str(e)[:50]}")
        
        # Testar POST
        try:
            response = requests.post(url, json=payload_teste, timeout=5)
            if response.status_code == 200:
                print(f"  OK - POST (200) - Resposta: {response.text[:200]}")
                resultados.append({
                    'endpoint': endpoint,
                    'method': 'POST',
                    'status': response.status_code,
                    'response': response.text[:500]
                })
            else:
                print(f"  X POST {response.status_code}")
        except Exception as e:
            print(f"  X POST Erro: {str(e)[:50]}")
        
        time.sleep(0.5)  # Não fazer muitas requisições rápido
    
    # Salvar resultados
    if resultados:
        with open('testes_api_resultados.json', 'w', encoding='utf-8') as f:
            json.dump(resultados, f, ensure_ascii=False, indent=2)
        print(f"\nOK - {len(resultados)} endpoints funcionais encontrados!")
        print("Resultados salvos em: testes_api_resultados.json")
    else:
        print("\nX Nenhum endpoint de API encontrado")
        print("O site provavelmente calcula precos apenas no JavaScript do cliente")
    
    return resultados

if __name__ == "__main__":
    testar_endpoints_api()

