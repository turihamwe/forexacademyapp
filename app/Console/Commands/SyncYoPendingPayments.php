<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\YoPaymentService;
use Illuminate\Console\Command;

class SyncYoPendingPayments extends Command
{
    protected $signature = 'yo:sync-pending-payments';

    protected $description = 'Poll Yo! Payments for pending deposit transactions (API section 7)';

    protected YoPaymentService $yoPaymentService;

    public function __construct(YoPaymentService $yoPaymentService)
    {
        parent::__construct();
        $this->yoPaymentService = $yoPaymentService;
    }

    public function handle()
    {
        $pending = Transaction::where('status', Transaction::STATUS_PENDING)
            ->whereNotNull('yo_transaction_ref')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending Yo! transactions to sync.');

            return 0;
        }

        foreach ($pending as $transaction) {
            $result = $this->yoPaymentService->syncPendingTransaction($transaction);
            $this->line(sprintf(
                '%s → %s',
                $transaction->external_reference,
                $result['status']
            ));
        }

        return 0;
    }
}
