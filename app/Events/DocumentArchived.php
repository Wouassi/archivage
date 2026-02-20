<?php
namespace App\Events;
use App\Models\Dossier;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentArchived {
    use Dispatchable, SerializesModels;
    public function __construct(public Dossier $dossier) {}
}
