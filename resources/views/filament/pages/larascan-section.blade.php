<div class="w-full">
    @livewire('larascan-scanner')
</div>

{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  ARCHITECTURE DE COMMUNICATION                                  ║
    ║                                                                  ║
    ║  LarascanScanner (Livewire)                                      ║
    ║       │ scan/upload → ajoute à la liste interne                  ║
    ║       │ savePathsToSession() → session('larascan_pdf_paths')     ║
    ║       │                                                          ║
    ║       │  *** PAS de JS bridge ***                                ║
    ║       │  *** PAS de champ hidden ***                             ║
    ║       │  *** PAS de polling ***                                  ║
    ║       │                                                          ║
    ║  [Utilisateur clique "Créer"]                                    ║
    ║       │                                                          ║
    ║  CreateDossier::mutateFormDataBeforeCreate()                     ║
    ║       └── session('larascan_pdf_paths') → fusionne en 1 PDF      ║
    ║                                                                  ║
    ║  La session PHP est la SEULE source de vérité.                   ║
    ║  Aucun JS ne peut déclencher une création de dossier.            ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}
