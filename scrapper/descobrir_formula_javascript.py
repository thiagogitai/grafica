"""
Script para descobrir a fórmula JavaScript de cálculo de preços
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json
import re

def extrair_funcoes_javascript(driver):
    """Extrai funções JavaScript da página"""
    print("\n" + "="*70)
    print("EXTRAINDO FUNCOES JAVASCRIPT")
    print("="*70)
    
    # Executar JavaScript para listar funções globais
    funcoes_calculo = driver.execute_script("""
        var funcoes = [];
        for (var prop in window) {
            if (typeof window[prop] === 'function') {
                var nome = prop.toLowerCase();
                if (nome.includes('calc') || nome.includes('price') || nome.includes('preco') || 
                    nome.includes('total') || nome.includes('update')) {
                    try {
                        var codigo = window[prop].toString();
                        funcoes.push({
                            nome: prop,
                            codigo: codigo.substring(0, 1000) // Primeiros 1000 chars
                        });
                    } catch(e) {}
                }
            }
        }
        return funcoes;
    """)
    
    if funcoes_calculo:
        print(f"\nEncontradas {len(funcoes_calculo)} funcoes relacionadas a calculo:")
        for func in funcoes_calculo:
            print(f"\n  Funcao: {func['nome']}")
            print(f"  Codigo (inicio):\n{func['codigo'][:300]}")
    else:
        print("\nNenhuma funcao global encontrada")
    
    return funcoes_calculo

def analisar_mudancas_preco(driver, preco_element):
    """Analisa como o preço muda ao alterar opções"""
    print("\n" + "="*70)
    print("ANALISE DE MUDANCAS DE PRECO")
    print("="*70)
    
    resultados_teste = []
    
    # Encontrar campo de quantidade
    try:
        qtd_field = driver.find_element(By.XPATH, "//input[@type='number']")
        print("\nTestando mudancas de quantidade...")
        
        for qtd in ["50", "100", "200", "500"]:
            qtd_field.clear()
            qtd_field.send_keys(qtd)
            time.sleep(2)
            preco = preco_element.text
            resultados_teste.append({
                'campo': 'quantity',
                'valor': qtd,
                'preco': preco
            })
            print(f"  Qtd {qtd}: {preco}")
    except Exception as e:
        print(f"Erro ao testar quantidade: {e}")
    
    # Encontrar selects e testar mudanças
    selects = driver.find_elements(By.TAG_NAME, 'select')
    print(f"\nTestando mudancas em {len(selects)} campos select...")
    
    for i, select in enumerate(selects[:5]):  # Testar apenas os 5 primeiros
        select_id = select.get_attribute('id') or select.get_attribute('name') or f'select_{i}'
        print(f"\n  Campo: {select_id}")
        
        try:
            opcoes = select.find_elements(By.TAG_NAME, 'option')
            if len(opcoes) > 1:
                # Opção 1
                Select(select).select_by_index(0)
                time.sleep(1.5)
                preco1 = preco_element.text
                
                # Opção 2
                Select(select).select_by_index(1)
                time.sleep(1.5)
                preco2 = preco_element.text
                
                if preco1 != preco2:
                    print(f"    Preco muda: {preco1} -> {preco2}")
                    resultados_teste.append({
                        'campo': select_id,
                        'opcao1': opcoes[0].get_attribute('value'),
                        'preco1': preco1,
                        'opcao2': opcoes[1].get_attribute('value'),
                        'preco2': preco2
                    })
                else:
                    print(f"    Preco nao muda: {preco1}")
        except Exception as e:
            print(f"    Erro: {e}")
    
    return resultados_teste

def capturar_requisicoes_rede(driver):
    """Captura requisições de rede usando logs do Chrome"""
    print("\n" + "="*70)
    print("ANALISANDO REQUISICOES DE REDE")
    print("="*70)
    
    try:
        logs = driver.get_log('performance')
        requisicoes = []
        
        for log in logs:
            try:
                message = json.loads(log['message'])
                method = message.get('message', {}).get('method', '')
                
                if method == 'Network.requestWillBeSent':
                    url = message.get('message', {}).get('params', {}).get('request', {}).get('url', '')
                    if any(keyword in url.lower() for keyword in ['api', 'calc', 'price', 'preco', 'calculate']):
                        requisicoes.append({
                            'url': url,
                            'method': message.get('message', {}).get('params', {}).get('request', {}).get('method', ''),
                            'postData': message.get('message', {}).get('params', {}).get('request', {}).get('postData', '')
                        })
                
                elif method == 'Network.responseReceived':
                    url = message.get('message', {}).get('params', {}).get('response', {}).get('url', '')
                    if any(keyword in url.lower() for keyword in ['api', 'calc', 'price', 'preco', 'calculate']):
                        status = message.get('message', {}).get('params', {}).get('response', {}).get('status', 0)
                        requisicoes.append({
                            'url': url,
                            'status': status
                        })
            except:
                continue
        
        if requisicoes:
            print(f"\nEncontradas {len(requisicoes)} requisicoes relevantes:")
            for req in requisicoes[:10]:
                print(f"  {req.get('method', 'GET')} {req.get('url', '')[:80]}")
                if req.get('postData'):
                    print(f"    Payload: {req['postData'][:100]}")
        else:
            print("\nNenhuma requisicao de API encontrada")
            print("O calculo provavelmente e feito apenas no JavaScript do cliente")
        
        return requisicoes
    except Exception as e:
        print(f"Erro ao capturar requisicoes: {e}")
        return []

def extrair_codigo_javascript_completo(driver):
    """Tenta extrair todo o JavaScript relacionado a cálculo"""
    print("\n" + "="*70)
    print("EXTRAINDO CODIGO JAVASCRIPT COMPLETO")
    print("="*70)
    
    # Buscar por scripts inline
    scripts = driver.find_elements(By.TAG_NAME, 'script')
    codigo_relevante = []
    
    for i, script in enumerate(scripts):
        codigo = script.get_attribute('innerHTML') or ''
        if codigo and any(keyword in codigo.lower() for keyword in ['calculate', 'price', 'preco', 'total', 'calc']):
            codigo_relevante.append({
                'script_num': i,
                'tipo': 'inline',
                'codigo': codigo
            })
    
    # Buscar por arquivos JavaScript externos
    scripts_externos = driver.execute_script("""
        var scripts = [];
        document.querySelectorAll('script[src]').forEach(function(script) {
            var src = script.src;
            if (src.includes('calc') || src.includes('price') || src.includes('preco') || 
                src.includes('product') || src.includes('livro')) {
                scripts.push(src);
            }
        });
        return scripts;
    """)
    
    print(f"\nEncontrados {len(codigo_relevante)} scripts inline relevantes")
    print(f"Encontrados {len(scripts_externos)} scripts externos relevantes")
    
    if scripts_externos:
        print("\nScripts externos que podem conter logica:")
        for src in scripts_externos:
            print(f"  {src}")
    
    # Salvar código relevante
    if codigo_relevante:
        with open('codigo_javascript_extraido.json', 'w', encoding='utf-8') as f:
            json.dump(codigo_relevante, f, ensure_ascii=False, indent=2)
        print("\nCodigo JavaScript relevante salvo em: codigo_javascript_extraido.json")
    
    return codigo_relevante, scripts_externos

def descobrir_formula():
    """Função principal para descobrir a fórmula"""
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    options = Options()
    options.add_argument("--start-maximized")
    options.add_argument('--enable-logging')
    options.add_argument('--v=1')
    options.set_capability('goog:loggingPrefs', {'performance': 'ALL'})
    
    driver = webdriver.Chrome(options=options)
    
    try:
        print("Acessando o site...")
        driver.get(url)
        time.sleep(5)
        
        # Aceitar cookies
        try:
            cookie_btn = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Aceitar')]"))
            )
            cookie_btn.click()
            time.sleep(1)
        except:
            pass
        
        # Encontrar elemento de preço
        print("\nProcurando elemento de preco...")
        preco_element = None
        seletores = [
            "//*[contains(@id, 'calc-total')]",
            "//*[contains(@id, 'total') and contains(text(), 'R$')]",
            "//span[contains(text(), 'R$')]",
            "//div[contains(text(), 'R$')]",
        ]
        
        for seletor in seletores:
            try:
                elementos = driver.find_elements(By.XPATH, seletor)
                for elem in elementos:
                    if 'R$' in elem.text and len(elem.text) < 50:
                        preco_element = elem
                        print(f"OK - Preco encontrado: {elem.get_attribute('id')}, Texto: {elem.text}")
                        break
                if preco_element:
                    break
            except:
                continue
        
        if not preco_element:
            print("ERRO - Elemento de preco nao encontrado")
            print("A pagina pode ter uma estrutura diferente")
            input("Pressione ENTER para continuar mesmo assim...")
        
        # 1. Extrair funções JavaScript
        funcoes = extrair_funcoes_javascript(driver)
        
        # 2. Extrair código JavaScript completo
        codigo_inline, scripts_externos = extrair_codigo_javascript_completo(driver)
        
        # 3. Analisar mudanças de preço
        if preco_element:
            mudancas = analisar_mudancas_preco(driver, preco_element)
        else:
            mudancas = []
        
        # 4. Capturar requisições de rede
        requisicoes = capturar_requisicoes_rede(driver)
        
        # Salvar tudo
        resultado_completo = {
            'funcoes_encontradas': funcoes,
            'scripts_externos': scripts_externos,
            'mudancas_preco': mudancas,
            'requisicoes': requisicoes,
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        with open('analise_formula_completa.json', 'w', encoding='utf-8') as f:
            json.dump(resultado_completo, f, ensure_ascii=False, indent=2)
        
        print("\n" + "="*70)
        print("ANALISE COMPLETA SALVA")
        print("="*70)
        print("Arquivo: analise_formula_completa.json")
        
        # Tentar extrair fórmula manualmente
        print("\n" + "="*70)
        print("INSTRUCOES PARA DESCOBRIR A FORMULA MANUALMENTE")
        print("="*70)
        print("""
1. Abra o DevTools (F12) no navegador
2. Vá na aba 'Sources' ou 'Sources'
3. Procure por arquivos JavaScript que contenham:
   - calculatePrice
   - calcPrice  
   - getPrice
   - updatePrice
   - totalPrice

4. Vá na aba 'Console' e tente:
   - window.calculatePrice
   - window.calcPrice
   - Object.keys(window).filter(k => k.includes('calc') || k.includes('price'))

5. Coloque um breakpoint na função de cálculo
6. Altere opções e veja como o preço é calculado
7. Anote a fórmula encontrada

6. Ou use o Network tab para ver se há requisições ao alterar opções
        """)
        
        input("\nPressione ENTER para fechar...")
        
    except Exception as e:
        print(f"ERRO: {e}")
        import traceback
        traceback.print_exc()
    finally:
        driver.quit()

if __name__ == "__main__":
    descobrir_formula()

