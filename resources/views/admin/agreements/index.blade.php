<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.legal_agreements') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Customer Agreement Card --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('messages.customer_agreement') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($customerAgreement)
                            <p><strong>{{ __('messages.status') }}:</strong>
                                @if($customerAgreement->is_active)
                                    <span class="badge bg-success">{{ __('messages.active') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                                @endif
                            </p>
                            <p><strong>{{ __('messages.version') }}:</strong> {{ $customerAgreement->version }}</p>
                            <p><strong>{{ __('messages.last_updated') }}:</strong> {{ $customerAgreement->updated_at->format('Y-m-d H:i') }}</p>
                            <a href="{{ route('admin.agreements.edit', $customerAgreement->id) }}" class="btn btn-primary btn-sm">
                                <i class="ri-pencil-line"></i> {{ __('messages.edit') }}
                            </a>
                        @else
                            <p class="text-muted">{{ __('messages.no_agreement_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Provider Agreement Card --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('messages.provider_agreement') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($providerAgreement)
                            <p><strong>{{ __('messages.status') }}:</strong>
                                @if($providerAgreement->is_active)
                                    <span class="badge bg-success">{{ __('messages.active') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                                @endif
                            </p>
                            <p><strong>{{ __('messages.version') }}:</strong> {{ $providerAgreement->version }}</p>
                            <p><strong>{{ __('messages.last_updated') }}:</strong> {{ $providerAgreement->updated_at->format('Y-m-d H:i') }}</p>
                            <a href="{{ route('admin.agreements.edit', $providerAgreement->id) }}" class="btn btn-primary btn-sm">
                                <i class="ri-pencil-line"></i> {{ __('messages.edit') }}
                            </a>
                        @else
                            <p class="text-muted">{{ __('messages.no_agreement_found') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
