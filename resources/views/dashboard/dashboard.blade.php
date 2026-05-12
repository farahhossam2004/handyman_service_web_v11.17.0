<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <!-- test-->
            @if($rezorpayX_details ==null)
            <div class="col-md-12">
                <div class="alert alert-warning border border-warning py-3">
                    <p class="h5 text-warning">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <i class="fas fa-info-circle"></i>
                            {{__('messages.info_message')}}
                            <a href="{{ route('setting.index') }}" target="_blank" class="text-primary"> {{__('messages.here_is_the_link')}}<i class="fas fa-external-link-alt mx-2"></i></a>
                        </div>
                    </p>
                </div>
            </div>
            @endif

            <div class="col-md-12">
                @php
                $sandCards = [
                    ['title' => __('messages.total_inspection_requests'), 'value' => $data['dashboard']['count_total_inspection_requests'] ?? 0, 'icon' => 'fas fa-search', 'color' => 'bg-primary'],
                    ['title' => __('messages.pending_quotes'), 'value' => $data['dashboard']['count_pending_quotes'] ?? 0, 'icon' => 'fas fa-file-invoice', 'color' => 'bg-warning'],
                    ['title' => __('messages.approved_quotes'), 'value' => $data['dashboard']['count_approved_quotes'] ?? 0, 'icon' => 'fas fa-file-invoice-dollar', 'color' => 'bg-success'],
                    ['title' => __('messages.active_orders'), 'value' => $data['dashboard']['count_active_orders'] ?? 0, 'icon' => 'fas fa-briefcase', 'color' => 'bg-info'],
                    ['title' => __('messages.completed_orders'), 'value' => $data['dashboard']['count_completed_orders'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'bg-success'],
                    ['title' => __('messages.cancelled_orders'), 'value' => $data['dashboard']['count_cancelled_orders'] ?? 0, 'icon' => 'fas fa-times-circle', 'color' => 'bg-danger'],
                    ['title' => __('messages.held_payments'), 'value' => $data['dashboard']['count_held_payments'] ?? 0, 'icon' => 'fas fa-lock', 'color' => 'bg-warning'],
                    ['title' => __('messages.released_payments'), 'value' => $data['dashboard']['count_released_payments'] ?? 0, 'icon' => 'fas fa-unlock', 'color' => 'bg-success'],
                    ['title' => __('messages.refunded_payments'), 'value' => $data['dashboard']['count_refunded_payments'] ?? 0, 'icon' => 'fas fa-undo', 'color' => 'bg-danger'],
                    ['title' => __('messages.active_providers'), 'value' => $data['dashboard']['count_active_providers'] ?? 0, 'icon' => 'fas fa-users', 'color' => 'bg-primary'],
                    ['title' => __('messages.elite_technicians'), 'value' => $data['dashboard']['count_elite_technicians'] ?? 0, 'icon' => 'fas fa-medal', 'color' => 'bg-warning'],
                    ['title' => __('messages.total_platform_revenue'), 'value' => getPriceFormat($data['total_revenue'] ?? 0), 'icon' => 'fas fa-chart-line', 'color' => 'bg-success'],
                ];
                @endphp
                <div class="row">
                    @foreach($sandCards as $card)
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 text-muted">{{ $card['title'] }}</p>
                                        <h4 class="mb-0 fw-bold">{{ $card['value'] }}</h4>
                                    </div>
                                    <div class="icon-shape rounded-circle {{ $card['color'] }} text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="{{ $card['icon'] }} fs-5"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="">{{__('messages.monthly_revenue')}}</h4>
                        </div>

                        {{-- <div class="d-flex gap-2 align-items-center">
                            <select id="filter-type" class="form-select">
                                <option value="month" selected>{{ __('messages.monthly') }}</option>
                                <option value="week">{{ __('messages.weekly') }}</option>
                                <option value="year">{{ __('messages.yearly') }}</option>
                                <option value="custom">{{ __('messages.custom_range') }}</option>
                            </select>

                            <select id="filter-year" class="form-select">
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>

                            <select id="filter-month" class="form-select">
                                <option value="all" selected>{{ __('messages.all_months') }}</option>
                                @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $index => $month)
                                    <option value="{{ $index + 1 }}">{{ $month }}</option>
                                @endforeach
                            </select>

                            <input type="date" id="filter-start-date" class="form-control d-none" placeholder="{{ __('messages.start_date') }}">
                            <input type="date" id="filter-end-date" class="form-control d-none" placeholder="{{ __('messages.end_date') }}">
                        </div> --}}


                        <div id="monthly-revenue" class="custom-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="card top-providers card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between gap-10">
                        <h4 class="fw-bold">{{ __('messages.recent_provider') }} ({{$data['dashboard']['count_total_provider']}})</h4>
                            <a href="{{ route('provider.index') }}" class="btn-link btn-link-hover"><u>{{__('messages.view_all')}} </u></a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="common-list list-unstyled">
                            @foreach($data['dashboard']['new_provider'] as $provider)
                            <li>
                                <div class="media gap-3">
                                    <div class="h-avatar is-medium h-5">
                                        <img class="avatar-50 rounded-circle bg-light" alt="user-icon" src="{{ getSingleMedia($provider,'profile_image', null) }}">
                                    </div>

                                    <div class="media-body ">
                                        <a href="{{ route('provider_info', $provider->id) }}">
                                            <h5 class="mb-1"><span class="fw-bold">{{ !empty($provider->display_name) ? $provider->display_name : '-' }}</span></h5>
                                            <span class="mb-1">{{ $provider->email ?? '-' }}</span>
                                        </a>
                                            <span class="common-list_rating d-flex gap-1">
                                                <i class="ri-star-s-fill"></i>
                                                {{round($provider->getServiceRating->avg('rating'), 1)}}
                                            </span>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="card top-providers card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between gap-10">
                        <h4 class="fw-bold">{{ __('messages.recent_customer') }} ({{$data['dashboard']['count_total_customer']}})</h4>
                        <a href="{{ route('user.index') }}" class="btn-link btn-link-hover"><u>{{__('messages.view_all')}}</u></a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="common-list list-unstyled">
                            @foreach($data['dashboard']['new_customer'] as $customer)
                            <li style="pointer-events:none;">
                                <div class="media gap-3">
                                    <div class="h-avatar is-medium h-5">
                                        <img class="avatar-50 rounded-circle bg-light" alt="user-icon" src="{{ getSingleMedia($customer,'profile_image', null) }}">
                                    </div>
                                    <div class="media-body ">
                                        <h5 class="mb-1"><span class="fw-bold">{{!empty($customer->display_name) ? $customer->display_name : '-'}}</span>  </h5>
                                        <span>
                                            {{
                                                optional($data['datetime'])->date_format && optional($data['datetime'])->time_format
                                                ? \Carbon\Carbon::parse($customer->created_at)
                                                    ->format(optional($data['datetime'])->date_format) .'  '. \Carbon\Carbon::parse($customer->created_at)
                                                    ->format(optional($data['datetime'])->time_format)
                                                : ''
                                            }}
                                        </span>


                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-12">
                <div class="card recent-activities card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between gap-10">
                        <h4>{{__('messages.recent_booking')}} ({{$data['dashboard']['count_total_booking']}})</h4>
                        <a href="{{ route('booking.index') }}" class="btn-link btn-link-hover"><u>{{__('messages.view_all')}}</u></a>
                    </div>
                        <div class="card-body">
                            <ul class="common-list p-0">

                                @foreach($data['dashboard']['upcomming_booking'] as $booking)
                                    <li class="d-flex gap-3 align-items-start align-items-lg-center justify-content-between flex-column flex-sm-row " >
                                        <div class="media align-items-center gap-3">
                                                <div class="h-avatar is-medium h-5">
                                                    <img class="avatar-50 rounded-circle bg-light" alt="user-icon" src="{{ getSingleMedia($booking->customer,'profile_image', null) }}">
                                                </div>
                                                <div class="media-body ">
                                                    <a href="{{ route('booking.show', $booking->id) }}">
                                                        <h5 class="mb-1">#{{$booking->id}}</h5>
                                                    </a>
                                                    <span>{{
        optional($data['datetime'])->date_format && optional($data['datetime'])->time_format
        ? date(optional($data['datetime'])->date_format, strtotime($booking->date)) .'  '. date(optional($data['datetime'])->time_format, strtotime($booking->date))
        : ''
    }}</span>
                                                    {{-- <span>{{(date("$data['datetime']->date_format $data['datetime']->time_format", strtotime($booking->date)))}}</span> --}}
                                                </div>
                                        </div>
                                        <span class="badge rounded-pill py-2 px-3 bg-primary-subtle text-capitalize">{{ucwords(str_replace('_', ' ', $booking->status))}}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
