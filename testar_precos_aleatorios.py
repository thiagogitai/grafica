#!/usr/bin/env python3
"""
Script para testar preços com valores aleatórios comparando site matriz vs VPS
"""
import json
import sys
import os
import random
import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import re

# URLs dos produtos
PRODUTOS = {
    'impressao-de-panfleto': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-panfleto',
        'slug': 'impressao-de-panfleto',
        'api_url': 'https://todahgrafica.com.br/api/product/validate-price'
    },
    'impressao-de-apostila': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-apostila',
        'slug': 'impressao-de-apostila',
        'api_url': 'https://todahgrafica.com.br/api/product/validate-price'
    },
    'impressao-de-revista': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-revista',
        'slug': 'impressao-de-revista',
        'api_url': 'https://todahgrafica.com.br/api/product/validate-price'
    },
    'impressao-de-tabloide': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-tabloide',
        'slug': 'impressao-de-tabloide',
        'api_url': 'https://todahgrafica.com.br/api/product/validate-price'
    },
    'impressao-de-livro': {
        'url': 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro',
        'slug': 'impressao-de-livro',
        'api_url': 'https://todahgrafica.com.br/api/product/validate-price'
    },
}

def limpar_preco(texto):
    """Extrai valor numérico do preço"""
    if not texto:
        return None
    # Remove tudo exceto números, vírgula e ponto
    texto_limpo = re.sub(r'[^\d,.]', '', str(texto))
    # Substitui vírgula por ponto
    texto_limpo = texto_limpo.replace(',', '.')
    try:
        return float(texto_limpo)
    except:
        return None

def obter_preco_site_matriz(url, opcoes_escolhidas):
    """Obtém preço do site matriz com as opções escolhidas"""
    chrome_options = Options()
    chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    
    service = Service()
    driver = webdriver.Chrome(service=service, options=chrome_options)
    
    try:
        driver.get(url)
        time.sleep(3)
        
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
        
        for idx, select in enumerate(selects):
            try:
                select_elem = Select(select)
                label = None
                
                # Tentar encontrar label
                try:
                    parent = select.find_element(By.XPATH, './..')
                    labels = parent.find_elements(By.TAG_NAME, 'label')
                    if labels:
                        label = labels[0].text.strip()
                except:
                    pass
                
                # Procurar opção correspondente
                opcoes_disponiveis = [opt.text.strip() for opt in select_elem.options if opt.text.strip()]
                
                # Procurar match nas opções escolhidas
                for nome_campo, valor_escolhido in opcoes_escolhidas.items():
                    if label and nome_campo.lower() in label.lower():
                        # Tentar encontrar a opção
                        for opt in select_elem.options:
                            if valor_escolhido.lower() in opt.text.lower() or opt.text.lower() in valor_escolhido.lower():
                                select_elem.select_by_visible_text(opt.text)
                                time.sleep(0.5)
                                break
                        break
            except Exception as e:
                pass
        
        # Aguardar cálculo do preço
        time.sleep(3)
        
        # Tentar encontrar preço
        preco_texto = None
        try:
            # Procurar por elementos com preço
            elementos_preco = driver.find_elements(By.XPATH, "//*[contains(text(), 'R$')]")
            for elem in elementos_preco:
                texto = elem.text
                if 'R$' in texto and len(texto) < 50:
                    preco_texto = texto
                    break
        except:
            pass
        
        if not preco_texto:
            # Tentar método alternativo
            try:
                preco_elem = driver.find_element(By.CSS_SELECTOR, "strong, .price, [class*='price']")
                preco_texto = preco_elem.text
            except:
                pass
        
        return limpar_preco(preco_texto)
        
    finally:
        driver.quit()

