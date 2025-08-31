@extends('layouts.app')

@section('content')
    <h1 class="text-center">Files</h1>

    <a href="{{ route('files.create') }}"
       title=""
       class="btn btn-secondary my-3">
        Add new file
    </a>

    <form method="post" action="{{ route('files.force-download') }}">
        @csrf

        <table class="table">
            <thead>
            <tr>
                <th>Select</th>
                <th>Name</th>
                <th>Status</th>
                <th>Progress</th>
            </tr>
            </thead>
            <tbody>
            @foreach($files as $file)
                <tr>
                    <td>
                        <input type="checkbox" name="force_download_ids[]" value="{{ $file->id }}">
                    </td>
                    <td>{{ $file->name }}</td>
                    <td class="status">{{ $file->status_name }}</td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar"
                                 role="progressbar"
                                 style="width: {{ $file->progress }}%;"
                                 aria-valuenow="{{ $file->progress }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                {{ $file->progress }}%
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-secondary">Download selected</button>
    </form>

    <a href="{{ route('files.create') }}"
       title=""
       class="btn btn-secondary my-3">
        Add new file
    </a>
@endsection
