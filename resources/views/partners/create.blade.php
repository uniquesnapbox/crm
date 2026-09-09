@extends('layouts.app')
@section('content')
<div class="content-wrapper">
    <div class="bg-white rounded shadow-sm p-4">
        <h4 class="mb-4">Add Partner</h4>
        <form method="POST" action="{{ route('partners.store') }}">
            @include('partners._form')
        </form>
    </div>
</div>
@endsection
