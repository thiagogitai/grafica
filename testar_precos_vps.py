#!/usr/bin/env python3
"""
Script para testar preços com valores aleatórios - RODA NO VPS
Compara preço extraído do site matriz vs preço calculado pelo script Python
"""
import json
import sys
import os
import random
import time
import re
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

# Importar scripts de scraping
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
try:
    from scrapper.scrape_revista import scrape_preco_tempo_real as scrape_revista
except:
    scrape_revista = None

try:
    from scrapper.scrape_tempo_real import scrape_preco_tempo_real as scrape_generico
except:
    scrape_generico = None

def limpar_preco(texto):
    """Extrai valor numérico do preço"""
    if not texto:
        return None
    valor = re.sub(r'[R$\s.]', '', str(texto))
    valor = valor.replace(',', '.')
    try:
        return float(valor)
    except:
        return None

def encontrar_elemento_preco(driver):
    """Encontra o elemento que contém o preço"""
    # Primeiro tentar ID específico (mais confiável)
    try:
        elemento = driver.find_element(By.ID, "calc-total")
        texto = elemento.text.strip()
        if 'R$' in texto:
            # Verificar se é um valor razoável (não milhões)
            valor = limpar_preco(texto)
            if valor and valor < 100000:  # Preço razoável (menos de 100 mil)
                return elemento, "calc-total"
    except:
        pass
    
    # Tentar outros IDs
    possiveis_ids = ['total-price', 'preco-total', 'price-total', 'total']
    for id_tentativa in possiveis_ids:
        try:
            elemento = driver.find_element(By.ID, id_tentativa)
            texto = elemento.text.strip()
            if 'R$' in texto:
                valor = limpar_preco(texto)
                if valor and valor < 100000:
                    return elemento, id_tentativa
        except:
            continue
    
    # Tentar XPath mais específicos
    possiveis_xpath = [
        "//*[@id='calc-total']",
        "//*[contains(@id, 'calc-total')]",
        "//*[contains(text(), 'Valor desse pedido')]/following-sibling::*[1]",
        "//*[contains(text(), 'Valor desse pedido')]/following-sibling::*[contains(text(), 'R$')]",
    ]
    
    for xpath in possiveis_xpath:
        try:
            elementos = driver.find_elements(By.XPATH, xpath)
            for elemento in elementos:
                texto = elemento.text.strip()
                if 'R$' in texto and len(texto) < 100:
                    valor = limpar_preco(texto)
                    if valor and valor < 100000:
                        return elemento, elemento.get_attribute('id') or 'preco_encontrado'
        except:
            continue
    
    return None, None

