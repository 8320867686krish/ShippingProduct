<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = file_get_contents('php://input');

            // Check if the received data is valid JSON
            if ($data === false) {
                Log::warning('Failed to get input data');
                return;
            }

            $data_json = json_decode($data, true);

            // Check if JSON decoding is successful
            if ($data_json === null) {
                Log::warning('Failed to decode JSON data:', ['data' => $data]);
                return;
            }

            Log::info('Decoded Webhook Data:', ['data' => $data_json]);
    }
}
