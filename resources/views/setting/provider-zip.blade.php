@php
    $siteSetupCurrency = \App\Models\Setting::where('type', 'SITE_SETUP')->where('key', 'SITE_SETUP')->first();
    $siteSetupCurrency = json_decode($siteSetupCurrency->value ?? '{}');
    $currencySymbol = 'ر.س';
    if ($siteSetupCurrency && !empty($siteSetupCurrency->default_currency)) {
        $cCountry = \App\Models\Country::find($siteSetupCurrency->default_currency);
        if ($cCountry && !empty($cCountry->symbol)) {
            $currencySymbol = $cCountry->symbol;
        }
    }
@endphp
<div class="col-md-12">
    <form action="{{ route('upload.zip') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label class="form-control-label" for="file">{{ __('messages.upload_zip_file') }}</label>/label>
            <input type="file" name="file" id="file" class="form-control" required>
        </div>
        <div class="d-flex justify-content-md-end">
            <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>/button>
        </div>
    </form>
</div>

<div class="mt-4">
    <div class="col-lg-12">
        <div>
            <strong>Note:</strong>
            <ul class="mb-0 mt-2">
                <li>
                    <strong>Commission:</strong> If you choose "Commission," the system will charge providers a percentage or a flat fee on each booking or transaction. <br>
                    &emsp;<i>Logic:</i> This means that every time a customer books a service, the platform takes a cut (based on the chosen commission percentage or flat fee). 
                    For example, if the commission is set at 20%, and a handyman completes a job for {{ $currencySymbol }}100, the platform will take {{ $currencySymbol }}20, and the provider will receive {{ $currencySymbol }}80.
                </li>
                <br>
                <li>
                    <strong>Subscription:</strong> If you choose "Subscription," providers will pay a fixed fee periodically (e.g., monthly or yearly) to use the platform's services. <br>
                    &emsp;<i>Logic:</i> This model allows service providers to pay a fixed amount every month or year to stay active on the platform. 
                    For example, if a provider pays {{ $currencySymbol }}50 per month as a subscription, regardless of how many jobs they complete, they will continue to have access to the platform.
                </li>
            </ul>
        </div>
    </div>
</div>