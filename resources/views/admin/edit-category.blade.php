
@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1>Editar Categoria</h1>
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $category->name }}" required>
            </div>
            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea name="description" id="description" class="form-control">{{ $category->description }}</textarea>
            </div>
            <div class="form-group">
                <label for="image">Imagem</label>
                <input type="file" name="image" id="image" class="form-control-file">
                @if ($category->image)
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" width="100">
                @endif
            </div>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
@endsection
