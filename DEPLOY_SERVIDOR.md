# Checklist para Deploy no Servidor

## ✅ O que funciona no servidor (Linux)

1. **Python 3.8+ funciona perfeitamente**
   - O problema do Python 3.13 é específico do Windows
   - Linux não tem o bug do asyncio/_overlapped

2. **Selenium funciona melhor no Linux**
   - Mais estável que no Windows
   - Melhor performance com Chrome headless

3. **Código já está preparado**
   - Usa `base_path()` do Laravel (funciona em qualquer sistema)
   - Detecta automaticamente Windows vs Linux
   - Usa `python3` no Linux automaticamente

## 📋 Checklist de Instalação no Servidor

### 1. Instalar Python e dependências
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install python3 python3-pip
pip3 install selenium

# Ou com venv (recomendado)
python3 -m venv venv
source venv/bin/activate
pip install selenium
```

### 2. Instalar Chrome e ChromeDriver
```bash
# Chrome
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo dpkg -i google-chrome-stable_current_amd64.deb
sudo apt-get install -f

# ChromeDriver
CHROMEDRIVER_VERSION=$(curl -sS chromedriver.storage.googleapis.com/LATEST_RELEASE)
wget https://chromedriver.storage.googleapis.com/$CHROMEDRIVER_VERSION/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/local/bin/
sudo chmod +x /usr/local/bin/chromedriver
```

### 3. Verificar permissões
```bash
# Verificar se o usuário do servidor web pode executar Python
sudo -u www-data python3 --version

# Verificar ChromeDriver
chromedriver --version
```

### 4. Testar script manualmente
```bash
cd /caminho/do/projeto/scrapper
python3 scrape_tempo_real.py '{"opcoes":{"quantity":50},"quantidade":50}'
```

## 🔧 Configurações do Laravel

### Permissões necessárias
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Variáveis de ambiente
Verificar se `APP_DEBUG=false` em produção

## ⚠️ Observações Importantes

1. **No servidor Linux, o código vai usar `python3` automaticamente**
2. **Não precisa do wrapper `.bat` no Linux**
3. **Chrome headless funciona perfeitamente no Linux**
4. **Timeout de 120 segundos é suficiente para scraping**

## 🧪 Testar após deploy

1. Acessar produto no servidor
2. Selecionar opções
3. Verificar se o preço aparece após validação
4. Verificar logs: `storage/logs/laravel.log`

