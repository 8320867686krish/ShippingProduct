<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData;
    protected $name;
    protected $shopDomain;
    protected $to;
    protected $cc;
    protected $mailableClass;

    /**
     * Create a new job instance.
     */
    public function __construct($emailData, $mailableClass)
    {
        $this->name = $emailData['name'];
        $this->shopDomain = $emailData['shopDomain'];
        $this->to = $emailData['to'];
        $this->cc = $emailData['cc'];
        $this->mailableClass = $mailableClass;
        Log::info("mail job", ['To mail'=>$this->to]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $mailable = new $this->mailableClass($this->name, $this->shopDomain);
            $mailable->cc($this->cc);
            Log::info("mail job", ['To mail' => $mailable]);
            Mail::to($this->to)->send($mailable);
        } catch (TransportException $e) {
            Log::error("Mail sending failed for {$this->to}: " . $e->getMessage());
        }
    }
}
