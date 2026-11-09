<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
            <h4 class="mb-sm-0 font-size-18">{{ $title ?? '' }}</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    @if(!empty($crumbs))
                        @foreach($crumbs as $crumb)
                            <li class="breadcrumb-item">
                                @if(isset($crumb['route']))
                                    <a href="{{ route($crumb['route'], $crumb['params'] ?? []) }}">{{ $crumb['label'] }}</a>
                                @elseif(isset($crumb['url']))
                                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                                @else
                                    <a href="javascript: void(0);">{{ $crumb['label'] }}</a>
                                @endif
                            </li>
                        @endforeach
                    @elseif(isset($li_1))
                        <li class="breadcrumb-item"><a href="javascript: void(0);">{{ $li_1 }}</a></li>
                    @endif
                    @if(!empty($title))
                        <li class="breadcrumb-item active">{{ $title }}</li>
                    @endif
                </ol>
            </div>

        </div>
    </div>
</div>
<!-- end page title -->
