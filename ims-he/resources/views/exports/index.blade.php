@extends('layouts.app')

@section('title', 'Export Center')

@section('content')
<div class="report-heading">
    <h2>Export Center</h2>
    <div>
        <form method="POST" action="{{ route('exports.all') }}" class="inline">
            @csrf
            <button type="submit">Download everything (ZIP)</button>
        </form>
        @if(auth()->user()->hasRole(\App\Models\User::ADMINISTRATOR))
            <a class="btn" href="{{ route('exports.backup') }}">Database backup</a>
        @endif
    </div>
</div>

<div class="card">
    <table>
        <thead><tr><th>Dataset</th><th>Filters</th><th></th></tr></thead>
        <tbody>
        @foreach($datasets as $dataset)
            <tr>
                <td>{{ $dataset['label'] }}</td>
                <td>
                    <form method="GET" action="{{ route('exports.download', $dataset['key']) }}" class="report-filter">
                        @if(in_array('dates', $dataset['filters'], true))
                            <x-field label="Start date" name="start_date" type="date" :value="request('start_date')" />
                            <x-field label="End date" name="end_date" type="date" :value="request('end_date')" />
                        @endif
                        @if(in_array('category', $dataset['filters'], true))
                            <x-select label="Category" name="category_id" :required="false">
                                <option value="">All categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}" @selected((string) request('category_id') === (string) $category->category_id)>{{ $category->category_name }}</option>
                                @endforeach
                            </x-select>
                        @endif
                        @if(in_array('status', $dataset['filters'], true))
                            <x-field label="Status" name="status" :value="request('status')" :required="false" />
                        @endif
                        <button type="submit">Download CSV</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
