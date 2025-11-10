"""
Captura o payload EXATO que a matriz envia para a API quando essa combinação é selecionada
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
import time
import json

def setup_driver():
    """Configura o driver do Selenium com logging de rede"""
    chrome_options = Options()
    # NÃO usar headless para ver o que está acontecendo
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
    
    # Habilitar logging de rede
    caps = DesiredCapabilities.CHROME
    caps['goog:loggingPrefs'] = {'performance': 'ALL'}
    
    try:
        driver = webdriver.Chrome(options=chrome_options, desired_capabilities=caps)
        return driver
    except Exception as e:
        print(f"Erro ao configurar driver: {e}")
        return None

def capturar_payload_matriz():
    """
    Captura o payload exato que a matriz envia
    """
    print("Acessando pagina matriz...\n")
    
    driver = setup_driver()
    if not driver:
        return None
    
    try:
        url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro"
        driver.get(url)
        time.sleep(5)
        
        print("Pagina carregada\n")
        print("Aguardando interacao do usuario...\n")
        print("Por favor, selecione a combinacao problematica na pagina que abriu.\n")
        print("Quando terminar, pressione ENTER aqui...")
        input()
        
        print("\nCapturando requisicoes de rede...\n")
        
        # Capturar logs de performance (requisições de rede)
        logs = driver.get_log('performance')
        payloads_capturados = []
        
        for log in logs:
            message = json.loads(log['message'])
            method = message.get('message', {}).get('method', '')
            
            if method == 'Network.requestWillBeSent':
                request = message.get('message', {}).get('params', {}).get('request', {})
                url_request = request.get('url', '')
                
                # Procurar requisições para /pricing
                if '/pricing' in url_request:
                    post_data = request.get('postData', '')
                    if post_data:
                        try:
                            payload = json.loads(post_data)
                            payloads_capturados.append({
                                'url': url_request,
                                'payload': payload
                            })
                            print("Payload capturado!")
                            print(f"   URL: {url_request}")
                            print(f"   Q1: {payload.get('pricingParameters', {}).get('Q1', 'N/A')}")
                            print(f"   Options count: {len(payload.get('pricingParameters', {}).get('Options', []))}")
                            print()
                        except:
                            pass
        
        if payloads_capturados:
            # Pegar o último payload (mais recente)
            ultimo_payload = payloads_capturados[-1]
            return ultimo_payload['payload']
        else:
            print("Nenhum payload capturado. Tentando metodo alternativo...\n")
            
            # Método alternativo: executar JavaScript para interceptar fetch/XMLHttpRequest
            script = """
            (function() {
                var originalFetch = window.fetch;
                var originalXHR = window.XMLHttpRequest.prototype.open;
                var payloads = [];
                
                window.fetch = function(...args) {
                    if (args[0].includes('/pricing')) {
                        if (args[1] && args[1].body) {
                            try {
                                var payload = JSON.parse(args[1].body);
                                payloads.push(payload);
                                console.log('PAYLOAD_CAPTURADO:', JSON.stringify(payload));
                            } catch(e) {}
                        }
                    }
                    return originalFetch.apply(this, args);
                };
                
                window.XMLHttpRequest.prototype.open = function(method, url, ...rest) {
                    if (url.includes('/pricing')) {
                        this.addEventListener('load', function() {
                            if (this.responseText) {
                                try {
                                    var payload = JSON.parse(this.responseText);
                                    payloads.push(payload);
                                    console.log('PAYLOAD_CAPTURADO_XHR:', JSON.stringify(payload));
                                } catch(e) {}
                            }
                        });
                    }
                    return originalXHR.apply(this, [method, url, ...rest]);
                };
                
                return payloads;
            })();
            """
            
            driver.execute_script(script)
            time.sleep(2)
            
            # Verificar console logs
            console_logs = driver.get_log('browser')
            for log in console_logs:
                if 'PAYLOAD_CAPTURADO' in log['message']:
                    payload_str = log['message'].split('PAYLOAD_CAPTURADO:')[1].strip()
                    try:
                        payload = json.loads(payload_str)
                        return payload
                    except:
                        pass
        
        return None
        
    except Exception as e:
        print(f"❌ Erro: {e}")
        import traceback
        traceback.print_exc()
        return None
    finally:
        print("\n⚠️ Mantendo navegador aberto por 30 segundos para inspeção...")
        time.sleep(30)
        driver.quit()

def main():
    print("=" * 70)
    print("CAPTURAR PAYLOAD REAL DA MATRIZ")
    print("=" * 70)
    print()
    print("Este script vai abrir o navegador na página matriz.")
    print("Você deve selecionar a combinação problemática manualmente.")
    print("Quando terminar, pressione ENTER aqui para capturar o payload.\n")
    
    payload = capturar_payload_matriz()
    
    if payload:
        print("\n" + "=" * 70)
        print("PAYLOAD CAPTURADO")
        print("=" * 70)
        print()
        print(json.dumps(payload, indent=2, ensure_ascii=False))
        print()
        
        # Salvar em arquivo
        with open('payload_capturado_matriz.json', 'w', encoding='utf-8') as f:
            json.dump(payload, f, indent=2, ensure_ascii=False)
        
        print("Payload salvo em: payload_capturado_matriz.json")
        
        # Comparar com o que estamos enviando
        print("\n" + "=" * 70)
        print("COMPARANDO COM NOSSO PAYLOAD")
        print("=" * 70)
        print()
        
        # Carregar nosso payload
        nosso_payload = {
            'pricingParameters': {
                'KitParameters': None,
                'Q1': '50',
                'Options': [
                    {'Key': '8507966BFD1CED08D52954CA1BFBAFAC', 'Value': '158x230mm'},
                    {'Key': '9DD0C964AA872B2B8F882356423C922D', 'Value': 'Couche Brilho 210gr '},
                    {'Key': 'F54EB0969F0ACEBD67F0722A3FF633F3', 'Value': '5 cores Frente x 1 cor Preto Verso'},
                    {'Key': 'FC83B57DD0039A0D73EC0FB9F63BDB59', 'Value': 'COM Orelha de 9cm'},
                    {'Key': '9D50176D0602173B5575AC4A62173EA2', 'Value': 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)'},
                    {'Key': '2913797D83A57041C2A87BED6F1FEDA9', 'Value': 'Impressão Offset - >500unidades'},
                    {'Key': 'E90F9B0C705E3F28CE0D3B51613AE230', 'Value': '4 cores frente e verso'},
                    {'Key': 'CFAB249F3402BE020FEFFD84CB991DAA', 'Value': 'SIM'},
                    {'Key': 'FCDF130D17B1F0C1FB2503C6F33559D7', 'Value': 'Miolo 944 páginas'},
                    {'Key': 'AFF7AA292FE40E02A7B255713E731899', 'Value': 'Dobrado'},
                    {'Key': '3E9AFD1A94DA1802222717C0AAAC0093', 'Value': 'Capa Dura Papelão 18 (1,8mm) + Cola PUR'},
                    {'Key': '2211AA823438ACBE3BBCE2EF334AC4EA', 'Value': 'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala'},
                    {'Key': '07316319702E082CF6DA43BF4A1C130A', 'Value': 'Shrink Coletivo c/ 50 peças'},
                    {'Key': '9F0D19D9628523760A8B7FF3464C9E9E', 'Value': 'Incluso'},
                    {'Key': 'A1EA4ABCE9F3330525CAD39BE77D01F7', 'Value': 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)'},
                    {'Key': '8C654A289F9D4F2A56C753120083C2ED', 'Value': 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'},
                ]
            }
        }
        
        matriz_options = payload.get('pricingParameters', {}).get('Options', [])
        nosso_options = nosso_payload['pricingParameters']['Options']
        
        print("Diferenças encontradas:\n")
        
        # Comparar cada opção
        for i, (matriz_opt, nosso_opt) in enumerate(zip(matriz_options, nosso_options)):
            if matriz_opt != nosso_opt:
                print(f"[{i}] DIFERENTE:")
                print(f"    Matriz: Key={matriz_opt.get('Key')}, Value='{matriz_opt.get('Value')}'")
                print(f"    Nosso:  Key={nosso_opt.get('Key')}, Value='{nosso_opt.get('Value')}'")
                print()
        
        if len(matriz_options) != len(nosso_options):
            print(f"⚠️ Número de opções diferente: Matriz={len(matriz_options)}, Nosso={len(nosso_options)}")
    else:
        print("❌ Não foi possível capturar o payload")

if __name__ == "__main__":
    main()

