<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpdateEliteTechnicians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elite:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Elite Technician status based on performance metrics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Elite threshold: > 4.5 rating, < 2 complaints
        $providers = User::where('user_type', 'provider')->with('getServiceRating')->get();

        foreach ($providers as $provider) {
            $avgRating = $provider->getServiceRating->avg('rating') ?? 0;
            $complaints = $provider->complaint_count ?? 0;

            if ($avgRating >= 4.5 && $complaints < 2) {
                if (!$provider->is_elite) {
                    $provider->is_elite = 1;
                    $provider->save();
                    Log::info("Provider {$provider->id} promoted to Elite.");
                }
            } else {
                if ($provider->is_elite) {
                    $provider->is_elite = 0;
                    $provider->save();
                    Log::info("Provider {$provider->id} demoted from Elite.");
                }
            }
        }

        $this->info('Elite statuses updated successfully.');
    }
}
