"""
Captura a estrutura EXATA da página de impressao-de-livro
Incluindo todos os campos, labels e opções na ordem correta
"""
import sys
import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options

def capturar_estrutura_livro():
    """Captura estrutura completa da página de livros"""
    url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
    
    print(f"\n{'='*70}", file=sys.stderr)
    print(f"📚 CAPTURANDO ESTRUTURA EXATA: impressao-de-livro", file=sys.stderr)
    print(f"{'='*70}", file=sys.stderr)
    
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option('useAutomationExtension', False)
    
    driver = webdriver.Chrome(options=options)
    
    try:
        print(f"\n🌐 Acessando: {url}", file=sys.stderr)
        driver.get(url)
        time.sleep(5)
        
        # Aceitar cookies
        try:
            driver.execute_script("""
                var btns = document.querySelectorAll('button');
                for (var btn of btns) {
                    var text = btn.textContent.toLowerCase();
                    if (text.includes('aceitar') || text.includes('accept')) {
                        btn.click();
                        break;
                    }
                }
            """)
            time.sleep(2)
        except:
            pass
        
        # Estrutura do template
        template = {
            'title_override': 'Impressão de Livro Personalizado',
            'base_price': None,
            'redirect_to_upload': True,
            'options': []
        }
        
        # Encontrar todos os campos do formulário
        form = driver.find_element(By.TAG_NAME, 'form')
        
        # Encontrar campo de quantidade
        qtd_input = None
        try:
            qtd_input = driver.find_element(By.ID, 'Q1')
            label_qtd = driver.execute_script("""
                var input = arguments[0];
                var label = input.closest('div').querySelector('label') || 
                           input.previousElementSibling ||
                           input.parentElement.previousElementSibling;
                return label ? label.textContent.trim() : 'Quantidade';
            """, qtd_input)
            
            template['options'].append({
                'name': 'quantity',
                'label': label_qtd if label_qtd else '1- Quantidade',
                'type': 'number',
                'default': 50,
                'min': 50,
                'step': 1
            })
            print(f"✅ Campo quantity: {label_qtd}", file=sys.stderr)
        except:
            print("⚠️  Campo quantity não encontrado", file=sys.stderr)
        
        # Encontrar todos os selects na ordem
        selects = driver.find_elements(By.TAG_NAME, 'select')
        print(f"\n✅ Encontrados {len(selects)} selects", file=sys.stderr)
        
        for idx, select in enumerate(selects):
            try:
                # Obter label
                label = driver.execute_script("""
                    var select = arguments[0];
                    var label = select.closest('div').querySelector('label') ||
                               select.previousElementSibling ||
                               select.parentElement.previousElementSibling;
                    return label ? label.textContent.trim() : '';
                """, select)
                
                if not label:
                    # Tentar pelo ID ou name
                    select_id = select.get_attribute('id') or select.get_attribute('name') or ''
                    label = f"Campo {idx + 2}"
                
                # Obter name do select
                select_name = select.get_attribute('name') or select.get_attribute('id') or f'option_{idx}'
                
                # Remover prefixo se houver
                if select_name.startswith('options[') and select_name.endswith(']'):
                    select_name = select_name.replace('options[', '').replace(']', '')
                
                # Obter todas as opções
                select_obj = Select(select)
                choices = []
                
                for opt in select_obj.options:
                    opt_text = opt.text.strip()
                    opt_value = opt.get_attribute('value') or opt_text
                    
                    if opt_text and opt_text != '-- Selecione --' and opt_text != 'Selecione':
                        choices.append({
                            'value': opt_value,
                            'label': opt_text
                        })
                
                if choices:
                    template['options'].append({
                        'name': select_name,
                        'label': label,
                        'type': 'select',
                        'choices': choices
                    })
                    print(f"   ✅ [{idx}] {select_name}: {len(choices)} opções - {label}", file=sys.stderr)
                else:
                    print(f"   ⚠️  [{idx}] {select_name}: Nenhuma opção válida", file=sys.stderr)
                    
            except Exception as e:
                print(f"   ❌ Erro ao processar select {idx}: {e}", file=sys.stderr)
                continue
        
        print(f"\n{'='*70}", file=sys.stderr)
        print(f"📊 RESULTADO:", file=sys.stderr)
        print(f"   Total de campos: {len(template['options'])}", file=sys.stderr)
        total_opcoes = sum(len(opt.get('choices', [])) for opt in template['options'] if opt.get('type') == 'select')
        print(f"   Total de opções: {total_opcoes}", file=sys.stderr)
        print(f"{'='*70}", file=sys.stderr)
        
        return template
        
    except Exception as e:
        import traceback
        print(f"\n❌ ERRO GERAL: {e}", file=sys.stderr)
        print(traceback.format_exc(), file=sys.stderr)
        return None
    finally:
        driver.quit()

def main():
    template = capturar_estrutura_livro()
    
    if not template:
        print(json.dumps({
            'success': False,
            'error': 'Não foi possível capturar a estrutura'
        }, indent=2))
        sys.exit(1)
    
    # Salvar template
    arquivo = '../resources/data/products/impressao-de-livro.json'
    with open(arquivo, 'w', encoding='utf-8') as f:
        json.dump(template, f, indent=2, ensure_ascii=False)
    
    print(f"\n✅ Template salvo em: {arquivo}", file=sys.stderr)
    
    # Resultado JSON
    print(json.dumps({
        'success': True,
        'total_campos': len(template['options']),
        'campos': [opt['name'] for opt in template['options']]
    }, indent=2, ensure_ascii=False))

if __name__ == "__main__":
    main()

