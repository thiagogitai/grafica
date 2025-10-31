@extends('layouts.app')

@section('title', 'Upload de Arquivo - ' . $product->name)

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Upload de Arte para: {{ $product->name }}</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('logo.png') }}" alt="{{ $product->name }}" class="img-fluid rounded">
                        </div>
                        <div class="col-md-8">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <p class="card-text">{{ $product->description }}</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Dimensões: {{ $product->width }}cm x {{ $product->height }}cm</li>
                                <li class="list-group-item">Tipo: {{ $product->is_duplex ? 'Frente e Verso' : 'Apenas Frente' }}</li>
                            </ul>
                        </div>
                    </div>

                    <hr>

                    <form action="{{ route('upload.store', ['product' => $product->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @foreach($options as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <div class="mb-3">
                            <label for="file_front" class="form-label"><strong>Arquivo (Frente)</strong> <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="file_front" name="file_front" accept=".pdf,.jpg,.png,.cdr,.ai,.psd" required>
                            <small class="form-text text-muted">Formatos aceitos: PDF, JPG, PNG, CorelDraw, Illustrator, Photoshop.</small>
                        </div>

                        @if($product->is_duplex)
                            <div class="mb-3">
                                <label for="file_back" class="form-label"><strong>Arquivo (Verso)</strong></label>
                                <input class="form-control" type="file" id="file_back" name="file_back" accept=".pdf,.jpg,.png,.cdr,.ai,.psd">
                            </div>
                        @endif

                        <div class="alert alert-warning mt-4">
                            <strong>Atenção:</strong> Certifique-se de que todos os textos e elementos importantes da sua arte estejam a pelo menos 5mm de distância das bordas para evitar cortes na impressão.
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="" id="margin_confirmation" required>
                            <label class="form-check-label" for="margin_confirmation">
                                Confirmo que verifiquei as margens de segurança da minha arte.
                            </label>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Enviar Arquivos e Adicionar ao Carrinho</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
