@extends('layouts.master')
@section('title') {{ $post->title }} @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
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
                            @if($post->pinned)
                                <span class="badge bg-info-subtle text-info me-1"><i class="ri-pushpin-line"></i> Fijado</span>
                            @endif
                            @if($post->priority !== 'normal')
                                <span class="badge {{ $priorityBadges[$post->priority] }}">{{ ucfirst($post->priority) }}</span>
                            @endif
                            <span class="badge {{ $typeBadges[$post->type] ?? '' }}">{{ ucfirst($post->type) }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            @can('editar-comunicados')
                            <form id="resend-notification-{{ $post->id }}" action="{{ route('feed.resendNotification', $post) }}" method="POST" style="display:none;">
                                @csrf
                            </form>
                            <button type="button" class="btn btn-soft-warning btn-sm"
                                data-confirm
                                data-form="resend-notification-{{ $post->id }}"
                                data-title="Relanzar Notificación"
                                data-message="¿Relanzar la notificación del comunicado <strong>{{ $post->title }}</strong> a todos los usuarios? Esto enviará una nueva notificación."
                                data-btn-label="Sí, relanzar"
                                data-btn-class="btn-warning"
                                title="Vuelve a enviar la notificación a todos los destinatarios">
                                <i class="bx bx-bell-plus me-1"></i> Relanzar Notificación
                            </button>
                            @endcan
                            <a href="{{ route('feed.index') }}" class="btn btn-ghost-secondary btn-sm">
                                <i class="ri-arrow-left-line"></i> Volver
                            </a>
                        </div>
                    </div>

                    <h4 class="mb-3">{{ $post->title }}</h4>

                    <div class="d-flex align-items-center gap-3 text-muted fs-13 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                    {{ strtoupper(substr($post->author->name ?? 'S', 0, 1)) }}
                                </span>
                            </div>
                            {{ $post->author->name ?? 'Sistema' }}
                        </div>
                        <span><i class="ri-time-line me-1"></i>{{ $post->published_at->format('d/m/Y H:i') }}</span>
                        @if($post->store)
                            <span><i class="ri-store-2-line me-1"></i>{{ $post->store->name }}</span>
                        @endif
                    </div>

                    <div class="border rounded p-3 mb-4 bg-light">
                        {!! $post->body !!}
                    </div>

                    <!-- Archivos Adjuntos -->
                    @if($post->files->count() > 0)
                        <h6 class="mb-3"><i class="ri-attachment-2 me-1"></i> Archivos Adjuntos ({{ $post->files->count() }})</h6>
                        <div class="row g-3 mb-4">
                            @foreach($post->files as $file)
                                <div class="col-xxl-3 col-lg-4 col-sm-6">
                                    <div class="border rounded border-dashed p-2">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-sm">
                                                    <div class="avatar-title bg-light text-secondary rounded fs-24 material-shadow">
                                                        @if(str_contains($file->mime_type, 'image'))
                                                            <i class="ri-image-2-fill text-primary"></i>
                                                        @elseif(str_contains($file->mime_type, 'pdf'))
                                                            <i class="ri-file-pdf-fill text-danger"></i>
                                                        @else
                                                            <i class="ri-file-zip-fill text-info"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <h5 class="fs-13 mb-1"><a href="{{ asset('storage/' . $file->path) }}" target="_blank" class="text-body text-truncate d-block" title="{{ $file->name }}">{{ $file->name }}</a></h5>
                                                <div>{{ number_format($file->size / 1024, 2) }} KB</div>
                                            </div>
                                            <div class="flex-shrink-0 ms-2">
                                                <div class="d-flex gap-1">
                                                    <a href="{{ asset('storage/' . $file->path) }}" download class="btn btn-icon text-muted btn-sm fs-18"><i class="ri-download-2-line"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Reacciones -->
                    <div class="d-flex gap-2 mb-4">
                        @php
                            $emojis = ['👍', '❤️', '👏', '😊', '😮'];
                            $grouped = $post->reactions->groupBy('emoji');
                        @endphp
                        @foreach($emojis as $emoji)
                            <form action="{{ route('feed.react', $post) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="emoji" value="{{ $emoji }}">
                                <button type="submit" class="btn btn-sm {{ isset($grouped[$emoji]) && $grouped[$emoji]->contains('user_id', auth()->id()) ? 'btn-soft-primary' : 'btn-ghost-secondary' }}">
                                    {{ $emoji }}
                                    @if(isset($grouped[$emoji]))
                                        <span class="ms-1">{{ $grouped[$emoji]->count() }}</span>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <!-- Comentarios -->
                    <div class="border-top pt-3 mb-4">
                        <h6 class="mb-3"><i class="ri-chat-1-line me-1"></i> Comentarios ({{ $post->comments->count() }})</h6>
                        
                        <div class="mb-4">
                            <form action="{{ route('feed.comment', $post) }}" method="POST">
                                @csrf
                                <div class="d-flex align-items-start gap-3">
                                    <div class="avatar-xs flex-shrink-0">
                                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <textarea name="body" class="form-control mb-2 form-control-sm" rows="2" placeholder="Escribe un comentario..." required></textarea>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-sm btn-primary">Comentar</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            @foreach($post->comments as $comment)
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar-xs flex-shrink-0">
                                    <span class="avatar-title rounded-circle bg-light-subtle text-secondary material-shadow">
                                        {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div class="flex-grow-1 bg-light p-2 rounded material-shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fs-13 mb-0">{{ $comment->user->name ?? 'Usuario Eliminado' }}</h6>
                                        <span class="text-muted fs-11">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mb-0 fs-13 text-body">{{ $comment->body }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Lecturas -->
                    @can('ver-lecturas')
                    <div class="border-top pt-3">
                        <h6 class="mb-3">
                            <i class="ri-eye-line me-1"></i> Lecturas
                            <span class="badge bg-primary">{{ $post->reads->count() }}</span>
                            <span class="badge bg-secondary ms-1">{{ $post->readPercentage() }}%</span>
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($post->reads->sortByDesc('read_at') as $read)
                            <span class="badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2">
                                <span class="avatar-title rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 18px; height: 18px; font-size: 10px;">
                                    {{ strtoupper(substr($read->user->name ?? '?', 0, 1)) }}
                                </span>
                                {{ $read->user->name ?? '?' }}
                                <span class="text-muted fs-11">{{ $read->read_at->format('d/m H:i') }}</span>
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
