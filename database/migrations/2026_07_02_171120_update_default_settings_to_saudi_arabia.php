<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('settings')
            ->where('type', 'general-setting')
            ->where('key', 'general-setting')
            ->first();

        if ($setting) {
            $value = json_decode($setting->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return;
            }

            $value['country_id'] = '191';
            $value['state_id'] = '3155';
            $value['city_id'] = '37420';
            $value['address'] = 'Makkah Al Mukarramah, Saudi Arabia';
            $value['zipcode'] = '24252';

            DB::table('settings')
                ->where('type', 'general-setting')
                ->where('key', 'general-setting')
                ->update(['value' => json_encode($value)]);
        }
    }

    public function down(): void
    {
        $setting = DB::table('settings')
            ->where('type', 'general-setting')
            ->where('key', 'general-setting')
            ->first();

        if ($setting) {
            $value = json_decode($setting->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return;
            }

            $value['country_id'] = '231';
            $value['state_id'] = '3956';
            $value['city_id'] = '47855';
            $value['address'] = '45 HUDSON AVE UNIT 1296 ALBANY NY 12201-6256 USA';
            $value['zipcode'] = '12201';

            DB::table('settings')
                ->where('type', 'general-setting')
                ->where('key', 'general-setting')
                ->update(['value' => json_encode($value)]);
        }
    }
};