def obter_preco_site_matriz(url, opcoes_escolhidas):
    """Obtém preço do site matriz com as opções escolhidas"""
    chrome_options = Options()
    chrome_options.add_argument('--headless=new')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-software-rasterizer')
    
    import tempfile
    selenium_cache_dir = os.path.join(tempfile.gettempdir(), 'selenium_cache_' + str(os.getpid()))
    os.makedirs(selenium_cache_dir, exist_ok=True)
    os.environ['SELENIUM_CACHE_DIR'] = selenium_cache_dir
    
    chrome_user_data_dir = os.path.join(tempfile.gettempdir(), 'chrome_user_data_' + str(os.getpid()))
    os.makedirs(chrome_user_data_dir, exist_ok=True)
    chrome_options.add_argument(f'--user-data-dir={chrome_user_data_dir}')
    
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
        
        # Aplicar opções usando o MESMO mapeamento do script Python
        selects = driver.find_elements(By.TAG_NAME, 'select')
        
        # Mapeamento EXATO igual ao scrape_revista.py
        if 'impressao-de-revista' in url or 'revista' in url:
            mapeamento = {
                'formato': 0,
                'papel_capa': 1,
                'cores_capa': 2,
                'orelha_capa': 3,
                'acabamento_capa': 4,
                'papel_miolo': 5,
                'cores_miolo': 6,
                'miolo_sangrado': 7,
                'quantidade_paginas_miolo': 8,
                'acabamento_miolo': 9,
                'acabamento_livro': 10,
                'guardas_livro': 11,
                'extras': 12,
                'frete': 13,
                'verificacao_arquivo': 14,
                'prazo_entrega': 15,
            }
        elif 'impressao-de-tabloide' in url or 'tabloide' in url:
            mapeamento = {
                'formato': 0,
                'papel_miolo': 1,
                'cores_miolo': 2,
                'quantidade_paginas_miolo': 3,
                'acabamento_miolo': 4,
                'acabamento_livro': 5,
                'extras': 6,
                'frete': 7,
                'verificacao_arquivo': 8,
                'prazo_entrega': 9,
            }
        else:
            # Fallback: usar método antigo por labels
            mapeamento = None
        
        if mapeamento:
            # Usar mapeamento fixo (igual ao script Python)
            campos_ordenados = []
            max_idx = max(mapeamento.values()) if mapeamento else 0
            for idx in range(max_idx + 1):
                for campo, valor in opcoes_escolhidas.items():
                    if campo == 'quantity':
                        continue
                    if mapeamento.get(campo) == idx:
                        campos_ordenados.append((campo, valor))
                        break
            
            # Processar campos na ordem correta
            for campo, valor in campos_ordenados:
                idx = mapeamento.get(campo)
                if idx is not None and idx < len(selects):
                    select = selects[idx]
                    valor_str = str(valor).strip()
                    
                    for opt in select.find_elements(By.TAG_NAME, 'option'):
                        v = opt.get_attribute('value')
                        t = opt.text.strip()
                        v_str = str(v).strip() if v else ''
                        t_str = str(t).strip() if t else ''
                        
                        if (v_str == valor_str or t_str == valor_str or 
                            valor_str in v_str or valor_str in t_str or
                            v_str in valor_str or t_str in valor_str):
                            try:
                                Select(select).select_by_value(v)
                                time.sleep(0.4)
                                break
                            except:
                                try:
                                    Select(select).select_by_visible_text(t)
                                    time.sleep(0.4)
                                    break
                                except:
                                    pass
        else:
            # Fallback: método antigo por labels
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
                    
                    # Procurar match nas opções escolhidas
                    for nome_campo, valor_escolhido in opcoes_escolhidas.items():
                        if label and (nome_campo.lower() in label.lower() or any(palavra in label.lower() for palavra in nome_campo.lower().split('_'))):
                            # Tentar encontrar a opção
                            for opt in select_elem.options:
                                opt_text = opt.text.strip()
                                if valor_escolhido.lower() in opt_text.lower() or opt_text.lower() in valor_escolhido.lower():
                                    try:
                                        select_elem.select_by_visible_text(opt_text)
                                        time.sleep(0.5)
                                        break
                                    except:
                                        try:
                                            select_elem.select_by_value(opt.value)
                                            time.sleep(0.5)
                                            break
                                        except:
                                            pass
                            break
                except Exception as e:
                    pass
        
        # Aplicar quantidade - campo é input id="Q1" (não type="number")
        try:
            # Tentar primeiro pelo ID específico (para revista)
            qtd_input = driver.find_element(By.ID, "Q1")
            if 'quantity' in opcoes_escolhidas:
                qtd_input.clear()
                time.sleep(0.2)
                qtd_input.send_keys(str(opcoes_escolhidas['quantity']))
                time.sleep(0.3)
                # Clicar fora (blur) para confirmar
                driver.execute_script("arguments[0].blur();", qtd_input)
                time.sleep(1)
        except:
            # Fallback: tentar input type="number" (para outros produtos)
            try:
                qtd_input = driver.find_element(By.XPATH, "//input[@type='number']")
                if 'quantity' in opcoes_escolhidas:
                    qtd_input.clear()
                    qtd_input.send_keys(str(opcoes_escolhidas['quantity']))
                    time.sleep(1)
            except:
                pass
        
        # Aguardar cálculo do preço
        time.sleep(4)
        
        # Encontrar preço - tentar múltiplas vezes
        for tentativa in range(10):
            elemento_preco, id_encontrado = encontrar_elemento_preco(driver)
            
            if elemento_preco:
                preco_texto = elemento_preco.text.strip()
                preco_valor = limpar_preco(preco_texto)
                
                # Validar se o preço é razoável
                if preco_valor and 0 < preco_valor < 100000:
                    print(f"   Preço encontrado (tentativa {tentativa + 1}): R$ {preco_valor:.2f} (texto: {preco_texto})", file=sys.stderr)
                    return preco_valor
            
            time.sleep(0.3)
        
        print(f"   Não foi possível encontrar preço válido", file=sys.stderr)
        return None
        
    finally:
        driver.quit()

