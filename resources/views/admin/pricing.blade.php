@extends('layouts.admin')

@section('title', 'Faturamento e Markup - Admin')

@section('page-content')
<div class="container py-4">
    <h1 class="h3 mb-4">Faturamento e Markup</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.pricing.update') }}" method="POST">
        @csrf
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Markup Global</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label for="global_markup" class="form-label">Percentual aplicado a toda loja (%)</label>
                        <input type="number" step="0.01" min="0" max="500" id="global_markup" name="global_markup" class="form-control @error('global_markup') is-invalid @enderror" value="{{ old('global_markup', $globalMarkup) }}">
                        @error('global_markup')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-0">Esse valor será combinado com os markups específicos de cada produto.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Markup por produto</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Template</th>
                            <th class="text-end" style="width: 160px;">Markup (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($product->description, 80) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $product->effectiveTemplateLabel() }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="input-group">
                                        <input type="number" min="0" max="500" step="0.01" class="form-control text-end" name="product_markups[{{ $product->id }}]" value="{{ old('product_markups.' . $product->id, $product->markup_percentage ?? 0) }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </div>
    </form>
</div>
@endsection
