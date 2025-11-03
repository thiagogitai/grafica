@extends('layouts.app')

@section('title', 'Avaliar Pedido #' . $order->id)

@section('body-class', 'customer-feedback')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Avaliar pedido #{{ $order->id }}</h4>
                    <small class="text-muted">Conte-nos como foi sua experiência e ajude-nos a melhorar.</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('customer.feedback.store', $order) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nota</label>
                            <div class="d-flex gap-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="rating" id="rating_{{ $i }}" value="{{ $i }}" {{ old('rating', 5) == $i ? 'checked' : '' }}>
                                        <label class="form-check-label" for="rating_{{ $i }}">{{ $i }}</label>
                                    </div>
                                @endfor
                            </div>
                            @error('rating')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="headline">Título</label>
                            <input type="text" id="headline" name="headline" class="form-control @error('headline') is-invalid @enderror" value="{{ old('headline') }}" placeholder="Ex.: Ótimo atendimento">
                            @error('headline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="comment">Comentário</label>
                            <textarea id="comment" name="comment" rows="4" class="form-control @error('comment') is-invalid @enderror" placeholder="Compartilhe detalhes que ajudem outros clientes.">{{ old('comment') }}</textarea>
                            @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customer.orders.show', $order) }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Enviar avaliação</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
