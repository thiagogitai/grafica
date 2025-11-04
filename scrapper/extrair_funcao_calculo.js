/**
 * Script JavaScript para ser executado no console do navegador
 * para descobrir a função de cálculo de preços
 * 
 * Cole este código no Console do DevTools (F12 > Console)
 */

// 1. Listar todas as funções relacionadas a cálculo
console.log("=== FUNCOES RELACIONADAS A CALCULO ===");
var funcoes = [];
for (var prop in window) {
    if (typeof window[prop] === 'function') {
        var nome = prop.toLowerCase();
        if (nome.includes('calc') || nome.includes('price') || nome.includes('preco') || 
            nome.includes('total') || nome.includes('update')) {
            funcoes.push(prop);
        }
    }
}
console.log("Funcoes encontradas:", funcoes);

// 2. Procurar por elementos que podem ter funções de cálculo
console.log("\n=== ELEMENTOS COM EVENTOS DE CALCULO ===");
var elementos = document.querySelectorAll('*');
var elementosComEventos = [];
elementos.forEach(function(elem) {
    var onclick = elem.getAttribute('onclick');
    if (onclick && (onclick.includes('calc') || onclick.includes('price'))) {
        elementosComEventos.push({
            elemento: elem.tagName + (elem.id ? '#' + elem.id : ''),
            onclick: onclick
        });
    }
});
console.log(elementosComEventos);

// 3. Procurar por scripts inline que contenham cálculo
console.log("\n=== SCRIPTS INLINE COM CALCULO ===");
var scripts = document.querySelectorAll('script');
var scriptsRelevantes = [];
scripts.forEach(function(script, index) {
    var codigo = script.innerHTML;
    if (codigo && (codigo.includes('calculate') || codigo.includes('price') || 
        codigo.includes('preco') || codigo.includes('total'))) {
        scriptsRelevantes.push({
            index: index,
            codigo: codigo.substring(0, 500) // Primeiros 500 chars
        });
    }
});
console.log(scriptsRelevantes);

// 4. Tentar encontrar o elemento de preço e observar mudanças
console.log("\n=== ELEMENTO DE PRECO ===");
var precoElement = document.querySelector('[id*="total"], [id*="price"], [id*="preco"]');
if (precoElement) {
    console.log("Elemento encontrado:", precoElement);
    console.log("ID:", precoElement.id);
    console.log("Classe:", precoElement.className);
    console.log("Texto atual:", precoElement.textContent);
    
    // Observar mudanças
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                console.log("PRECO MUDOU PARA:", precoElement.textContent);
            }
        });
    });
    
    observer.observe(precoElement, {
        childList: true,
        characterData: true,
        subtree: true
    });
    console.log("Observer configurado - altere opcoes e veja as mudancas no console");
} else {
    console.log("Elemento de preco nao encontrado automaticamente");
}

// 5. Procurar por variáveis globais relacionadas a preço
console.log("\n=== VARIAVEIS GLOBAIS ===");
var variaveis = [];
for (var prop in window) {
    var valor = window[prop];
    if (prop.toLowerCase().includes('price') || prop.toLowerCase().includes('preco') ||
        prop.toLowerCase().includes('total') || prop.toLowerCase().includes('calc')) {
        variaveis.push({
            nome: prop,
            tipo: typeof valor,
            valor: typeof valor === 'function' ? '[Function]' : String(valor).substring(0, 100)
        });
    }
}
console.log(variaveis);

// 6. Listar todos os event listeners
console.log("\n=== EVENT LISTENERS ===");
var selects = document.querySelectorAll('select');
selects.forEach(function(select, index) {
    console.log("Select " + index + ":", select.id || select.name, 
                "opcoes:", select.options.length);
});

// 7. Tentar interceptar mudanças de select
console.log("\n=== INTERCEPTANDO MUDANCAS DE SELECT ===");
var selects = document.querySelectorAll('select');
selects.forEach(function(select) {
    var originalChange = select.onchange;
    select.addEventListener('change', function() {
        console.log("SELECT ALTERADO:", select.id || select.name, 
                    "Valor:", select.value);
        setTimeout(function() {
            var preco = document.querySelector('[id*="total"], [id*="price"]');
            if (preco) {
                console.log("Preco apos mudanca:", preco.textContent);
            }
        }, 1000);
    });
});

console.log("\n=== PRONTO ===");
console.log("Agora altere as opcoes no formulario e observe as mensagens no console");

