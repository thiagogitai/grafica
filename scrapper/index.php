<?php
// Carregar os dados de preços do arquivo JSON
$jsonFile = 'precos_grafica.json';
$priceMatrix = [];
if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $priceMatrix = json_decode($jsonData, true);
    if ($priceMatrix === null) {
        // Se não conseguir decodificar, usar array vazio
        $priceMatrix = [];
    }
} else {
    // Se o arquivo não existir, usar array vazio
    $priceMatrix = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Preços de Panfleto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Exo 2', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .loader {
            border-top-color: #3498db;
            -webkit-animation: spin 1s linear infinite;
            animation: spin 1s linear infinite;
        }

        @-webkit-keyframes spin {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="bg-blue-50 text-gray-800">

    <header class="bg-blue-600 shadow-md">
        <div class="container mx-auto p-4">
            <h1 class="text-4xl font-bold text-white">Grafica Online</h1>
        </div>
    </header>

    <div class="container mx-auto p-4 lg:p-8">
        <main class="bg-white rounded-2xl shadow-lg p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

                <!-- Coluna da Esquerda: Imagem e Gabaritos -->
                <div class="lg:col-span-4">
                    <div class="sticky top-8">
                        <div class="aspect-w-1 aspect-h-1 bg-gray-100 rounded-xl overflow-hidden mb-6">
                            <img src="https://www.lojagraficaeskenazi.com.br/files/subscribers/c6a9938c-c430-473f-930d-d2d03c709191/sites/86883fab-bf0e-4dd9-9137-c05ce7a3150f/products/695dd9c8-518f-41ef-aa58-17a8019571aa/Impress%C3%A3odePanfletoOnline_large.png?stamp=636109192712098687" alt="Impressão de Panfleto Online" class="object-contain w-full h-full">
                        </div>
                        <div class="space-y-4 text-center">
                             <a href="#" id="openModalLink" class="inline-flex items-center justify-center gap-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors w-full">
                                <i class="fas fa-download"></i>
                                <span>Baixe o gabarito do produto</span>
                            </a>
                             <a href="mailto:contato@lojagraficaeskenazi.com.br" class="inline-flex items-center justify-center gap-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors w-full">
                                <i class="fas fa-envelope"></i>
                                <span>Envie suas dúvidas</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Coluna do Meio: Calculadora -->
                <div class="lg:col-span-5">
                    <div class="mb-6">
                        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">Impressão de Panfleto Online</h1>
                        <p class="text-gray-600 text-lg">Selecione as informações do produto conforme sua necessidade.</p>
                    </div>

                    <div id="calculator" class="space-y-5">
                        <!-- 1- Quantidade -->
                        <div>
                            <label for="quantity" class="block text-sm font-bold text-gray-700 mb-1">1- Quantidade</label>
                            <select id="quantity" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                                <option value="100">100</option>
                                <option value="500">500</option>
                                <option value="1000">1.000</option>
                                <option value="2000">2.000</option>
                                <option value="5000">5.000</option>
                                <option value="10000">10.000</option>
                            </select>
                        </div>

                        <!-- 2- Papel -->
                        <div>
                            <label for="paper" class="block text-sm font-bold text-gray-700 mb-1">2- Papel</label>
                            <select id="paper" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                                <option value="Couche Brilho 150gr ">Couche Brilho 150gr</option>
                                <option value="Couche Brilho 90gr">Couche Brilho 90gr</option>
                                <option value="Couche Brilho 115gr ">Couche Brilho 115gr</option>
                                <option value="Couche Brilho 210gr ">Couche Brilho 210gr</option>
                                <option value="Offset 75gr ">Offset 75gr</option>
                                <option value="Offset 90gr ">Offset 90gr</option>
                                <option value="Offset 120gr ">Offset 120gr</option>
                                <option value="Offset 180gr ">Offset 180gr</option>
                                <option value="Couche Fosco 150gr ">Couche Fosco 150gr</option>
                                <option value="Cartão Triplex C2S 300gr ">Cartão Triplex C2S 300gr</option>
                            </select>
                        </div>

                        <!-- 3- Formato -->
                        <div>
                            <label for="format" class="block text-sm font-bold text-gray-700 mb-1">3- Formato</label>
                            <select id="format" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                                <option value="A4: 210mmx297mm">A4: 210mm x 297mm</option>
                                <option value="A5: 210mmx148mm">A5: 210mm x 148mm</option>
                                <option value="A6: 105mmx148mm">A6: 105mm x 148mm</option>
                                <option value="DL: 210mmx100mm">DL: 210mm x 100mm</option>
                                <option value="300mmx310mm">300mm x 310mm</option>
                                <option value="420mmx300mm">420mm x 300mm</option>
                                <option value="210mmx210mm">210mm x 210mm</option>
                                <option value="150mmx150mm">150mm x 150mm</option>
                                <option value="90mmx120mm">90mm x 120mm</option>
                                <option value="100mmx100mm">100mm x 100mm</option>
                            </select>
                        </div>

                        <!-- 4- Cores -->
                        <div>
                            <label for="colors" class="block text-sm font-bold text-gray-700 mb-1">4- Cores</label>
                            <select id="colors" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                                 <option value="4 cores frente e verso">4 cores frente e verso</option>
                                <option value="4 cores frente">4 cores frente</option>
                                <option value="1 cor frente preto">1 cor frente preto</option>
                                <option value="1 cor frente e verso preto">1 cor frente e verso preto</option>
                            </select>
                        </div>

                        <!-- 5- Acabamento -->
                         <div>
                            <label for="finishing" class="block text-sm font-bold text-gray-700 mb-1">5- Acabamento</label>
                            <select id="finishing" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                               <option value="0">Cantos Retos</option>
                               <option value="0">Cantos Retos + Laminação FOSCO Bopp F/V (acima de 200g)</option>
                               <option value="0">Cantos Retos + Laminação BRILHO Bopp F/V (Acima de 200g)</option>
                            </select>
                        </div>
                        
                        <!-- 7- Formato do Arquivo -->
                         <div>
                            <label for="file_format" class="block text-sm font-bold text-gray-700 mb-1">7- Formato do Arquivo</label>
                            <select id="file_format" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                               <option value="0">Arquivo PDF (fechado para impressão) (Grátis)</option>
                               <option value="80">Arquvo CDR / INDD / AI / JPG / PNG (aberto) (R$ 80,00)</option>
                            </select>
                        </div>

                        <!-- 8- Verificação do Arquivo -->
                         <div>
                            <label for="file_check" class="block text-sm font-bold text-gray-700 mb-1">8- Verificação do Arquivo</label>
                            <select id="file_check" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3">
                               <option value="0">Digital On-Line (Grátis)</option>
                               <option value="150">Prova de Cor Impressa - SOMENTE para São Paulo (R$ 150,00)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Coluna da Direita: Preço e Ações -->
                <div class="lg:col-span-3">
                    <div class="sticky top-8 bg-gray-50 rounded-2xl p-6 shadow-md">
                        <div class="text-center mb-4">
                            <p class="text-base text-gray-600">Valor desse pedido:</p>
                            <p id="total-price" class="text-4xl lg:text-5xl font-bold text-blue-600 my-2">R$0,00</p>
                            <p class="text-sm text-gray-500">valor de cada unidade: <span id="unit-price">R$0,00</span></p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="file-upload" class="text-center block text-sm font-medium text-gray-700 mb-2">Faça upload da sua arte:</label>
                                <input id="file-upload" type="file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100"/>
                            </div>
                            <button class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 px-4 rounded-lg text-lg transition-all shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50 transform hover:-translate-y-0.5">
                                <i class="fas fa-shopping-cart mr-2"></i>Adicionar ao carrinho
                            </button>
                        </div>
                        <div class="mt-6 text-center">
                            <p class="text-green-600 font-semibold"><i class="fas fa-truck mr-2"></i>Frete grátis para todo Brasil!</p>
                             <p class="text-xs text-red-600 mt-2">*Prazo para a análise do arquivo é de 1 dia útil</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Gabaritos -->
    <div id="gabaritoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all" id="modal-content">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Gabaritos para Panfleto</h2>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times fa-2x"></i>
                </button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-sm font-semibold text-gray-600">Formato</th>
                            <th class="p-3 text-sm font-semibold text-gray-600 text-center" colspan="3">Arquivos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- Os dados da tabela original serão inseridos aqui -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <script>


        const priceMatrix = <?php echo json_encode($priceMatrix); ?>;

        const gabaritoData = [
            { size: 'A4 - 210mm x 297mm', ai: '#', pdf: '#', eps: '#' },
            { size: 'A5 - 148mm x 210mm', ai: '#', pdf: '#', eps: '#' },
            { size: 'A6 - 105mm x 148mm', ai: '#', pdf: '#', eps: '#' },
            { size: 'DL - 100mm x 210mm', ai: '#', pdf: '#', eps: '#' },
        ];


        document.addEventListener('DOMContentLoaded', () => {
            const selects = document.querySelectorAll('#calculator select');
            const totalPriceEl = document.getElementById('total-price');
            const unitPriceEl = document.getElementById('unit-price');

            // --- Funções ---
            function formatCurrency(value) {
                if (typeof value !== 'number' || isNaN(value)) {
                    return "R$ --,--";
                }
                return value.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
            }

            function calculatePrice() {
                try {
                    const quantity = document.getElementById('quantity').value;
                    const paper = document.getElementById('paper').value;
                    const format = document.getElementById('format').value;
                    const colors = document.getElementById('colors').value;
                    
                    const finishingCost = parseFloat(document.getElementById('finishing').value);
                    const fileFormatCost = parseFloat(document.getElementById('file_format').value);
                    const fileCheckCost = parseFloat(document.getElementById('file_check').value);

                    let basePrice = 0;
                    
                    // Busca na matriz de preços
                    if (priceMatrix[quantity] && priceMatrix[quantity][format] && priceMatrix[quantity][format][paper] && typeof priceMatrix[quantity][format][paper][colors] !== 'undefined') {
                        basePrice = priceMatrix[quantity][format][paper][colors];
                    } else {
                        totalPriceEl.textContent = 'Preço Indisponível';
                        unitPriceEl.textContent = 'R$ --,--';
                        console.error('Combinação não encontrada:', {quantity, format, paper, colors});
                        return;
                    }

                    const total = basePrice + finishingCost + fileFormatCost + fileCheckCost;
                    const unitPrice = total / parseInt(quantity);

                    totalPriceEl.textContent = formatCurrency(total);
                    unitPriceEl.textContent = formatCurrency(unitPrice);
                } catch (error) {
                    console.error("Erro ao calcular o preço:", error);
                    totalPriceEl.textContent = 'Erro no Cálculo';
                    unitPriceEl.textContent = 'R$ --,--';
                }
            }

            // --- Modal ---
            const modal = document.getElementById('gabaritoModal');
            const modalContent = document.getElementById('modal-content');
            const openModalLink = document.getElementById('openModalLink');
            const closeModalBtn = document.getElementById('closeModalBtn');

            const openModal = () => modal.classList.remove('hidden');
            const closeModal = () => modal.classList.add('hidden');

            openModalLink.addEventListener('click', (e) => {
              e.preventDefault();
              openModal();
            });
            closeModalBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // --- Preencher tabela do Modal ---
            const tableBody = modal.querySelector('tbody');
            tableBody.innerHTML = gabaritoData.map(item => `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 font-medium">${item.size}</td>
                    <td class="p-3 text-center"><a href="${item.ai}" class="text-red-500 hover:underline">AI</a></td>
                    <td class="p-3 text-center"><a href="${item.pdf}" class="text-red-500 hover:underline">PDF</a></td>
                    <td class="p-3 text-center"><a href="${item.eps}" class="text-red-500 hover:underline">EPS</a></td>
                </tr>
            `).join('');


            // --- Event Listeners ---
            selects.forEach(select => {
                select.addEventListener('change', calculatePrice);
            });

            // Calcular preço inicial
            calculatePrice();
        });
    </script>

</body>

</html>

