# Requisitos para Servidor

## Python e Selenium

### Servidor Linux (Recomendado)
- Python 3.8+ (funciona melhor que 3.13 no Windows)
- Selenium instalado: `pip install selenium`
- ChromeDriver instalado e no PATH
- Chrome/Chromium instalado

### Dependências Python
```bash
pip install selenium
```

### ChromeDriver
```bash
# Ubuntu/Debian
sudo apt-get install chromium-chromedriver

# Ou baixar manualmente
wget https://chromedriver.storage.googleapis.com/LATEST_RELEASE/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/local/bin/
sudo chmod +x /usr/local/bin/chromedriver
```

### Verificar instalação
```bash
python3 --version
python3 -c "from selenium import webdriver; print('Selenium OK')"
chromedriver --version
```

## Laravel

- PHP 8.1+
- Extensões PHP necessárias
- Permissões de escrita em `storage/` e `bootstrap/cache/`

## Observações

1. **O erro do Python 3.13 Windows NÃO deve ocorrer no Linux**
   - O problema do asyncio é específico do Windows
   - Linux geralmente funciona melhor com Selenium

2. **Caminhos no código**
   - O código já usa `base_path()` que funciona em qualquer sistema
   - Não há caminhos hardcoded do Windows

3. **Permissões**
   - Garantir que o usuário do servidor web pode executar Python
   - Garantir que pode criar processos (para executar scripts Python)

