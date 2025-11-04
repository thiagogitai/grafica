#!/bin/bash
# Script para atualizar ChromeDriver no VPS

echo "🔍 Verificando versão do Chrome..."
CHROME_VERSION=$(google-chrome --version | grep -oP '\d+\.\d+\.\d+\.\d+' | head -1)
echo "Chrome versão: $CHROME_VERSION"

if [ -z "$CHROME_VERSION" ]; then
    echo "❌ Não foi possível detectar versão do Chrome"
    exit 1
fi

echo "🗑️  Removendo ChromeDriver antigo..."
sudo rm -f /usr/local/bin/chromedriver

echo "📥 Baixando ChromeDriver compatível..."
cd /tmp
rm -f chromedriver.zip chromedriver-linux64.zip

# Tentar baixar versão específica
wget -q -O chromedriver.zip "https://storage.googleapis.com/chrome-for-testing-public/${CHROME_VERSION}/linux64/chromedriver-linux64.zip" 2>&1

# Se falhar, usar URL alternativa
if [ ! -f chromedriver.zip ] || [ ! -s chromedriver.zip ]; then
    echo "Tentando URL alternativa..."
    wget -q -O chromedriver.zip "https://edgedl.me.gvt1.com/edgedl/chrome/chrome-for-testing/${CHROME_VERSION}/linux64/chromedriver-linux64.zip" 2>&1
fi

if [ ! -f chromedriver.zip ] || [ ! -s chromedriver.zip ]; then
    echo "❌ Erro ao baixar ChromeDriver"
    echo "Tente baixar manualmente de: https://googlechromelabs.github.io/chrome-for-testing/"
    exit 1
fi

echo "📦 Extraindo..."
unzip -o -q chromedriver.zip

echo "📝 Instalando..."
sudo mv chromedriver-linux64/chromedriver /usr/local/bin/chromedriver
sudo chmod +x /usr/local/bin/chromedriver

echo "✅ Verificando instalação..."
chromedriver --version

echo ""
echo "✓ ChromeDriver atualizado com sucesso!"
echo "Teste com: python3 scrapper/scrape_tempo_real.py '{\"opcoes\":{\"quantity\":50},\"quantidade\":50}'"

