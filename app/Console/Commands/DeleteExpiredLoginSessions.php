<?php

namespace App\Console\Commands;

use App\Models\LoginSession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sessions:clean-expired')]
#[Description('Удаляет истёкшие и ненужные QR-сессии логина')]
class DeleteExpiredLoginSessions extends Command
{
    protected $signature = 'sessions:clean-expired';

    protected $description = 'Удаляет истёкшие и ненужные QR-сессии логина';

    public function handle(): void
    {
        $count1 = LoginSession::where('expires_at', '<', now())->delete();
        $count2 = LoginSession::where('status', 'consumed')->delete();
        $count3 = LoginSession::where('status', 'expired')->delete();
        $count4 = LoginSession::where('status', 'rejected')->delete();
        $count_sum = $count1 + $count2 + $count3 + $count4;
        $this->info("Удалено просроченных записей: {$count_sum}");
    }
}