def obter_preco_script_python(slug, opcoes):
    """Obtém preço usando o script Python de scraping"""
    import subprocess
    import json
    import platform
    
    try:
        quantidade = opcoes.get('quantity', 50)
        
        print(f"\n   🔍 DEBUG: Iniciando obtenção de preço via script Python")
        print(f"      Slug: {slug}")
        print(f"      Quantidade: {quantidade}")
        print(f"      Total de opções: {len(opcoes)}")
        print(f"      Opções enviadas:")
        for campo, valor in sorted(opcoes.items()):
            print(f"         - {campo}: {valor}")
        
        # Detectar comando Python correto
        if platform.system() == 'Windows':
            python_cmd = 'python'  # Windows geralmente usa 'python'
        else:
            python_cmd = 'python3'  # Linux/Mac usa 'python3'
        
        # Determinar qual script usar
        script_path = None
        base_dir = os.path.dirname(os.path.abspath(__file__))
        
        if slug == 'impressao-de-revista':
            script_path = os.path.join(base_dir, 'scrapper', 'scrape_revista.py')
        elif slug == 'impressao-de-tabloide':
            script_path = os.path.join(base_dir, 'scrapper', 'scrape_tabloide.py')
        else:
            # Tentar usar genérico
            script_path = os.path.join(base_dir, 'scrapper', 'scrape_tempo_real.py')
        
        if not script_path or not os.path.exists(script_path):
            print(f"   Script não encontrado: {script_path}", file=sys.stderr)
            print(f"   Diretório atual: {os.getcwd()}", file=sys.stderr)
            print(f"   Base dir: {base_dir}", file=sys.stderr)
            return None
        
        # Preparar dados para o script
        dados_json = json.dumps({
            'opcoes': opcoes,
            'quantidade': quantidade
        })
        
        print(f"\n   🔍 DEBUG: Comando a ser executado:")
        print(f"      {python_cmd} {script_path}")
        print(f"      JSON enviado: {dados_json[:200]}...")
        
        # Executar script via subprocess para capturar stdout corretamente
        # script_path já está em caminho absoluto
        resultado_subprocess = subprocess.run(
            [python_cmd, script_path, dados_json],
            capture_output=True,
            text=True,
            timeout=120,
            cwd=base_dir  # Executar no diretório base do projeto
        )
        
        print(f"\n   🔍 DEBUG: Resultado do subprocess:")
        print(f"      Return code: {resultado_subprocess.returncode}")
        print(f"      Stdout length: {len(resultado_subprocess.stdout) if resultado_subprocess.stdout else 0}")
        print(f"      Stderr length: {len(resultado_subprocess.stderr) if resultado_subprocess.stderr else 0}")
        
        # Capturar stdout e stderr
        stdout = resultado_subprocess.stdout
        stderr = resultado_subprocess.stderr
        
        # Log stderr para debug
        if stderr:
            print(f"   Script stderr: {stderr[:500]}", file=sys.stderr)
        
        # Tentar parsear JSON do stdout
        if stdout:
            try:
                resultado = json.loads(stdout)
                if resultado.get('success'):
                    preco = resultado.get('price') or resultado.get('preco')
                    if preco:
                        return float(preco)
                else:
                    print(f"   Script retornou erro: {resultado.get('error', 'Erro desconhecido')}", file=sys.stderr)
            except json.JSONDecodeError:
                # Tentar extrair número do stdout
                import re
                numeros = re.findall(r'\d+\.?\d*', stdout)
                if numeros:
                    try:
                        return float(numeros[0])
                    except:
                        pass
                print(f"   Não foi possível parsear JSON do stdout: {stdout[:200]}", file=sys.stderr)
        
        return None
        
    except subprocess.TimeoutExpired:
        print(f"   Script excedeu timeout (120s)", file=sys.stderr)
        return None
    except Exception as e:
        print(f"   Erro ao executar script Python: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return None

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
    
    url = f"https://www.lojagraficaeskenazi.com.br/product/{slug}"
    
    # Escolher opções aleatórias
    opcoes = escolher_opcoes_aleatorias(slug)
    print(f"\n📋 Opções escolhidas:")
    for k, v in list(opcoes.items())[:10]:  # Mostrar apenas primeiras 10
        print(f"   {k}: {v}")
    if len(opcoes) > 10:
        print(f"   ... e mais {len(opcoes) - 10} opções")
    
    # Obter preço do site matriz (extração direta)
    print(f"\n🌐 Obtendo preço do site matriz (extração direta)...")
    preco_matriz = obter_preco_site_matriz(url, opcoes)
    
    if preco_matriz:
        print(f"   ✅ Preço matriz: R$ {preco_matriz:.2f}")
    else:
        print(f"   ❌ Não foi possível obter preço do site matriz")
    
    # Obter preço via script Python
    print(f"\n🐍 Obtendo preço via script Python...")
    preco_script = obter_preco_script_python(slug, opcoes)
    
    if preco_script:
        print(f"   ✅ Preço script: R$ {preco_script:.2f}")
    else:
        print(f"   ❌ Não foi possível obter preço via script Python")
    
    # Comparar
    print(f"\n📊 COMPARAÇÃO:")
    if preco_matriz and preco_script:
        diferenca = abs(preco_matriz - preco_script)
        percentual = (diferenca / preco_matriz) * 100 if preco_matriz > 0 else 0
        
        if diferenca < 0.01:
            print(f"   ✅ PREÇOS BATERAM! (diferença: R$ {diferenca:.2f})")
            return True
        else:
            print(f"   ⚠️  PREÇOS DIFERENTES!")
            print(f"      Matriz: R$ {preco_matriz:.2f}")
            print(f"      Script: R$ {preco_script:.2f}")
            print(f"      Diferença: R$ {diferenca:.2f} ({percentual:.2f}%)")
            return False
    else:
        print(f"   ❌ Não foi possível comparar (faltam dados)")
        return False

def main():
    print("="*80)
    print("TESTE DE PREÇOS ALEATÓRIOS - Site Matriz vs Script Python (VPS)")
    print("="*80)
    
    # Testar vários produtos
    produtos_para_testar = [
        'impressao-de-revista',
        'impressao-de-tabloide',
    ]
    
    resultados = []
    num_testes = 5  # Número de testes por produto
    
    for produto in produtos_para_testar:
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

