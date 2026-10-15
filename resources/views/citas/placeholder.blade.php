@extends('layouts.main')

@section('title', $title . ' - xNova')
@section('page-title', $title)

@section('content')
<section class="card">
    <div class="card-body">
        <h2 class="card-title">{{ $title }}</h2>
        <p class="text-muted mt-4">{{ $description }}</p>
    </div>
</section>
@endsection
