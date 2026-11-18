@extends('layouts.app')

@section('title', 'Export Center')

@section('heading', 'Export Center')
@section('page-actions')
    <form method="POST" action="{{ route('exports.all') }}" class="inline">
        @csrf
        <button type="submit">Download everything (ZIP)</button>
    </form>
@endsection

@section('content')
<div class="card">
    <table class="export-table">
        <thead><tr><th>Dataset</th><th>Filters</th><th>Download</th></tr></thead>
        <tbody>
        @foreach($datasets as $dataset)
            <tr>
                <td>{{ $dataset['label'] }}</td>
                <td>
                    {{-- The Download button in the next column submits this form (form="..."). --}}
                    <form id="export-{{ $dataset['key'] }}" method="GET" action="{{ route('exports.download', $dataset['key']) }}" class="report-filter">
                        @if(in_array('dates', $dataset['filters'], true))
                            <x-field label="From" name="start_date" type="date" :value="request('start_date')" :required="false" />
                            <x-field label="To" name="end_date" type="date" :value="request('end_date')" :required="false" />
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
                            <x-select label="Status" name="status" :required="false">
                                <option value="">All statuses</option>
                                @foreach($statusOptions[$dataset['key']] ?? [] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                @endforeach
                            </x-select>
                        @endif
                        @if($dataset['filters'] === [])
                            <span class="text-muted">No filters</span>
                        @endif
                    </form>
                </td>
                <td><button type="submit" form="export-{{ $dataset['key'] }}">Download CSV</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
