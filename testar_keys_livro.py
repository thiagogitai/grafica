#!/usr/bin/env python3
"""
Script para testar se as Keys mapeadas de impressao-de-livro funcionam corretamente
Compara preços obtidos via API com preços do site matriz
"""
import json
import requests
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select
import tempfile
import os

# Carregar Keys mapeadas
with open('mapeamento_keys_todos_produtos.json', 'r', encoding='utf-8') as f:
    dados = json.load(f)

mapeamento_por_produto = dados.get('mapeamento_por_produto', {})
keys_livro = mapeamento_por_produto.get('impressao-de-livro', {})

if not keys_livro:
    print("❌ Keys de impressao-de-livro não encontradas!")
    exit(1)

print(f"✅ Carregadas {len(keys_livro)} Keys de impressao-de-livro")
print("="*80)

# Configurar Chrome
chrome_options = Options()
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--window-size=1920,1080')

chrome_user_data_dir = tempfile.mkdtemp(prefix='chrome_user_data_')
chrome_options.add_argument(f'--user-data-dir={chrome_user_data_dir}')
os.environ['SELENIUM_CACHE_DIR'] = tempfile.gettempdir()

service = Service()
driver = webdriver.Chrome(service=service, options=chrome_options)

def obter_preco_site_matriz(opcoes_testar):
    """Obtém preço diretamente do site matriz usando scraping"""
    try:
        url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
        driver.get(url)
        time.sleep(5)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btn = Array.from(document.querySelectorAll('button')).find(
                    b => b.textContent.includes('Aceitar') || b.textContent.includes('aceitar')
                );
                if (btn) btn.click();
            """)
            time.sleep(1)
        except:
            pass
        
        # Aplicar opções
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        # Mapear opções para selects (usar mesma lógica do scrape_revista.py)
        # Para livro, precisamos mapear os campos para os selects
        # Vou usar uma abordagem mais simples: aplicar todas as opções sequencialmente
        
        # Aplicar quantidade primeiro
        try:
            qtd_input = driver.find_element(By.ID, "Q1")
            quantidade = opcoes_testar.get('quantity', 50)
            qtd_input.clear()
            qtd_input.send_keys(str(quantidade))
            driver.execute_script("arguments[0].blur();", qtd_input)
            time.sleep(1)
        except:
            pass
        
        # Aplicar selects na ordem (0 a 15)
        for idx_select in range(min(16, len(selects))):
            select = selects[idx_select]
            try:
                opcoes_select = select.find_elements(By.TAG_NAME, 'option')
                if len(opcoes_select) <= 1:
                    continue
                
                # Tentar encontrar a opção correspondente
                for campo, valor in opcoes_testar.items():
                    if campo == 'quantity':
                        continue
                    
                    # Procurar opção que corresponde ao valor
                    for idx_opt in range(1, len(opcoes_select)):
                        opt_text = opcoes_select[idx_opt].text.strip()
                        if str(valor).strip() in opt_text or opt_text in str(valor).strip():
                            Select(select).select_by_index(idx_opt)
                            time.sleep(0.5)
                            break
            except:
                pass
        
        # Aguardar cálculo
        time.sleep(5)
        
        # Buscar preço
        try:
            preco_element = driver.find_element(By.ID, "calc-total")
            preco_texto = preco_element.text.strip()
            
            # Extrair valor numérico
            import re
            valor = re.sub(r'[R$\s.]', '', preco_texto)
            valor = valor.replace(',', '.')
            preco = float(valor)
            
            if 1 <= preco <= 100000:
                return preco
        except:
            pass
        
        return None
    except Exception as e:
        print(f"   ❌ Erro ao obter preço do site matriz: {e}")
        return None

def obter_preco_via_api(opcoes_testar):
    """Obtém preço via API usando as Keys mapeadas"""
    try:
        # Mapear opções para Keys
        options = []
        for campo, valor in opcoes_testar.items():
            if campo == 'quantity':
                continue
            
            valor_str = str(valor).strip()
            
            # Procurar Key correspondente
            if valor_str in keys_livro:
                options.append({
                    'Key': keys_livro[valor_str],
                    'Value': valor_str
                })
            else:
                # Tentar match parcial
                encontrado = False
                for key_texto, key_value in keys_livro.items():
                    if valor_str in key_texto or key_texto in valor_str:
                        options.append({
                            'Key': key_value,
                            'Value': key_texto
                        })
                        encontrado = True
                        break
                
                if not encontrado:
                    print(f"   ⚠️ Key não encontrada para: {campo} = {valor_str}")
        
        if not options:
            print("   ❌ Nenhuma opção mapeada para Keys!")
            return None
        
        # Preparar payload
        payload = {
            "pricingParameters": {
                "Options": options,
                "Quantity": opcoes_testar.get('quantity', 50)
            }
        }
        
        # Chamar API
        url = "https://www.lojagraficaeskenazi.com.br/api/pricing/calculate"
        response = requests.post(url, json=payload, headers={
            'Content-Type': 'application/json',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if 'Cost' in data and data['Cost']:
                preco = float(str(data['Cost']).replace(',', '.'))
                return preco
            elif 'ErrorMessage' in data:
                print(f"   ❌ API retornou erro: {data['ErrorMessage']}")
        
        return None
    except Exception as e:
        print(f"   ❌ Erro ao chamar API: {e}")
        return None

# Testes com diferentes combinações
testes = [
    {
        'quantity': 50,
        'formato': 'A4',
        'papel_capa': 'Cartão Triplex 250gr',
        'cores_capa': '4 cores Frente',
        'orelha_capa': 'SEM ORELHA',
        'acabamento_capa': 'Laminação FOSCA FRENTE (Acima de 240g)',
        'papel_miolo': 'Couche brilho 90gr',
        'cores_miolo': '4 cores frente e verso',
        'miolo_sangrado': 'NÃO',
        'quantidade_paginas_miolo': 'Miolo 8 páginas',
        'acabamento_miolo': 'Dobrado',
        'acabamento_livro': 'Grampeado - 2 grampos',
        'guardas_livro': 'SEM GUARDAS',
        'extras': 'Nenhum',
        'frete': 'Incluso',
        'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)',
        'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
    },
    {
        'quantity': 100,
        'formato': 'A5',
        'papel_capa': 'Cartão Triplex 300gr',
        'cores_capa': '4 cores Frente e Verso',
        'orelha_capa': 'COM Orelha de 14cm',
        'acabamento_capa': 'Laminação BRILHO FRENTE (Acima de 240g)',
        'papel_miolo': 'Offset 90gr',
        'cores_miolo': '4 cores frente e verso',
        'miolo_sangrado': 'SIM',
        'quantidade_paginas_miolo': 'Miolo 16 páginas',
        'acabamento_miolo': 'Grampeado - 2 grampos',
        'acabamento_livro': 'Grampeado - 2 grampos',
        'guardas_livro': 'COM GUARDAS',
        'extras': 'Nenhum',
        'frete': 'Incluso',
        'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)',
        'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
    },
    {
        'quantity': 200,
        'formato': '105x148mm (A6)',
        'papel_capa': 'Cartão Triplex 250gr',
        'cores_capa': '4 cores Frente',
        'orelha_capa': 'SEM ORELHA',
        'acabamento_capa': 'Laminação FOSCA FRENTE (Acima de 240g)',
        'papel_miolo': 'Couche brilho 90gr',
        'cores_miolo': '1 cor (P/B) frente e verso',
        'miolo_sangrado': 'NÃO',
        'quantidade_paginas_miolo': 'Miolo 32 páginas',
        'acabamento_miolo': 'Dobrado',
        'acabamento_livro': 'Grampeado - 2 grampos',
        'guardas_livro': 'SEM GUARDAS',
        'extras': 'Nenhum',
        'frete': 'Incluso',
        'verificacao_arquivo': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)',
        'prazo_entrega': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
    }
]

print("TESTANDO PREÇOS DE IMPRESSÃO-DE-LIVRO")
print("="*80)
print(f"Total de testes: {len(testes)}\n")

resultados = []

for idx, opcoes in enumerate(testes, 1):
    print(f"\n{'='*80}")
    print(f"TESTE {idx}/{len(testes)}")
    print(f"{'='*80}")
    print(f"Quantidade: {opcoes['quantity']}")
    print(f"Formato: {opcoes['formato']}")
    print(f"Páginas: {opcoes['quantidade_paginas_miolo']}")
    
    # Obter preço via API
    print("\n📡 Obtendo preço via API...")
    preco_api = obter_preco_via_api(opcoes)
    
    # Obter preço do site matriz
    print("🌐 Obtendo preço do site matriz (scraping)...")
    preco_site = obter_preco_site_matriz(opcoes)
    
    # Comparar
    print(f"\n📊 RESULTADO:")
    print(f"   API: R$ {preco_api:.2f}" if preco_api else "   API: ❌ Erro")
    print(f"   Site: R$ {preco_site:.2f}" if preco_site else "   Site: ❌ Erro")
    
    if preco_api and preco_site:
        diferenca = abs(preco_api - preco_site)
        percentual_diff = (diferenca / preco_site) * 100
        
        if diferenca < 0.01:  # Diferença menor que 1 centavo
            print(f"   ✅ VALORES IDÊNTICOS!")
            resultado = "✅ IDÊNTICO"
        elif percentual_diff < 1:  # Diferença menor que 1%
            print(f"   ✅ Valores muito próximos (diferença: R$ {diferenca:.2f} - {percentual_diff:.2f}%)")
            resultado = "✅ PRÓXIMO"
        else:
            print(f"   ⚠️ Diferença significativa: R$ {diferenca:.2f} ({percentual_diff:.2f}%)")
            resultado = "⚠️ DIFERENTE"
    else:
        resultado = "❌ ERRO"
    
    resultados.append({
        'teste': idx,
        'opcoes': opcoes,
        'preco_api': preco_api,
        'preco_site': preco_site,
        'resultado': resultado
    })
    
    time.sleep(3)  # Aguardar entre testes

driver.quit()

# Resumo final
print("\n" + "="*80)
print("RESUMO FINAL")
print("="*80)
for res in resultados:
    print(f"Teste {res['teste']}: {res['resultado']}")
    if res['preco_api'] and res['preco_site']:
        print(f"  API: R$ {res['preco_api']:.2f} | Site: R$ {res['preco_site']:.2f}")

print("\n" + "="*80)

