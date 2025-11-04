# 🔧 Atualizar ChromeDriver no VPS

## Problema
ChromeDriver versão 114, mas Chrome versão 142. Incompatibilidade causa erro 500.

## Solução Rápida

Execute no VPS:

```bash
# 1. Verificar versão atual do Chrome
google-chrome --version

# 2. Remover ChromeDriver antigo
sudo rm /usr/local/bin/chromedriver

# 3. Baixar ChromeDriver compatível (versão 142)
CHROME_VERSION=$(google-chrome --version | grep -oP '\d+\.\d+\.\d+\.\d+' | head -1)
CHROMEDRIVER_VERSION=$(curl -s "https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json" | grep -oP '"version": "\K[^"]+' | head -1)

# Baixar ChromeDriver para Linux 64-bit
wget -O /tmp/chromedriver.zip "https://storage.googleapis.com/chrome-for-testing-public/${CHROME_VERSION}/linux64/chromedriver-linux64.zip"

# Ou usar versão mais recente disponível
wget -O /tmp/chromedriver.zip "https://edgedl.me.gvt1.com/edgedl/chrome/chrome-for-testing/142.0.7444.59/linux64/chromedriver-linux64.zip"

# 4. Extrair e instalar
cd /tmp
unzip -o chromedriver.zip
sudo mv chromedriver-linux64/chromedriver /usr/local/bin/chromedriver
sudo chmod +x /usr/local/bin/chromedriver

# 5. Verificar instalação
chromedriver --version
```

## Solução Alternativa (Mais Simples)

```bash
# Instalar ChromeDriver usando webdriver-manager (Python)
cd /www/wwwroot/grafica
pip3 install webdriver-manager

# OU usar o script de atualização automática abaixo
```

## Script de Atualização Automática

```bash
#!/bin/bash
# Atualizar ChromeDriver automaticamente

CHROME_VERSION=$(google-chrome --version | grep -oP '\d+\.\d+\.\d+\.\d+' | head -1)
echo "Chrome versão: $CHROME_VERSION"

# Extrair versão major (ex: 142)
MAJOR_VERSION=$(echo $CHROME_VERSION | cut -d. -f1)

# Baixar ChromeDriver compatível
cd /tmp
rm -f chromedriver.zip chromedriver-linux64.zip

# Tentar baixar versão específica
wget -O chromedriver.zip "https://storage.googleapis.com/chrome-for-testing-public/${CHROME_VERSION}/linux64/chromedriver-linux64.zip" 2>/dev/null

# Se falhar, tentar última versão estável
if [ ! -f chromedriver.zip ] || [ ! -s chromedriver.zip ]; then
    echo "Tentando última versão estável..."
    wget -O chromedriver.zip "https://edgedl.me.gvt1.com/edgedl/chrome/chrome-for-testing/${CHROME_VERSION}/linux64/chromedriver-linux64.zip" 2>/dev/null
fi

if [ -f chromedriver.zip ] && [ -s chromedriver.zip ]; then
    unzip -o chromedriver.zip
    sudo mv chromedriver-linux64/chromedriver /usr/local/bin/chromedriver
    sudo chmod +x /usr/local/bin/chromedriver
    echo "✓ ChromeDriver atualizado!"
    chromedriver --version
else
    echo "✗ Erro ao baixar ChromeDriver. Tente manualmente."
fi
```

## Solução Definitiva: Usar webdriver-manager no Python

Modificar o script Python para baixar ChromeDriver automaticamente:

```python
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager

# Usar webdriver-manager para baixar automaticamente
service = Service(ChromeDriverManager().install())
options = Options()
options.add_argument('--headless')
options.add_argument('--no-sandbox')
options.add_argument('--disable-dev-shm-usage')

driver = webdriver.Chrome(service=service, options=options)
```

## Verificar Após Instalação

```bash
# Testar script Python
cd /www/wwwroot/grafica
python3 scrapper/scrape_tempo_real.py '{"opcoes":{"quantity":50},"quantidade":50}'
```

Se funcionar, o erro 500 deve desaparecer.

