<?php

namespace App\Jobs;
use App\Services\HoldService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHoldsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private HoldService $holdService;

    /**
     * Create a new job instance.
     */
    public function __construct(HoldService $holdService)
    {
        $this->holdService = $holdService;
    }

    /**
     * Execute the job.
     */
    public function handle(HoldService $holdService): void
    {
        Log::info("Starting expired holds release job");

        $releasedCount = $holdService->releaseExpiredHolds();

        Log::info("Expired holds release job completed", [
            'released_count' => $releasedCount
        ]);
    }
}
