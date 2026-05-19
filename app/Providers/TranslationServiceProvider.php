<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('TranslationServiceProvider: settings table not available during boot', [
                'error' => $e->getMessage(),
            ]);
            return;
        }

        try {
            Cache::rememberForever('translations', function () {
                $translations = collect();
                $language_option = ["ar","nl","en","fr","de","hi","it"];

                if (\Session::get('setup_data') == '') {
                    $setup_data = sitesetupSession('get');
                    if ($setup_data) {
                        $language_option = $setup_data->language_option;
                    }
                }

                foreach ($language_option as $locale) {
                    $translations[$locale] = [
                        'php' => $this->phpTranslations($locale),
                        'json' => $this->jsonTranslations($locale),
                    ];
                }

                return $translations;
            });
        } catch (\Throwable $e) {
            Log::error('TranslationServiceProvider: failed to cache translations: ' . $e->getMessage());
        }
    }

    private function phpTranslations($locale)
    {
        $path = resource_path("lang/$locale");

        if (!File::exists($path) || !File::isDirectory($path)) {
            return collect();
        }

        return collect(File::allFiles($path))->flatMap(function ($file) use ($locale) {
            $key = ($translation = $file->getBasename('.php'));

            return [$key => trans($translation, [], $locale)];
        });
    }

    private function jsonTranslations($locale)
    {
        $path = resource_path("lang/$locale.json");

        if (is_string($path) && is_readable($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }
}
