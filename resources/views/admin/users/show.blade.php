@extends('layouts.master')
@section('title') @lang('translation.show_user') @endsection
@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">@lang('translation.show_user')</h4>
                    <div class="flex-shrink-0">
                        <a class="btn btn-primary btn-sm" href="{{ route('users.index') }}"> @lang('translation.back')</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.name'):</strong>
                                {{ $user->name ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.lastname'):</strong>
                                {{ $user->lastname ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.username'):</strong>
                                {{ $user->username ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.email'):</strong>
                                {{ $user->email ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.phone'):</strong>
                                {{ $user->phone ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.birthday'):</strong>
                                {{ $user->birthday ? $user->birthday->format('d/m/Y') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.rfc'):</strong>
                                {{ $user->rfc ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.status'):</strong>
                                @if($user->status)
                                    <span class="badge bg-success">@lang('translation.active')</span>
                                @else
                                    <span class="badge bg-danger">@lang('translation.inactive')</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>Límite de Crédito:</strong>
                                ${{ number_format($user->credit_limit, 2) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>Crédito Disponible:</strong>
                                ${{ number_format($user->available_credit, 2) }}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.address'):</strong>
                                {{ $user->address ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.email_verified'):</strong>
                                @if($user->email_verified_at)
                                    <span class="badge bg-success">@lang('translation.verified')</span>
                                    <small class="text-muted">{{ $user->email_verified_at->format('d/m/Y H:i') }}</small>
                                @else
                                    <span class="badge bg-warning">@lang('translation.not_verified')</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.phone_verified'):</strong>
                                @if($user->phone)
                                    @if($user->phone_verified_at)
                                        <span class="badge bg-success">@lang('translation.verified')</span>
                                        <small class="text-muted">{{ $user->phone_verified_at->format('d/m/Y H:i') }}</small>
                                    @else
                                        <span class="badge bg-warning">@lang('translation.not_verified')</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <strong>@lang('translation.roles'):</strong>
                                @if(!empty($user->getRoleNames()))
                                    @foreach($user->getRoleNames() as $v)
                                        <span class="badge bg-primary">{{ $v }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
