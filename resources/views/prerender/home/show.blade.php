@extends('prerender.base')

<?php /** @var Common\Core\Prerender\MetaTags $meta */ ?>

@section('body')
    <h1>{{ $meta->getTitle() }}</h1>
    <p>{{ $meta->getDescription() }}</p>
@endsection
