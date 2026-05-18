<?php

namespace App\Console\Commands;

use App\Models\EscrowTransaction;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseScheduledEscrow extends Command
{
    protected $signature = 'sand:release-escrow';
    protected $description = 'Auto-release escrow transactions scheduled for release.';

    public function handle(): int
    {
        $released = 0;

        EscrowTransaction::scheduledForRelease()
            ->chunk(100, function ($transactions) use (&$released) {
                foreach ($transactions as $escrow) {
                    DB::transaction(function () use ($escrow) {
                        $booking = Booking::find($escrow->escrowable_id);
                        if (! $booking) return;

                        $booking->payment_status = 'released';
                        $booking->save();

                        $escrow->update([
                            'status'          => 'released',
                            'released_amount' => $escrow->held_amount,
                            'held_amount'     => 0,
                            'released_at'     => now(),
                            'notes'           => 'Scheduled auto-release.',
                        ]);
                    });

                    $released++;
                }
            });

        $this->info("Released {$released} escrow transactions.");
        return Command::SUCCESS;
    }
}
