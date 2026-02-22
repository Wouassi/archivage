<?php

namespace App\Services;

use App\Models\Dossier;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Log;

/**
 * Service de rappels automatiques.
 *
 * Détecte les dossiers incomplets (sans PDF) et envoie des
 * notifications aux utilisateurs.
 *
 * Usage :
 *   php artisan app:send-reminders          (quotidien via cron)
 *   ReminderService::checkAndNotify();      (appelé manuellement)
 *
 * Cron (dans app/Console/Kernel.php) :
 *   $schedule->command('app:send-reminders')->dailyAt('08:00');
 */
class ReminderService
{
    /**
     * Vérifier les dossiers incomplets et notifier.
     */
    public static function checkAndNotify(): int
    {
        $notified = 0;

        // ═══ 1. Dossiers sans PDF depuis plus de 3 jours ═══
        $oldIncomplets = Dossier::whereNull('fichier_path')
            ->where('created_at', '<', now()->subDays(3))
            ->with(['depense', 'exercice'])
            ->get();

        if ($oldIncomplets->isNotEmpty()) {
            $count = $oldIncomplets->count();
            $total = $oldIncomplets->sum('montant_engage');
            $fmt = number_format($total, 0, ',', ' ');

            $top5 = $oldIncomplets->take(5)->map(fn ($d) =>
                "• {$d->ordre_paiement} — {$d->beneficiaire}"
            )->implode("\n");

            $body = "📂 {$count} dossier(s) sans PDF depuis plus de 3 jours\n"
                  . "💰 Montant total : {$fmt} FCFA\n\n"
                  . $top5
                  . ($count > 5 ? "\n… et " . ($count - 5) . " autre(s)" : "");

            self::notifyAllUsers(
                "⚠️ {$count} dossier(s) incomplet(s)",
                $body,
                'warning'
            );
            $notified += $count;
        }

        // ═══ 2. Dossiers sans PDF depuis plus de 7 jours (urgent) ═══
        $urgents = Dossier::whereNull('fichier_path')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        if ($urgents > 0) {
            self::notifyAllUsers(
                "🔴 URGENT : {$urgents} dossier(s) sans PDF depuis 7+ jours",
                "Ces dossiers nécessitent une action immédiate. "
                . "Scannez ou uploadez les documents manquants.",
                'danger'
            );
        }

        // ═══ 3. Rappel exercice bientôt clos ═══
        $exercicesProches = \App\Models\Exercice::where('statut', 'actif')
            ->whereNotNull('date_fin')
            ->where('date_fin', '<=', now()->addDays(30))
            ->where('date_fin', '>=', now())
            ->get();

        foreach ($exercicesProches as $ex) {
            $jours = now()->diffInDays($ex->date_fin);
            $sansPdf = Dossier::where('exercice_id', $ex->id)->whereNull('fichier_path')->count();

            if ($sansPdf > 0) {
                self::notifyAllUsers(
                    "📅 Exercice {$ex->annee} : {$jours} jour(s) restant(s)",
                    "⚠️ {$sansPdf} dossier(s) sans PDF à compléter avant la clôture le "
                    . $ex->date_fin->format('d/m/Y') . ".",
                    'warning'
                );
                $notified++;
            }
        }

        Log::info("[Rappels] {$notified} notification(s) envoyée(s)");
        return $notified;
    }

    /**
     * Envoyer une notification à tous les utilisateurs.
     */
    private static function notifyAllUsers(string $title, string $body, string $color = 'info'): void
    {
        $users = User::all();

        foreach ($users as $user) {
            try {
                Notification::make()
                    ->title($title)
                    ->body($body)
                    ->icon(match ($color) {
                        'danger'  => 'heroicon-o-exclamation-triangle',
                        'warning' => 'heroicon-o-bell-alert',
                        default   => 'heroicon-o-bell',
                    })
                    ->iconColor($color)
                    ->{$color}()
                    ->actions([
                        Action::make('voir')
                            ->label('Voir les dossiers')
                            ->url(url('/admin/dossiers?tableFilters[fichier_path][value]=0'))
                            ->openUrlInNewTab(false),
                        Action::make('lu')
                            ->label('✅ Compris')
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($user);
            } catch (\Throwable $e) {
                Log::debug("[Rappels] Erreur pour {$user->email}: " . $e->getMessage());
            }
        }
    }
}
