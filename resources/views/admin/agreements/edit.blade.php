<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="fw-bold">{{ $pageTitle }}</h5>
                            <a href="{{ route('admin.agreements.index') }}" class="btn btn-secondary btn-sm">
                                <i class="ri-arrow-go-back-line"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    {{ html()->modelForm($agreement, 'POST', route('admin.agreements.update', $agreement->id))->attribute('data-toggle', 'validator')->open() }}
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {{ html()->label(__('messages.arabic_content') . ' <span class="text-danger">*</span>', 'content_ar')->class('form-control-label') }}
                                {{ html()->textarea('content_ar', $agreement->content_ar)
                                    ->class('form-control tinymce-agreement-ar')
                                    ->rows(10)
                                    ->placeholder(__('messages.arabic_content')) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                {{ html()->label(__('messages.english_content') . ' <span class="text-danger">*</span>', 'content_en')->class('form-control-label') }}
                                {{ html()->textarea('content_en', $agreement->content_en)
                                    ->class('form-control tinymce-agreement-en')
                                    ->rows(10)
                                    ->placeholder(__('messages.english_content')) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <p><strong>{{ __('messages.type') }}:</strong> {{ $agreement->type }}</p>
                        <p><strong>{{ __('messages.version') }}:</strong> {{ $agreement->version }}</p>
                        <p><strong>{{ __('messages.status') }}:</strong>
                            @if($agreement->is_active)
                                <span class="badge bg-success">{{ __('messages.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                            @endif
                        </p>
                    </div>

                    @if(auth()->user()->hasRole(['admin', 'demo_admin']))
                        {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-end') }}
                    @endif

                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <script>
            (function($) {
                $(document).ready(function(){
                    tinymceEditor('.tinymce-agreement-ar', 'ar', function (ed) {}, 450);
                    tinymceEditor('.tinymce-agreement-en', 'en', function (ed) {}, 450);
                });
            })(jQuery);
        </script>
    @endsection
</x-master-layout>
