<!DOCTYPE html>
<html  lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{csrf_token()}}">
        <meta name="baseUrl" content="{{ config('app.url') }}" />
        @php
            $titleGeneralSetting = \DB::table('settings')->where('key', 'general-setting')->value('value');
            $titleSiteName = $titleGeneralSetting ? json_decode($titleGeneralSetting, true)['site_name'] ?? null : null;
        @endphp
        <title>{{ $titleSiteName ?? 'Sanad Dashboard' }} | سند</title>
        <link rel="shortcut icon" class="site_favicon_preview" href="{{ asset('landing-images/greylogo.png') }}" />
        <link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css')}}">
        <link href="{{ asset('css/frontend.min.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/frontend/slick.css')}}">
    </head>
    <script>
        window._locale = '{{ $locale }}';
        window._translations = {!! cache('translations') !!};
    </script>
    <body>
        <div id="app">
            <Default></Default>
        </div>
    </body>
</html>
