@extends('layouts.master')
@section('title') Comunicados @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">
                            <i class="ri-chat-3-line me-1"></i> Comunicados
                        </h5>
                        @can('crear-comunicados')
                        <a href="{{ route('feed.create') }}" class="btn btn-primary btn-sm">
                            <i class="ri-add-line me-1"></i> Nuevo Comunicado
                        </a>
                        @endcan
                    </div>
                </div>

                <!-- Filtros rápidos -->
                <div class="card-body border-top border-bottom">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('feed.index') }}" class="btn btn-sm {{ !request('type') && !request('priority') ? 'btn-primary' : 'btn-ghost-primary' }}">Todos</a>
                        <a href="{{ route('feed.index', ['type' => 'comunicado']) }}" class="btn btn-sm {{ request('type') == 'comunicado' ? 'btn-primary' : 'btn-ghost-primary' }}">📢 Comunicados</a>
                        <a href="{{ route('feed.index', ['type' => 'anuncio']) }}" class="btn btn-sm {{ request('type') == 'anuncio' ? 'btn-info' : 'btn-ghost-info' }}">📌 Anuncios</a>
                        <a href="{{ route('feed.index', ['type' => 'tarea']) }}" class="btn btn-sm {{ request('type') == 'tarea' ? 'btn-warning' : 'btn-ghost-warning' }}">✅ Tareas</a>
                        <span class="border-start mx-2"></span>
                        <a href="{{ route('feed.index', ['priority' => 'urgente']) }}" class="btn btn-sm {{ request('priority') == 'urgente' ? 'btn-danger' : 'btn-ghost-danger' }}">🔴 Urgente</a>
                        <a href="{{ route('feed.index', ['priority' => 'importante']) }}" class="btn btn-sm {{ request('priority') == 'importante' ? 'btn-warning' : 'btn-ghost-warning' }}">🟡 Importante</a>
                    </div>
                </div>

                <div class="card-body">
                    @forelse($posts as $post)
                    <div class="d-flex align-items-start mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <!-- Avatar -->
                        <div class="avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                {{ strtoupper(substr($post->author->name ?? 'S', 0, 1)) }}
                            </span>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    @if($post->pinned)
                                        <span class="badge bg-info-subtle text-info me-1"><i class="ri-pushpin-line"></i> Fijado</span>
                                    @endif
                                    @php
                                        $priorityBadges = [
                                            'urgente' => 'bg-danger-subtle text-danger',
                                            'importante' => 'bg-warning-subtle text-warning',
                                            'normal' => '',
                                        ];
                                        $typeBadges = [
                                            'comunicado' => 'bg-primary-subtle text-primary',
                                            'anuncio' => 'bg-info-subtle text-info',
                                            'tarea' => 'bg-success-subtle text-success',
                                        ];
                                    @endphp
                                    @if($post->priority !== 'normal')
                                        <span class="badge {{ $priorityBadges[$post->priority] }}">{{ ucfirst($post->priority) }}</span>
                                    @endif
                                    <span class="badge {{ $typeBadges[$post->type] ?? '' }}">{{ ucfirst($post->type) }}</span>

                                    <h6 class="mb-1 mt-2">
                                        <a href="{{ route('feed.show', $post) }}" class="text-reset">{{ $post->title }}</a>
                                    </h6>
                                    <p class="text-muted mb-2">{{ $post->summary ?: Str::limit(strip_tags(preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $post->body)), 200) }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 text-muted fs-12">
                                <span><i class="ri-user-line me-1"></i>{{ $post->author->name ?? 'Sistema' }}</span>
                                <span><i class="ri-time-line me-1"></i>{{ $post->published_at->diffForHumans() }}</span>
                                @if($post->store)
                                    <span><i class="ri-store-2-line me-1"></i>{{ $post->store->name }}</span>
                                @endif
                                <span>
                                    <i class="ri-eye-line me-1"></i>{{ $post->reads->count() }} lecturas
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $post->readPercentage() }}%</span>
                                </span>
                                @if($post->files->count() > 0)
                                    <span title="{{ $post->files->count() }} archivo(s) adjunto(s)">
                                        <i class="ri-attachment-2 me-1 text-info"></i>{{ $post->files->count() }}
                                    </span>
                                @endif
                            </div>

                            <!-- Reacciones rápidas -->
                            <div class="mt-2 d-flex gap-1">
                                @php
                                    $emojis = ['👍', '❤️', '👏', '😊', '😮'];
                                    $grouped = $post->reactions->groupBy('emoji');
                                @endphp
                                @foreach($emojis as $emoji)
                                    <form action="{{ route('feed.react', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="emoji" value="{{ $emoji }}">
                                        <button type="submit" class="btn btn-sm {{ isset($grouped[$emoji]) && $grouped[$emoji]->contains('user_id', auth()->id()) ? 'btn-soft-primary' : 'btn-ghost-secondary' }} py-0 px-1"
                                                title="{{ isset($grouped[$emoji]) ? $grouped[$emoji]->pluck('user.name')->implode(', ') : '' }}">
                                            {{ $emoji }} @if(isset($grouped[$emoji]))<span class="fs-11">{{ $grouped[$emoji]->count() }}</span>@endif
                                        </button>
                                    </form>
                                @endforeach

                                @can('ver-lecturas')
                                <a href="{{ route('feed.readers', $post) }}" class="btn btn-sm btn-ghost-info py-0 px-1 ms-2" title="Ver quién leyó">
                                    <i class="ri-eye-2-line"></i>
                                </a>
                                @endcan
                            </div>
                        </div>

                        @if(auth()->id() === $post->user_id || auth()->user()->can('editar-comunicados'))
                        <div class="dropdown ms-2">
                            <button class="btn btn-ghost-secondary btn-sm" data-bs-toggle="dropdown">
                                <i class="ri-more-2-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('feed.edit', $post) }}"><i class="ri-pencil-line me-2"></i>Editar</a></li>
                                <li>
                                    <form id="del-post-{{ $post->id }}" action="{{ route('feed.destroy', $post) }}" method="POST" style="display:none;">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button class="dropdown-item text-danger"
                                        data-confirm
                                        data-form="del-post-{{ $post->id }}"
                                        data-title="Eliminar Comunicado"
                                        data-message="¿Estás seguro de eliminar el comunicado <strong>{{ $post->title }}</strong>? Esta acción no se puede deshacer."
                                        data-btn-label="Sí, eliminar">
                                        <i class="ri-delete-bin-line me-2"></i>Eliminar
                                    </button>
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="ri-chat-off-line fs-24 d-block mb-2"></i>
                        No hay comunicados publicados.
                    </div>
                    @endforelse

                    <div class="d-flex justify-content-end mt-3">
                        {{ $posts->appends(request()->all())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
