#!/usr/bin/env python3
"""
Script para verificar quais formatos estão disponíveis no site matriz
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select
import time
import json

base_url = "https://www.lojagraficaeskenazi.com.br"
url = f"{base_url}/product/impressao-de-livro"

chrome_options = Options()
chrome_options.add_argument('--headless=new')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')
chrome_options.add_argument('--disable-gpu')
chrome_options.add_argument('--window-size=1920,1080')

service = Service()
driver = webdriver.Chrome(service=service, options=chrome_options)

try:
    print("🔍 Acessando site matriz...")
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
    
    # Encontrar select de formato (primeiro select geralmente é formato)
    selects = driver.find_elements(By.TAG_NAME, 'select')
    print(f"📊 Total de selects encontrados: {len(selects)}\n")
    
    if len(selects) > 0:
        # O primeiro select geralmente é formato
        select_formato = selects[0]
        
        print("🔍 OPÇÕES DE FORMATO NO SITE MATRIZ:\n")
        print("=" * 80)
        
        opcoes = select_formato.find_elements(By.TAG_NAME, 'option')
        formatos_encontrados = []
        
        for idx, opt in enumerate(opcoes):
            if idx == 0:
                continue  # Pular primeira opção vazia
            
            texto = opt.text.strip()
            value = opt.get_attribute('value') or ''
            
            if texto:
                formatos_encontrados.append({
                    'texto': texto,
                    'value': value,
                    'index': idx
                })
                print(f"   {idx}. '{texto}' (value: '{value}')")
        
        print("\n" + "=" * 80)
        print(f"📊 Total de formatos encontrados: {len(formatos_encontrados)}\n")
        
        # Verificar se "105x148mm (A6)" existe
        encontrado_a6 = False
        for fmt in formatos_encontrados:
            if '105x148' in fmt['texto'] or 'A6' in fmt['texto']:
                print(f"✅ FORMATO A6 ENCONTRADO: '{fmt['texto']}'")
                print(f"   Value: '{fmt['value']}'")
                print(f"   Index: {fmt['index']}")
                encontrado_a6 = True
        
        if not encontrado_a6:
            print("❌ FORMATO '105x148mm (A6)' NÃO ENCONTRADO NO SITE!")
            print("\n💡 Sugestões:")
            print("   1. O formato pode não estar disponível para livro")
            print("   2. O formato pode ter nome diferente no site")
            print("   3. Verificar se existe '105x148mm' sem o '(A6)'")
            
            # Verificar se existe sem o (A6)
            for fmt in formatos_encontrados:
                if '105x148' in fmt['texto']:
                    print(f"\n   ✅ ENCONTRADO SIMILAR: '{fmt['texto']}'")
                    print(f"      💡 Sugestão: Corrigir template para usar '{fmt['texto']}'")
        
        # Salvar lista de formatos
        with open('formatos_site_matriz_livro.json', 'w', encoding='utf-8') as f:
            json.dump(formatos_encontrados, f, indent=2, ensure_ascii=False)
        
        print(f"\n💾 Lista de formatos salva em 'formatos_site_matriz_livro.json'")
        
    else:
        print("❌ Nenhum select encontrado na página!")
        
except Exception as e:
    print(f"❌ Erro: {e}")
    import traceback
    traceback.print_exc()
    
finally:
    driver.quit()

