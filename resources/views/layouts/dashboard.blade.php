@php
    $rtlLocales = ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa'];
    $dir = session()->has('dir') ? session()->get('dir') : (in_array(app()->getLocale(), $rtlLocales, true) ? 'rtl' : 'ltr');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{ config('app.url') }}" />

    @php
        $titleGeneralSetting = \DB::table('settings')->where('key', 'general-setting')->value('value');
        $titleSiteName = $titleGeneralSetting ? json_decode($titleGeneralSetting, true)['site_name'] ?? null : null;
    @endphp
    <title>{{ $pageTitle ?? $titleSiteName ?? 'Sanad Dashboard' }} | سند</title>

    @include('partials._head')
</head>
<body class="" id="app">
@include('partials._body')
</body>
</html>
