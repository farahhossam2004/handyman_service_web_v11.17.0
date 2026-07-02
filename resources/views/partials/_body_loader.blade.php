@php
    $loaderThemeSetup = \App\Models\Setting::where('type', 'theme-setup')->where('key', 'theme-setup')->first();
@endphp
<div id="loading-center">
    <div class="loader-skeleton">
        <div class="loader-ring">
            <div class="loader-ring-inner"></div>
        </div>
        <div class="loader-icon">
            <img src="{{ getSingleMedia($loaderThemeSetup ?? null, 'logo', false) }}" alt="logo" style="height: 32px; width: auto;">
        </div>
        <div class="loader-text">Loading</div>
        <div class="loader-dots">
            <span></span><span></span><span></span>
        </div>
    </div>
</div>
