<?php

namespace App\Observers;

use App\Models\Transfer;

class TransferObserver
{
    public function created(Transfer $transfer): void
    {
        Transfer::flushCache();
    }

    public function updated(Transfer $transfer): void
    {
        Transfer::flushCache();
    }

    public function deleted(Transfer $transfer): void
    {
        Transfer::flushCache();
    }
}
