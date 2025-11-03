@extends('layouts.app')

@section('title', 'Minha Conta - Grafica Todah Serviços Fotográficos')

@section('body-class', 'customer-dashboard')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Minha Conta</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Meu Perfil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('customer.profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="company_name">Empresa</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $profile->company_name) }}">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="tax_id">CNPJ/CPF</label>
                            <input type="text" class="form-control @error('tax_id') is-invalid @enderror" id="tax_id" name="tax_id" value="{{ old('tax_id', $profile->tax_id) }}">
                            @error('tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="phone">Telefone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $profile->phone ?? $user->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="default_address">Endereço padrão</label>
                            <textarea class="form-control @error('default_address') is-invalid @enderror" id="default_address" name="default_address" rows="2">{{ old('default_address', $profile->default_address) }}</textarea>
                            @error('default_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salvar perfil</button>
                    </form>
                </div>
            </div>

            @if($pendingFeedback->isNotEmpty())
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Avalie seus pedidos</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($pendingFeedback as $order)
                            <a href="{{ route('customer.feedback.create', $order) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Pedido #{{ $order->id }}
                                <span class="badge bg-warning text-dark">Avaliar</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Meus Pedidos</h5>
                    <span class="text-muted small">Últimos {{ $orders->perPage() }} pedidos</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst(__($order->status)) }}
                                    </span>
                                </td>
                                <td>R$ {{ number_format($order->total, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('customer.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                        Detalhes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhum pedido encontrado.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
