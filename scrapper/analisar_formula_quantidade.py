"""
Script para analisar a fórmula de cálculo começando com quantidade 50
e testando sistematicamente mudanças
"""
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json
import re

def extrair_valor_preco(texto):
    """Extrai valor numérico do preço (remove R$ e formatação)"""
    if not texto:
        return None
    # Remove R$, pontos, espaços e converte vírgula em ponto
    valor = re.sub(r'[R$\s.]', '', texto)
    valor = valor.replace(',', '.')
    try:
        return float(valor)
    except:
        return None

def analisar_formula_com_quantidade():
    """Analisa a fórmula começando com quantidade 50"""
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    options = Options()
    options.add_argument("--start-maximized")
    options.add_argument('--enable-logging')
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
        preco_element = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "calc-total"))
        )
        print(f"OK - Elemento encontrado: calc-total")
        
        # Encontrar campo de quantidade (pode ser select ou input)
        qtd_field = None
        campo_tipo = None
        
        # Tentar vários seletores
        seletores = [
            ("select", "//select[contains(@id, 'quantity') or contains(@name, 'quantity')]"),
            ("select", "//select[contains(@id, 'Options')]"),
            ("select", "//select[contains(@id, '0')]"),  # Primeiro select pode ser quantidade
            ("input", "//input[@type='number']"),
            ("input", "//input[contains(@id, 'quantity')]"),
            ("input", "//input[contains(@name, 'quantity')]"),
        ]
        
        for tipo, seletor in seletores:
            try:
                qtd_field = driver.find_element(By.XPATH, seletor)
                campo_tipo = tipo
                print(f"OK - Campo quantidade encontrado ({tipo}): {qtd_field.get_attribute('id') or qtd_field.get_attribute('name')}")
                break
            except:
                continue
        
        if not qtd_field:
            # Listar todos os selects para debug
            selects = driver.find_elements(By.TAG_NAME, 'select')
            print(f"\nDEBUG - Encontrados {len(selects)} campos select na pagina:")
            for i, sel in enumerate(selects):
                print(f"  Select {i}: id={sel.get_attribute('id')}, name={sel.get_attribute('name')}")
            
            # Tentar usar o primeiro select como quantidade
            if selects:
                qtd_field = selects[0]
                campo_tipo = 'select'
                print(f"Usando primeiro select como quantidade: {selects[0].get_attribute('id') or selects[0].get_attribute('name')}")
            else:
                print("ERRO - Campo de quantidade nao encontrado")
                return
        
        # Definir quantidade inicial como 50
        print("\n" + "="*70)
        print("DEFININDO QUANTIDADE INICIAL: 50")
        print("="*70)
        
        if campo_tipo == 'select':
            try:
                Select(qtd_field).select_by_value("50")
            except:
                # Tentar por texto
                try:
                    Select(qtd_field).select_by_visible_text("50")
                except:
                    # Tentar por índice
                    opcoes = qtd_field.find_elements(By.TAG_NAME, 'option')
                    for i, opt in enumerate(opcoes):
                        if "50" in opt.text or opt.get_attribute('value') == "50":
                            Select(qtd_field).select_by_index(i)
                            break
        else:
            qtd_field.clear()
            qtd_field.send_keys("50")
        
        time.sleep(2)
        preco_inicial = preco_element.text
        valor_inicial = extrair_valor_preco(preco_inicial)
        print(f"Preco inicial (qtd 50): {preco_inicial} (R$ {valor_inicial:.2f})")
        
        # Testar diferentes quantidades
        print("\n" + "="*70)
        print("TESTANDO DIFERENTES QUANTIDADES")
        print("="*70)
        
        quantidades = ["50", "100", "150", "200", "250", "500", "1000"]
        resultados_qtd = []
        
        for qtd in quantidades:
            if campo_tipo == 'select':
                try:
                    Select(qtd_field).select_by_value(qtd)
                except:
                    try:
                        Select(qtd_field).select_by_visible_text(qtd)
                    except:
                        opcoes = qtd_field.find_elements(By.TAG_NAME, 'option')
                        for i, opt in enumerate(opcoes):
                            if qtd in opt.text or opt.get_attribute('value') == qtd:
                                Select(qtd_field).select_by_index(i)
                                break
            else:
                qtd_field.clear()
                qtd_field.send_keys(qtd)
            
            time.sleep(2)
            preco_texto = preco_element.text
            valor = extrair_valor_preco(preco_texto)
            
            resultados_qtd.append({
                'quantidade': int(qtd),
                'preco_texto': preco_texto,
                'preco_valor': valor
            })
            
            if valor_inicial:
                relacao = valor / valor_inicial if valor_inicial else None
                print(f"  Qtd {qtd}: {preco_texto} (R$ {valor:.2f}) - Relacao: {relacao:.2f}x")
            else:
                print(f"  Qtd {qtd}: {preco_texto} (R$ {valor:.2f})")
        
        # Analisar padrão de quantidade
        print("\n" + "="*70)
        print("ANALISE DE PADRAO DE QUANTIDADE")
        print("="*70)
        
        if len(resultados_qtd) >= 2:
            base_qtd = resultados_qtd[0]['quantidade']
            base_preco = resultados_qtd[0]['preco_valor']
            
            print(f"\nPreco por unidade (base qtd {base_qtd}):")
            for resultado in resultados_qtd:
                qtd = resultado['quantidade']
                preco = resultado['preco_valor']
                if preco and qtd:
                    preco_unitario = preco / qtd
                    print(f"  Qtd {qtd}: R$ {preco_unitario:.4f} por unidade")
                    resultado['preco_unitario'] = preco_unitario
        
        # Voltar para quantidade 50
        print("\nVoltando para quantidade 50...")
        if campo_tipo == 'select':
            try:
                Select(qtd_field).select_by_value("50")
            except:
                opcoes = qtd_field.find_elements(By.TAG_NAME, 'option')
                for i, opt in enumerate(opcoes):
                    if "50" in opt.text or opt.get_attribute('value') == "50":
                        Select(qtd_field).select_by_index(i)
                        break
        else:
            qtd_field.clear()
            qtd_field.send_keys("50")
        
        time.sleep(2)
        
        # Testar mudanças em cada campo select (mantendo qtd 50)
        print("\n" + "="*70)
        print("TESTANDO MUDANCAS EM CAMPOS (QUANTIDADE 50)")
        print("="*70)
        
        selects = driver.find_elements(By.TAG_NAME, 'select')
        resultados_campos = []
        
        for i, select in enumerate(selects):
            select_id = select.get_attribute('id') or select.get_attribute('name') or f'select_{i}'
            
            # Pular campo de quantidade
            if 'quantity' in select_id.lower():
                continue
            
            try:
                opcoes = select.find_elements(By.TAG_NAME, 'option')
                if len(opcoes) < 2:
                    continue
                
                # Primeira opção
                Select(select).select_by_index(0)
                time.sleep(1.5)
                preco1_texto = preco_element.text
                preco1_valor = extrair_valor_preco(preco1_texto)
                
                # Segunda opção
                Select(select).select_by_index(1)
                time.sleep(1.5)
                preco2_texto = preco_element.text
                preco2_valor = extrair_valor_preco(preco2_texto)
                
                if preco1_valor and preco2_valor and preco1_valor != preco2_valor:
                    diferenca = preco2_valor - preco1_valor
                    print(f"\n  Campo: {select_id}")
                    print(f"    Opcao 1: {opcoes[0].get_attribute('value')[:50]} -> {preco1_texto}")
                    print(f"    Opcao 2: {opcoes[1].get_attribute('value')[:50]} -> {preco2_texto}")
                    print(f"    Diferenca: R$ {diferenca:.2f}")
                    
                    resultados_campos.append({
                        'campo': select_id,
                        'opcao1': opcoes[0].get_attribute('value'),
                        'preco1': preco1_valor,
                        'opcao2': opcoes[1].get_attribute('value'),
                        'preco2': preco2_valor,
                        'diferenca': diferenca
                    })
            except Exception as e:
                print(f"  Erro ao testar campo {select_id}: {e}")
        
        # Tentar extrair código JavaScript de cálculo
        print("\n" + "="*70)
        print("EXTRAINDO CODIGO JAVASCRIPT DE CALCULO")
        print("="*70)
        
        codigo_calc = driver.execute_script("""
            // Procurar por funções relacionadas a cálculo
            var funcoes = [];
            for (var prop in window) {
                if (typeof window[prop] === 'function') {
                    var nome = prop.toLowerCase();
                    if (nome.includes('calc') || nome.includes('price') || nome.includes('update')) {
                        try {
                            var codigo = window[prop].toString();
                            funcoes.push({
                                nome: prop,
                                codigo: codigo
                            });
                        } catch(e) {}
                    }
                }
            }
            
            // Procurar por event listeners no elemento de preço
            var precoElem = document.getElementById('calc-total');
            var listeners = [];
            if (precoElem) {
                // Tentar encontrar eventos relacionados
                var selects = document.querySelectorAll('select');
                selects.forEach(function(select, idx) {
                    if (select.onchange) {
                        listeners.push({
                            tipo: 'onchange',
                            elemento: select.id || select.name,
                            codigo: select.onchange.toString().substring(0, 500)
                        });
                    }
                });
            }
            
            return {
                funcoes: funcoes,
                listeners: listeners
            };
        """)
        
        if codigo_calc['funcoes']:
            print(f"\nEncontradas {len(codigo_calc['funcoes'])} funcoes:")
            for func in codigo_calc['funcoes']:
                print(f"  - {func['nome']}")
        
        # Salvar resultados
        resultado_final = {
            'quantidade_inicial': 50,
            'preco_inicial': {
                'texto': preco_inicial,
                'valor': valor_inicial
            },
            'testes_quantidade': resultados_qtd,
            'testes_campos': resultados_campos,
            'codigo_javascript': codigo_calc,
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        with open('analise_formula_qtd50.json', 'w', encoding='utf-8') as f:
            json.dump(resultado_final, f, ensure_ascii=False, indent=2)
        
        print("\n" + "="*70)
        print("ANALISE COMPLETA SALVA")
        print("="*70)
        print("Arquivo: analise_formula_qtd50.json")
        
        # Resumo
        print("\n" + "="*70)
        print("RESUMO DA ANALISE")
        print("="*70)
        print(f"Quantidade inicial: 50")
        print(f"Preco inicial: {preco_inicial}")
        print(f"Quantidades testadas: {len(resultados_qtd)}")
        print(f"Campos testados: {len(resultados_campos)}")
        
        if resultados_qtd:
            print("\nRelacao quantidade x preco:")
            for r in resultados_qtd:
                if r.get('preco_unitario'):
                    print(f"  Qtd {r['quantidade']}: R$ {r['preco_unitario']:.4f}/unidade")
        
        input("\nPressione ENTER para fechar...")
        
    except Exception as e:
        print(f"ERRO: {e}")
        import traceback
        traceback.print_exc()
    finally:
        driver.quit()

if __name__ == "__main__":
    analisar_formula_com_quantidade()

