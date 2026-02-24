@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Leaderboard</h1>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th class="text-end">Points</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaderboard as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->email }}</td>
                            <td class="text-end fw-semibold">{{ (int) $row->total_points }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-3">No scores yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
