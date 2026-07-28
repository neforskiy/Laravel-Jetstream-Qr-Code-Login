<?php

namespace App\Console\Commands;

use App\Models\LoginSession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sessions:clean-expired')]
#[Description('Удаляет истёкшие QR-сессии логина')]
class DeleteExpiredLoginSessions extends Command
{
    protected $signature = 'sessions:clean-expired';

    protected $description = 'Удаляет истёкшие QR-сессии логина';

    public function handle(): void
    {
        $count = LoginSession::where('expires_at', '<', now())->delete();

        $this->info("Удалено просроченных записей: {$count}");
    }
}
