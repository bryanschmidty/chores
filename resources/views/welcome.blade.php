@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-3">Chore App MVP</h1>
                    <p class="text-body-secondary mb-4">
                        Track chores, submit completions, approve work, and keep points transparent for the whole household.
                    </p>
                    @auth
                        <a href="{{ route('app.home') }}" class="btn btn-primary">Open App</a>
                    @else
                        <a href="{{ route('auth.google.redirect') }}" class="btn btn-primary">Log In with Google</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
@endsection
