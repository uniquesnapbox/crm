@extends('layouts.public')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 b-shadow-4">
                <div class="card-body p-4">
                    <h2 class="mb-4 text-dark font-weight-bold">{{ $customPage->page_title }}</h2>
                    <div class="text-dark-grey">
                        {!! nl2br(e($customPage->content)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
