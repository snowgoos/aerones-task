@extends('layouts.app')

@section('content')
    <h1>Add new file</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('files.store') }}" autocomplete="off">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">
                Name
            </label>
            <input id="name"
                   name="name"
                   type="text"
                   class="form-control"
                   placeholder="Enter the name">
        </div>
        <div class="mb-3">
            <label for="url" class="form-label">URL</label>
            <input id="url"
                   name="url"
                   type="url"
                   class="form-control"
                   placeholder="Enter the file URL">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <a href="{{ route('files.index') }}" class="btn btn-secondary mt-3">Back to list</a>
@endsection
