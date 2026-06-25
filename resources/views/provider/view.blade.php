<x-master-layout>
    {{ html()->form('DELETE', route('provider.destroy', $providerdata->id))->attribute('data--submit', 'provider' . $providerdata->id)->open()}}
    <style>
       
    </style>
    <main class="main-area">
        <div class="main-content">
            <div class="container-fluid">
                @include('partials._provider')
                <div class="card">
                    <div class="card-body p-30">
                        <div class="provider-details-overview">
                            <div class="provider-details-overview__statistics">
                                <div class="statistics-card statistics-card__style2 statistics-card__pending-withdraw">
                                    <h2>{{ getPriceFormat($providerData['pendWithdrwan']) ?? 0}}</h2>
                                    <h3>{{__('messages.pending_withdraw')}}</h3>
                                </div>

                                <div class="statistics-card statistics-card__style2 statistics-card__already-withdraw">
                                    <h2>{{getPriceFormat($providerData['providerAlreadyWithdrawAmt']) ?? 0}}</h2>
                                    <h3>{{__('messages.already_withdraw')}}</h3>
                                </div>

                                <div class="statistics-card statistics-card__style2 statistics-card__total-earning">
                                    <h2>{{$providerData['total_booking'] ?? 0}}</h2>
                                    <h3>{{__('messages.total_name', ['name' => __('messages.booking')])}}</h3>
                                </div>
                                <div class="statistics-card statistics-card__style2 statistics-card__withdrawable-amount">
                                    <h2>{{getPriceFormat($providerData['wallet']) ?? 0}}</h2>
                                    <h3>{{__('messages.wallet_balance')}}</h3>
                                </div>
                            </div>
                            <div class="provider-details-overview__order-overview rounded-2">
                                <div class="statistics-card statistics-card__order-overview h-100 pb-2">
                                    <h3 class="mb-0 text-center">{{__('messages.booking_overview')}}</h3>
                                    @if($data['PendingStatusCount']+$data['InProgressstatuscount']+$data['Completedstatuscount']+$data['Ongoingstatuscount'] > 0)
                                    <div id="chart" class="d-flex justify-content-center">
                                    </div>
                                    @else
                                    <p style="color:#009900; font-size:20px; font-style:italic; text-align:center; margin-top: 20%;">
                                        {{__('messages.nodata')}}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="information-details-box d-flex flex-md-row flex-column gap-4">
                                    <img class="avatar-img rounded-2 object-fit-cover" src="{{ getSingleMedia($providerdata,'profile_image', null)}}" alt="" />
                                    <div class="media-body">
                                        <h2 class="information-details-box__title mb-4">
                                            {{ $providerdata->display_name ?? '-'}}
                                        </h2>
                                        <ul class="contact-list list-inline d-flex gap-3 flex-column">
                                            <li>
                                                <i class="ri-smartphone-line"></i>
                                                <a href="tel: {{ $providerdata->contact_number }}" class="contact-info-text heading-color p-0">{{ !empty($providerdata->contact_number) ? $providerdata->contact_number: '-' }}</a>
                                            </li>
                                            <li>
                                                <i class="ri-mail-line"></i>
                                                <a href="mailto: {{ $providerdata->email }}" class="contact-info-text heading-color p-0">{{ $providerdata->email ?? '-'}}</a>
                                            </li>
                                            <li>
                                                <i class="ri-map-2-line"></i>
                                                <span class="contact-info-text">{{ !empty($providerdata->address) ? $providerdata->address : '-' }}</span>
                                            </li>
                                            <li>
                                                <i class="ri-file-text-line"></i>
                                                <span class="contact-info-text"><strong>{{ __('messages.national_id') }}:</strong> {{ $providerdata->national_id ?? '-' }}</span>
                                            </li>
                                            <li>
                                                <i class="ri-image-line"></i>
                                                <span class="contact-info-text"><strong>{{ __('messages.national_id_image') }}:</strong>
                                                    @if($providerdata->national_id_image)
                                                        <a href="{{ $providerdata->national_id_image_url }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                                            <i class="ri-eye-line"></i> {{ __('messages.preview') }}
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                            </li>
                                            <li>
                                                <i class="ri-shield-check-line"></i>
                                                <span class="contact-info-text"><strong>{{ __('messages.verification_status') }}:</strong>
                                                    @if($providerdata->verification_status === 'approved')
                                                        <span class="badge badge-active text-success bg-success-subtle">{{ __('messages.approved') }}</span>
                                                    @elseif($providerdata->verification_status === 'rejected')
                                                        <span class="badge badge-inactive text-danger bg-danger-subtle">{{ __('messages.rejected') }}</span>
                                                    @else
                                                        <span class="badge text-warning bg-warning-subtle">{{ __('messages.pending_verification') }}</span>
                                                    @endif
                                                    @if(auth()->user()->hasRole(['admin', 'demo_admin']) && $providerdata->verification_status !== 'approved')
                                                        <form method="POST" action="{{ route('provider.verification-action') }}" class="d-inline verification-form">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $providerdata->id }}">
                                                            <input type="hidden" name="action" value="approved">
                                                            <button type="submit" class="btn btn-sm btn-success ms-2 verification-btn" data-title='{{ __("messages.confirm_approve_verification") }}' data-message='{{ __("messages.confirm_approve_verification_msg") }}'>
                                                                <i class="ri-check-line"></i> {{ __('messages.approve') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if(auth()->user()->hasRole(['admin', 'demo_admin']) && $providerdata->verification_status !== 'rejected')
                                                        <form method="POST" action="{{ route('provider.verification-action') }}" class="d-inline verification-form">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $providerdata->id }}">
                                                            <input type="hidden" name="action" value="rejected">
                                                            <button type="submit" class="btn btn-sm btn-danger ms-1 verification-btn" data-title='{{ __("messages.confirm_reject_verification") }}' data-message='{{ __("messages.confirm_reject_verification_msg") }}'>
                                                                <i class="ri-close-line"></i> {{ __('messages.reject') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Zones Section at the bottom -->
                        <div class="zones-header">
                            <h2 class="zones-header__title">{{ __('messages.available_zones') }}</h2>
                            <div class="zone-list">

                                @php
                                    $zones = $providerdata->zones->where('status', 1);
                                @endphp
                             
                                @forelse($zones as $zone)
                                   @if($zone->status==1)
                                    <div class="zone-item">
                                        <i class="ri-map-pin-line"></i>
                                        <span>{{ $zone->name }}</span>
                                    </div>
                                    @endif
                                @empty
                                    <div class="no-zones-message">
                                        <i class="ri-information-line me-2"></i>
                                        {{ __('messages.no_zones_available') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
{{ html()->form()->close() }}
@section('bottom_script')


    <script type="text/javascript">
        $(document).on('click', '.verification-btn', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            var title = $(this).data('title');
            var message = $(this).data('message');

            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#5F60B9',
                cancelButtonColor: '#858482',
                confirmButtonText: '{{ __("messages.yes") }}',
                cancelButtonText: '{{ __("messages.no") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        var pendingCount = parseInt("{{ $data['PendingStatusCount'] }}");
        var inProgressCount = parseInt("{{ $data['InProgressstatuscount'] }}");
        var Completedcount = parseInt("{{ $data['Completedstatuscount'] }}");
        var onGoingCount = parseInt("{{ $data['Ongoingstatuscount'] }}");
        var cancelledCount = parseInt("{{ $data['CancelledStatusCount'] }}");

        var options = {
            series: [Completedcount, onGoingCount, pendingCount, inProgressCount, cancelledCount],
            chart: {
                width: 380,
                type: 'pie',
            },
            labels: ['Completed', 'On Going','Pending', 'In Progress', 'Cancelled'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();
    </script>
@endsection
</x-master-layout>
