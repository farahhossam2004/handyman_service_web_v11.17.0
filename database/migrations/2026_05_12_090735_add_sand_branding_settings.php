<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['type' => 'theme-setup', 'key' => 'theme-setup'],
            [
                'value' => json_encode([
                    'primary_color'    => '#397D8D',
                    'primary_hover'    => '#316D7D',
                    'sidebar_bg'       => '#1A2E35',
                    'sidebar_text'     => '#C8DDE3',
                    'logo'             => null,
                    'favicon'          => null,
                    'footer_logo'      => null,
                    'loader'           => null,
                ]),
            ]
        );

        DB::table('app_settings')->updateOrInsert(
            ['id' => 1],
            ['site_name' => 'Sand | سند']
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('type', 'theme-setup')
            ->where('key', 'theme-setup')
            ->delete();

        DB::table('app_settings')
            ->where('site_name', 'Sand | سند')
            ->update(['site_name' => '']);
    }
};