<script>
    var chartData = '<?php echo $data['category_chart']['chartdata']; ?>';
    var currency_data = '<?php echo json_encode(currency_data()); ?>';
    var currency_object = JSON.parse(currency_data);
    var chartArray = JSON.parse(chartData);
    var chartlabel = '<?php echo $data['category_chart']['chartlabel']; ?>';
    var labelsArray = JSON.parse(chartlabel);
    if(jQuery('#monthly-revenue').length){
        var options = {
        series: [{
            name: 'revenue',
            data: [ {{ implode ( ',' ,$data['revenueData'] ) }} ]
            // data: [30, 39, 20, 28, 36, 33,20]
        }],
        chart: {
            height: 265,
            type: 'line',
            toolbar:{
                show: true,
            },
            events: {
                click: function(chart, w, e) {
                }
            }
        },
        colors: ['var(--bs-primary)'],
        plotOptions: {
            bar: {
                horizontal: false,
                s̶t̶a̶r̶t̶i̶n̶g̶S̶h̶a̶p̶e̶: 'flat',
                e̶n̶d̶i̶n̶g̶S̶h̶a̶p̶e̶: 'flat',
                borderRadius: 0,
                columnWidth: '70%',
                barHeight: '70%',
                distributed: false,
                rangeBarOverlap: true,
                rangeBarGroupRows: false,
                colors: {
                    ranges: [{
                        from: 0,
                        to: 0,
                        color: undefined
                    }],
                    backgroundBarColors: [],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 0,
                },
                dataLabels: {
                    position: 'top',
                    maxItems: 100,
                    hideOverflowingLabels: true,
                }
            }
        },
        dataLabels: {
          enabled: false
        },
        grid: {
            borderColor: 'var(--bs-border-color)',
            xaxis: {
                lines: {
                    show: false
                }
            },
            yaxis: {
                lines: {
                    show: true,
                }
            }
        },
        legend: {
          show: false
        },
        yaxis: {
            labels: {
                offsetY:0,
                minWidth: 60,
                maxWidth: 60,
                style: {
                    colors: 'var(--bs-body-color)',
                },
                formatter: function(value) {
                    return currency_object.currency_symbol + value;
                }
            },
        },
        xaxis: {
            categories: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'June',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],
            labels: {
                minHeight: 22,
                maxHeight: 22,
                style: {
                    colors: 'var(--bs-body-color)',
                    fontSize: '12px'
                }
            }
        }
        };

        var chart = new ApexCharts(document.querySelector("#monthly-revenue"), options);
        chart.render();

        $('#filter-type').on('change', function() {
    var type = $(this).val();

    if(type == 'custom') {
        $('#filter-start-date, #filter-end-date').removeClass('d-none');
        $('#filter-month').addClass('d-none');
    } else if(type == 'year') {
        $('#filter-month, #filter-start-date, #filter-end-date').addClass('d-none');
    } else if(type == 'week') {
        $('#filter-month, #filter-start-date, #filter-end-date').addClass('d-none');
    } else { // monthly
        $('#filter-month').removeClass('d-none');
        $('#filter-start-date, #filter-end-date').addClass('d-none');
    }
});

$('#filter-year, #filter-month, #filter-start-date, #filter-end-date, #filter-type').on('change', function() {
    var type = $('#filter-type').val();
    var year = $('#filter-year').val();
    var month = $('#filter-month').val();
    var start_date = $('#filter-start-date').val();
    var end_date = $('#filter-end-date').val();

    $.ajax({
        url: "{{ route('dashboard.revenue.filter') }}",
        type: "GET",
        data: { type: type, year: year, month: month, start_date: start_date, end_date: end_date },
        success: function(response) {
            console.log(response); // Check the response from the server

            // Ensure the chart gets updated correctly
            chart.updateSeries([{
                name: 'revenue',
                data: response.revenueData // Assuming `revenueData` is the array you need
            }]);

            chart.updateOptions({
                xaxis: { categories: response.chartLabels } // Ensure chartLabels are updated
            });
        },
        error: function(error) {
            console.error("Error fetching filtered data:", error);
        }
    });
});



    }

</script>
