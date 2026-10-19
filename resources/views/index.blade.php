@extends(config('activityloggerui.layout') ?: 'activityloggerui::layout')

@section('content')
    @include('activityloggerui::history')
@endsection
