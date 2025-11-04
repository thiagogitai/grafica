@echo off
REM Wrapper para executar scrape_tempo_real.py no Python 3.13 Windows
REM Configura variáveis de ambiente para evitar problema com asyncio

set PYTHONUNBUFFERED=1
set PYTHONASYNCIODEBUG=0
set PYTHONIOENCODING=utf-8

python "%~dp0scrape_tempo_real.py" %*

