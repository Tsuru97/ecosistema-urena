@extends('layouts.master')
@section('title') @lang('translation.users') @endsection
@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">@lang('translation.users')</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('users.create') }}" class="btn btn-success btn-label waves-effect waves-light btn-sm">
                            <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> @lang('translation.add_new')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm" value="{{ request('search') }}" placeholder="@lang('translation.search')...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm">@lang('translation.search')</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        <a href="{{ route('users.index', array_merge(request()->except('page'), ['sort_field' => 'id', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            No
                                            @if(request('sort_field') == 'id')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('users.index', array_merge(request()->except('page'), ['sort_field' => 'name', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            @lang('translation.name')
                                            @if(request('sort_field') == 'name')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('users.index', array_merge(request()->except('page'), ['sort_field' => 'email', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            @lang('translation.email')
                                            @if(request('sort_field') == 'email')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">Sucursales</th>
                                    <th scope="col">@lang('translation.roles')</th>
                                    <th scope="col">@lang('translation.status')</th>
                                    <th scope="col">@lang('translation.verification')</th>
                                    <th scope="col" style="width: 150px;">@lang('translation.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $key => $user)
                                    <tr>
                                        <td>{{ $users->firstItem() + $key }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->stores->count() > 0)
                                                @foreach($user->stores as $store)
                                                    <span class="badge bg-info-subtle text-info mb-1">{{ $store->name }}
                                                        @if($store->pivot && $store->pivot->is_default)
                                                            <i class="ri-star-fill text-warning ms-1" title="Sucursal Principal"></i>
                                                        @endif
                                                    </span><br>
                                                @endforeach
                                            @else
                                                <span class="text-muted small">Sin asignar</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($user->getRoleNames()))
                                                @foreach($user->getRoleNames() as $v)
                                                    <span class="badge bg-primary">{{ $v }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->status)
                                                <span class="badge bg-success">@lang('translation.active')</span>
                                            @else
                                                <span class="badge bg-danger">@lang('translation.inactive')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->email_verified_at)
                                                <span title="@lang('translation.email_verified')" class="text-success">
                                                    <i class="ri-mail-check-line fs-17"></i>
                                                </span>
                                            @else
                                                <form action="{{ route('users.resend.verify.email', $user->id) }}" method="POST" style="display:inline" title="@lang('translation.resend_email_verification')">
                                                    @csrf
                                                    <button type="submit" class="border-0 bg-transparent p-0 text-muted" style="cursor:pointer">
                                                        <i class="ri-mail-send-line fs-17"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($user->phone)
                                                @if($user->phone_verified_at)
                                                    <span title="@lang('translation.phone_verified')" class="ms-1 text-success">
                                                        <i class="ri-smartphone-line fs-17"></i>
                                                    </span>
                                                @else
                                                    <form action="{{ route('users.resend.verify.whatsapp', $user->id) }}" method="POST" style="display:inline" title="@lang('translation.resend_whatsapp_verification')">
                                                        @csrf
                                                        <button type="submit" class="border-0 bg-transparent p-0 text-muted ms-1" style="cursor:pointer">
                                                            <i class="ri-whatsapp-line fs-17"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <div class="hstack gap-3 flex-wrap">
                                                <a href="{{ route('users.edit',$user->id) }}" class="link-success fs-15"><i class="ri-edit-2-line"></i></a>

                                                <form id="del-user-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:none;">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button type="button" class="link-danger fs-15 border-0 bg-transparent p-0"
                                                    data-confirm
                                                    data-form="del-user-{{ $user->id }}"
                                                    data-title="Eliminar Usuario"
                                                    data-message="¿Estás seguro de eliminar a <strong>{{ $user->name }}</strong>? Esta acción no se puede deshacer."
                                                    data-btn-label="Sí, eliminar">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $users->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