def obter_preco_vps(slug, opcoes_escolhidas):
    """Obtém preço do VPS via API"""
    try:
        payload = {
            'product_slug': slug,
            'opcoes': opcoes_escolhidas,
            'force_validation': True,
            '_force': True
        }
        
        response = requests.post(
            PRODUTOS[slug]['api_url'],
            json=payload,
            timeout=60
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'preco' in data:
                return limpar_preco(data['preco'])
            else:
                return None, data.get('error', 'Erro desconhecido')
        else:
            return None, f"HTTP {response.status_code}: {response.text[:200]}"
            
    except Exception as e:
        return None, str(e)

def escolher_opcoes_aleatorias(slug):
    """Escolhe opções aleatórias baseadas no template JSON"""
    json_path = f'resources/data/products/{slug}.json'
    if not os.path.exists(json_path):
        return {}
    
    with open(json_path, 'r', encoding='utf-8') as f:
        template = json.load(f)
    
    opcoes_escolhidas = {}
    
    for campo in template.get('options', []):
        nome = campo.get('name')
        tipo = campo.get('type')
        
        if nome == 'quantity':
            # Escolher quantidade aleatória
            min_val = campo.get('min', 50)
            max_val = campo.get('max', 1000)
            opcoes_escolhidas['quantity'] = random.randint(min_val, min(max_val, 500))
        elif tipo == 'select':
            choices = campo.get('choices', [])
            if choices:
                escolhido = random.choice(choices)
                valor = escolhido.get('value', escolhido.get('label', ''))
                opcoes_escolhidas[nome] = valor
    
    return opcoes_escolhidas

def testar_produto(slug, num_teste=1):
    """Testa um produto com opções aleatórias"""
    print(f"\n{'='*80}")
    print(f"TESTE {num_teste}: {slug}")
    print(f"{'='*80}")
    
    # Escolher opções aleatórias
    opcoes = escolher_opcoes_aleatorias(slug)
    print(f"\n📋 Opções escolhidas:")
    for k, v in opcoes.items():
        print(f"   {k}: {v}")
    
    # Obter preço do site matriz
    print(f"\n🌐 Obtendo preço do site matriz...")
    preco_matriz = obter_preco_site_matriz(PRODUTOS[slug]['url'], opcoes)
    
    if preco_matriz:
        print(f"   ✅ Preço matriz: R$ {preco_matriz:.2f}")
    else:
        print(f"   ❌ Não foi possível obter preço do site matriz")
    
    # Obter preço do VPS
    print(f"\n🖥️  Obtendo preço do VPS...")
    resultado_vps = obter_preco_vps(slug, opcoes)
    
    if isinstance(resultado_vps, tuple):
        preco_vps, erro = resultado_vps
        print(f"   ❌ Erro VPS: {erro}")
        preco_vps = None
    else:
        preco_vps = resultado_vps
        if preco_vps:
            print(f"   ✅ Preço VPS: R$ {preco_vps:.2f}")
        else:
            print(f"   ❌ Não foi possível obter preço do VPS")
    
    # Comparar
    print(f"\n📊 COMPARAÇÃO:")
    if preco_matriz and preco_vps:
        diferenca = abs(preco_matriz - preco_vps)
        percentual = (diferenca / preco_matriz) * 100 if preco_matriz > 0 else 0
        
        if diferenca < 0.01:
            print(f"   ✅ PREÇOS BATERAM! (diferença: R$ {diferenca:.2f})")
            return True
        else:
            print(f"   ⚠️  PREÇOS DIFERENTES!")
            print(f"      Matriz: R$ {preco_matriz:.2f}")
            print(f"      VPS:    R$ {preco_vps:.2f}")
            print(f"      Diferença: R$ {diferenca:.2f} ({percentual:.2f}%)")
            return False
    else:
        print(f"   ❌ Não foi possível comparar (faltam dados)")
        return False

def main():
    print("="*80)
    print("TESTE DE PREÇOS ALEATÓRIOS - Site Matriz vs VPS")
    print("="*80)
    
    # Testar vários produtos
    produtos_para_testar = [
        'impressao-de-panfleto',
        'impressao-de-revista',
        'impressao-de-tabloide',
        'impressao-de-livro',
    ]
    
    resultados = []
    num_testes = 3  # Número de testes por produto
    
    for produto in produtos_para_testar:
        if produto not in PRODUTOS:
            continue
        
        for i in range(1, num_testes + 1):
            try:
                resultado = testar_produto(produto, f"{produto} - Teste {i}")
                resultados.append((produto, i, resultado))
                time.sleep(2)  # Pausa entre testes
            except KeyboardInterrupt:
                print("\n\n⚠️  Interrompido pelo usuário")
                break
            except Exception as e:
                print(f"\n   ❌ Erro no teste: {e}")
                import traceback
                traceback.print_exc()
                resultados.append((produto, i, False))
    
    # Resumo final
    print("\n" + "="*80)
    print("RESUMO DOS TESTES")
    print("="*80)
    
    sucessos = sum(1 for _, _, r in resultados if r)
    total = len(resultados)
    
    print(f"\n✅ Testes com preços batendo: {sucessos}/{total}")
    print(f"❌ Testes com diferenças: {total - sucessos}/{total}")
    
    if total > 0:
        taxa_sucesso = (sucessos / total) * 100
        print(f"📊 Taxa de sucesso: {taxa_sucesso:.1f}%")
    
    print("\n" + "="*80)

if __name__ == '__main__':
    main()

