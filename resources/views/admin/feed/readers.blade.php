@extends('layouts.master')
@section('title') Lecturas: {{ $post->title }} @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-eye-line me-1"></i> Lecturas
                            <span class="badge bg-primary ms-1">{{ $post->readPercentage() }}%</span>
                        </h5>
                        <a href="{{ route('feed.show', $post) }}" class="btn btn-ghost-secondary btn-sm">
                            <i class="ri-arrow-left-line"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-3">{{ $post->title }}</h6>

                    <!-- Usuarios que leyeron -->
                    <h6 class="text-success mb-2">
                        <i class="ri-check-line me-1"></i> Leyeron ({{ $post->reads->count() }})
                    </h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Fecha de lectura</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($post->reads->sortByDesc('read_at') as $read)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title rounded-circle bg-success-subtle text-success">
                                                    {{ strtoupper(substr($read->user->name ?? '?', 0, 1)) }}
                                                </span>
                                            </div>
                                            {{ $read->user->name ?? '?' }} {{ $read->user->lastname ?? '' }}
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $read->read_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Usuarios que NO leyeron -->
                    @if($unreadUsers->count() > 0)
                    <h6 class="text-danger mb-2">
                        <i class="ri-close-line me-1"></i> No han leído ({{ $unreadUsers->count() }})
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($unreadUsers as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title rounded-circle bg-danger-subtle text-danger">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            {{ $user->name }} {{ $user->lastname ?? '' }}
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
