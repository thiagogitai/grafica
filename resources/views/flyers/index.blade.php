@extends('layouts.app')

@section('content')
<div class="container my-5">
    <h1 class="mb-4">Flyers e Panfletos</h1>
    <form action="{{ route('cart.add.flyer') }}" method="POST" id="flyer-form">
        @csrf
        <input type="hidden" name="details" id="final-details" value="">

        <div class="row g-3">
            <div class="col-md-3">
                <label for="quantity" class="form-label">Quantidade</label>
                <select id="quantity" name="options[quantity]" class="form-select"></select>
            </div>
            <div class="col-md-3">
                <label for="size" class="form-label">Tamanho</label>
                <select id="size" name="options[size]" class="form-select" disabled></select>
            </div>
            <div class="col-md-3">
                <label for="paper" class="form-label">Papel</label>
                <select id="paper" name="options[paper]" class="form-select" disabled></select>
            </div>
            <div class="col-md-3">
                <label for="color" class="form-label">Cores</label>
                <select id="color" name="options[color]" class="form-select" disabled></select>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-md-4">
                <label for="finishing" class="form-label">Acabamento</label>
                <select id="finishing" name="options[finishing]" class="form-select">
                    <option value="0">Cantos Retos</option>
                    <option value="0">Cantos Retos + Laminação FOSCO Bopp F/V (acima de 200g)</option>
                    <option value="0">Cantos Retos + Laminação BRILHO Bopp F/V (Acima de 200g)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="file_format" class="form-label">Formato do Arquivo</label>
                <select id="file_format" name="options[file_format]" class="form-select">
                    <option value="0">Arquivo PDF (fechado para impressão) (Grátis)</option>
                    <option value="80">Arquvo CDR / INDD / AI / JPG / PNG (aberto) (R$ 80,00)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="file_check" class="form-label">Verificação do Arquivo</label>
                <select id="file_check" name="options[file_check]" class="form-select">
                    <option value="0">Digital On-Line (Grátis)</option>
                    <option value="150">Prova de Cor Impressa - SOMENTE para São Paulo (R$ 150,00)</option>
                </select>
            </div>
        </div>

        <div class="mt-4 d-flex align-items-center gap-3">
            <h3 class="m-0">Preço: R$ <span id="final-price">--</span></h3>
            <button type="submit" class="btn btn-primary">Adicionar ao Carrinho</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const prices = @json($prices);

    const $q = document.getElementById('quantity');
    const $s = document.getElementById('size');
    const $p = document.getElementById('paper');
    const $c = document.getElementById('color');
    const $price = document.getElementById('final-price');
    const $details = document.getElementById('final-details');
    const $finish = document.getElementById('finishing');
    const $fmt = document.getElementById('file_format');
    const $chk = document.getElementById('file_check');

    function fill(select, items) {
        select.innerHTML = '';
        items.forEach(txt => {
            const opt = document.createElement('option');
            opt.value = txt;
            opt.textContent = txt;
            select.appendChild(opt);
        });
        select.disabled = items.length === 0;
    }
    function updateSizes(){ fill($s, Object.keys(prices[$q.value]||{})); updatePapers(); }
    function updatePapers(){ const map=(prices[$q.value]||{})[$s.value]||{}; fill($p, Object.keys(map)); updateColors(); }
    function updateColors(){ const map=((prices[$q.value]||{})[$s.value]||{})[$p.value]||{}; fill($c, Object.keys(map)); updatePrice(); }
    function parseNum(x){ return Number(String(x).replace(/[^0-9.\-]/g,'')||0); }
    function updatePrice(){
        const base = (((prices[$q.value]||{})[$s.value]||{})[$p.value]||{})[$c.value];
        const extras = parseNum($finish.value)+parseNum($fmt.value)+parseNum($chk.value);
        $price.textContent = (typeof base==='number'? (base+extras).toFixed(2).replace('.', ',') : '--');
    }
    [$q,$s,$p,$c,$finish,$fmt,$chk].forEach(el=>el.addEventListener('change',()=>{
        if(el===$q) updateSizes(); else if(el===$s) updatePapers(); else if(el===$p) updateColors(); else updatePrice();
    }));
    document.getElementById('flyer-form').addEventListener('submit', function(){
        const det={
            quantity: $q.options[$q.selectedIndex]?.text || $q.value,
            size: $s.options[$s.selectedIndex]?.text || $s.value,
            paper: $p.options[$p.selectedIndex]?.text || $p.value,
            color: $c.options[$c.selectedIndex]?.text || $c.value,
            finishing: $finish.options[$finish.selectedIndex]?.text || '',
            file_format: $fmt.options[$fmt.selectedIndex]?.text || '',
            file_check: $chk.options[$chk.selectedIndex]?.text || ''
        }; $details.value = JSON.stringify(det);
    });
    // inicial
    fill($q, Object.keys(prices));
    updateSizes();
});
</script>
@endsection

