<x-master-layout>
    @php
        $invoiceCurrencySymbol = 'ر.س';
        $siteSetupCurrency = \App\Models\Setting::getValueByKey('site-setup', 'site-setup');
        if ($siteSetupCurrency && !empty($siteSetupCurrency->default_currency)) {
            $cCountry = \App\Models\Country::find($siteSetupCurrency->default_currency);
            if ($cCountry) {
                $invoiceCurrencySymbol = $cCountry->symbol;
            }
        }
    @endphp
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4 class="fw-bold">{{ __('messages.invoice') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row pb-4 mx-0 card-header-border">
                            <div class="col-lg-12 mb-3">
                                @php
                                    $invoiceThemeSetup = \App\Models\Setting::where('type', 'theme-setup')->where('key', 'theme-setup')->first();
                                @endphp
                                <img class="avatar avatar-50 is-squared" src="{{ getSingleMedia($invoiceThemeSetup ?? null, 'logo', false) }}">
                            </div>
                            <div class="col-lg-6">
                                <div class="text-start">
                                    <h5 class="fw-bold mb-2">{{ __('messages.invoice_id') }}</h5>
                                    <p class="mb-0">IN-05866</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="text-end">
                                    <h5 class="fw-bold mb-2">{{ __('messages.invoice_date') }}</h5>
                                    <p class="mb-0">2nd Oct 2019 03:16 PM</p>
                                </div>
                            </div>
                        </div>
                        <div class="row pt-4 pb-5 mx-0">
                            <div class="col-lg-6">
                                <div class="text-start">
                                    <h5 class="fw-bold mb-3">{{ __('messages.invoice') }} From</h5>
                                    <p class="mb-0 mb-1">Chris Wood</p>
                                    <p class="mb-0 mb-1">4183 Forest Avenue</p>
                                    <p class="mb-0 mb-1">Makkah</p>
                                    <p class="mb-0 mb-1">24252</p>
                                    <p class="mb-0 mb-2">Saudi Arabia</p>
                                    <p class="mb-0 mb-2">chris.wood@blueberry.com</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="text-end">
                                    <h5 class="fw-bold mb-3">{{ __('messages.invoice') }} To</h5>
                                    <p class="mb-0 mb-1">Blueberry LLC</p>
                                    <p class="mb-0 mb-1">354 King Fahd Road</p>
                                    <p class="mb-0 mb-1">Riyadh</p>
                                    <p class="mb-0 mb-1">12211</p>
                                    <p class="mb-0 mb-2">Saudi Arabia</p>
                                    <p class="mb-0 mb-2">info@blueberry.com</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead>
                                                <tr class="text-muted">
                                                    <th scope="col" class="text-start">{{ __('messages.id') }}</th>
                                                    <th scope="col">{{ __('messages.description') }}</th>
                                                    <th scope="col" class="text-end">{{ __('messages.quantity') }}</th>
                                                    <th scope="col" class="text-end">{{ __('messages.price') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td class="text-start">
                                                        1
                                                    </td>
                                                    <td>
                                                        OR-325548
                                                    </td>
                                                    <td class="text-end">
                                                        6
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $invoiceCurrencySymbol }}800
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start">
                                                        2
                                                    </td>
                                                    <td>
                                                        OR-500008
                                                    </td>
                                                    <td class="text-end">
                                                        3
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $invoiceCurrencySymbol }}500
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start">
                                                        3
                                                    </td>
                                                    <td>
                                                        OR-654412
                                                    </td>
                                                    <td class="text-end">
                                                        5
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $invoiceCurrencySymbol }}600
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-end mb-2">
                                            Subtotal: <p class="ms-2 mb-0">{{ $invoiceCurrencySymbol }}1,600</p>
                                        </div>
                                        <div class="d-flex justify-content-end mb-2">
                                            Taxes: <p class="ms-2 mb-0">{{ $invoiceCurrencySymbol }}300</p>
                                        </div>
                                        <div class="d-flex justify-content-end mb-2">
                                            Total: <p class="ms-2 mb-0 fw-bold">{{ $invoiceCurrencySymbol }}1,900</p>
                                        </div>

                                    </li>
                                </ul>
                            </div>
                            <div class="col-lg-12">
                                <div class="d-flex flex-wrap justify-content-between align-items-center p-4">
                                    <div class="flex align-items-start flex-column">
                                        <h6>{{ __('messages.notes') }}</h6>
                                        <p class="mb-0 my-2">Please send all items at the same time to the shipping address. Thanksin advance.</p>
                                    </div>
                                    <div>
                                        <button class="btn btn-secondary px-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Print
                                        </button>
                                        <button class="btn btn-primary px-4">{{ __('messages.send') }}</button>/button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
