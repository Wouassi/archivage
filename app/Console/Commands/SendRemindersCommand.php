<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    protected $signature = 'app:send-reminders';
    protected $description = 'Envoyer des rappels pour les dossiers incomplets (sans PDF)';

    public function handle(): int
    {
        $this->info('Vérification des dossiers incomplets...');

        $count = ReminderService::checkAndNotify();

        $this->info("✅ {$count} rappel(s) traité(s)");

        return 0;
    }
}
