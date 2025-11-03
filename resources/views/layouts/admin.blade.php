
@extends('layouts.app')

@section('title', 'Admin - Grafica Todah Serviços Fotográficos')
@section('body-class', 'admin-page bg-light')

@push('styles')
<style>
    .admin-shell {
        min-height: calc(100vh - 140px);
        position: relative;
    }
    .admin-shell__hero {
        background: linear-gradient(135deg, rgba(212, 160, 23, 0.95), rgba(244, 214, 135, 0.9));
        border-radius: 20px;
        color: #fff;
        padding: 32px 32px 20px;
        box-shadow: 0 20px 45px rgba(141, 102, 11, 0.25);
    }
    .admin-shell__hero h1 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
    }
    .admin-shell__sidebar {
        position: sticky;
        top: 108px;
    }
    .admin-shell .list-group-item {
        border: none;
        border-radius: 12px;
        margin-bottom: 8px;
        font-weight: 600;
        color: #5f4b17;
        transition: all 0.2s ease;
    }
    .admin-shell .list-group-item i {
        width: 20px;
    }
    .admin-shell .list-group-item.active,
    .admin-shell .list-group-item:hover {
        background-color: rgba(212, 160, 23, 0.15);
        color: #2f2508;
        transform: translateX(4px);
    }
    .admin-shell .card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 12px 32px rgba(112, 85, 19, 0.08);
    }
    .admin-shell__content {
        margin-top: -10px;
    }
    .admin-shell__breadcrumbs {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.875rem;
        opacity: 0.85;
    }
    .admin-shell__breadcrumbs span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .admin-shell__quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .admin-shell__quick-actions .btn {
        border-radius: 999px;
        padding: 10px 18px;
        font-weight: 600;
        background-color: #363636;
        border: none;
        color: #fff;
    }
    .admin-shell__quick-actions .btn:hover,
    .admin-shell__quick-actions .btn:focus {
        background-color: #2a2a2a;
        color: #fff;
    }
    .admin-tabs .nav-link {
        border-radius: 14px;
        border: 1px solid transparent;
        background-color: rgba(54, 54, 54, 0.08) !important;
        color: #6a531c !important;
        font-weight: 600;
        padding: 12px 18px;
        transition: all 0.2s ease;
    }
    .admin-tabs .nav-link:hover,
    .admin-tabs .nav-link:focus {
        background-color: #363636 !important;
        color: #d4a017 !important;
        box-shadow: 0 6px 18px rgba(40, 40, 40, 0.25);
    }
    .admin-tabs .nav-link.active {
        background-color: #363636 !important;
        color: #d4a017 !important;
        border-color: rgba(212, 160, 23, 0.35);
        box-shadow: 0 10px 24px rgba(54, 54, 54, 0.3);
    }
    @media (max-width: 991.98px) {
        .admin-shell__content {
            margin-top: 0;
        }
        .admin-shell__sidebar {
            position: static;
        }
    }
</style>
@endpush

@section('content')
    <div class="container py-4 admin-shell">
        <div class="admin-shell__hero mb-4">
            <div class="d-lg-flex justify-content-between align-items-center gap-3">
                <div>
                    <div class="admin-shell__breadcrumbs mb-2">
                        @php
                            $breadcrumb = trim($__env->yieldContent('admin-breadcrumb'));
                            if ($breadcrumb === '') {
                                $breadcrumb = $__env->yieldContent('page-breadcrumb');
                            }
                        @endphp
                        <span>
                            <i class="fas fa-home"></i> Admin
                        </span>
                        {!! $breadcrumb !!}
                    </div>
                    <h1 class="mb-2">@yield('admin-title', 'Painel Administrativo')</h1>
                    <p class="mb-0 text-white-50">
                        @yield('admin-subtitle', 'Gerencie pedidos, produtos e configurações da sua gráfica.')
                    </p>
                </div>
                <div class="admin-shell__quick-actions">
                    @php
                        $actions = trim($__env->yieldContent('admin-actions'));
                        if ($actions === '') {
                            $actions = $__env->yieldContent('page-actions');
                        }
                    @endphp
                    {!! $actions !!}
                </div>
            </div>
        </div>

        <div class="row g-4">
            <aside class="col-12 col-lg-3 admin-shell__sidebar">
                <div class="list-group bg-white shadow-sm p-3 rounded-4">
                    <a href="{{ route('admin.dashboard') }}"
                       class="list-group-item list-group-item-action {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i> Visão geral
                    </a>
                    <a href="{{ route('admin.orders') }}"
                       class="list-group-item list-group-item-action {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-bag"></i> Pedidos
                    </a>
                    <a href="{{ route('admin.products') }}"
                       class="list-group-item list-group-item-action {{ request()->routeIs(['admin.products', 'admin.products.*']) ? 'active' : '' }}">
                        <i class="fas fa-box-open"></i> Produtos
                    </a>
                    <a href="{{ route('admin.pricing') }}"
                       class="list-group-item list-group-item-action {{ request()->routeIs('admin.pricing') ? 'active' : '' }}">
                        <i class="fas fa-percentage"></i> Faturamento
                    </a>
                    <a href="{{ route('admin.categories') }}"
                       class="list-group-item list-group-item-action {{ request()->routeIs(['admin.categories', 'admin.categories.*']) ? 'active' : '' }}">
                        <i class="fas fa-tags"></i> Categorias
                    </a>
                    <a href="{{ route('admin.settings') }}"
                       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i> Configurações
                    </a>
                </div>
            </aside>
            <div class="col-12 col-lg-9">
                <div class="admin-shell__content">
                    @php
                        $content = trim($__env->yieldContent('admin-content'));
                        if ($content === '') {
                            $content = $__env->yieldContent('page-content');
                        }
                    @endphp
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
@endsection
