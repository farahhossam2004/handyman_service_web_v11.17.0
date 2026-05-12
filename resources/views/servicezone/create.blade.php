<x-master-layout>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{__('messages.service_zone_configuration')}}</h5>
                            <a href="{{route('servicezone.index')}}" class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{__('messages.back')}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="container">
                    <h2 class="mb-4">{{ $pageTitle }}</h2>

                    <form method="POST" action="{{ route('servicezone.store') }}" id="servicezone">
                        @csrf
                        @if(isset($servicezone->id))
                            <input type="hidden" name="id" value="{{ $servicezone->id }}">
                        @endif

                        <div class="form-group mb-3">
                            <label for="zone_name">{{__('messages.zone_name')}} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="zone_name" class="form-control" placeholder="{{__('messages.enter_zone_name')}}" value="{{ $servicezone->name ?? old('name') }}" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="status">{{__('messages.status')}}</label>
                            <select name="status" id="status" class="form-control">
                                <option value="1" {{ (isset($servicezone) && $servicezone->status == 1) ? 'selected' : '' }}>{{__('messages.active')}}</option>
                                <option value="0" {{ (isset($servicezone) && $servicezone->status == 0) ? 'selected' : '' }}>{{__('messages.inactive')}}</option>
                            </select>
                        </div>

                        @if(auth()->user()->can('service zone add'))
                            <button type="submit" class="btn btn-md btn-primary float-end">{{__('messages.save')}}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-master-layout>
