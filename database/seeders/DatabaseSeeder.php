<?php

namespace Database\Seeders;

use App\Models\Depense;
use App\Models\Dossier;
use App\Models\Exercice;
use App\Models\Imputation;
use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. RÔLES
        foreach (['Associé', 'Expert-comptable', 'Collaborateur', 'Assistant'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // 2. UTILISATEURS
        $users = [
            ['name' => 'Dr. WOUASSI Dorian',  'email' => 'admin@cabinet.cm',     'role' => 'Associé'],
            ['name' => 'Mme KAMGA Sylvie',     'email' => 'expert@cabinet.cm',    'role' => 'Expert-comptable'],
            ['name' => 'M. NDJOCK Paul',       'email' => 'collab@cabinet.cm',    'role' => 'Collaborateur'],
            ['name' => 'Mlle FOTSO Marie',     'email' => 'assistant@cabinet.cm', 'role' => 'Assistant'],
        ];
        foreach ($users as $u) {
            $user = User::firstOrCreate(['email' => $u['email']], [
                'name' => $u['name'], 'password' => bcrypt('password'), 'active' => true,
            ]);
            $user->assignRole($u['role']);
        }
        $admin = User::where('email', 'admin@cabinet.cm')->first();

        // 3. EXERCICES
        Exercice::firstOrCreate(['annee' => 2025], [
            'date_debut' => '2025-01-01', 'date_fin' => '2025-12-31', 'statut' => 'clos',
        ]);
        $ex2026 = Exercice::firstOrCreate(['annee' => 2026], [
            'date_debut' => '2026-01-01', 'date_fin' => '2026-12-31', 'statut' => 'actif',
        ]);

        // 4. DÉPENSES
        $deps = [
            ['libelle' => 'Terrains',             'type' => 'INVESTISSEMENT'],
            ['libelle' => 'Bâtiments',            'type' => 'INVESTISSEMENT'],
            ['libelle' => 'Matériel de bureau',    'type' => 'INVESTISSEMENT'],
            ['libelle' => 'Matériel informatique', 'type' => 'INVESTISSEMENT'],
            ['libelle' => 'Véhicules',            'type' => 'INVESTISSEMENT'],
            ['libelle' => 'Achats de fournitures', 'type' => 'FONCTIONNEMENT'],
            ['libelle' => 'Services extérieurs',   'type' => 'FONCTIONNEMENT'],
            ['libelle' => 'Charges de personnel',  'type' => 'FONCTIONNEMENT'],
            ['libelle' => 'Entretien',            'type' => 'FONCTIONNEMENT'],
            ['libelle' => 'Frais de déplacement', 'type' => 'FONCTIONNEMENT'],
        ];
        foreach ($deps as $d) { Depense::firstOrCreate(['libelle' => $d['libelle']], $d); }

        // 5. IMPUTATIONS — COMPTES À 6 CHIFFRES (OHADA/SYSCOHADA)
        $imps = [
            ['dep' => 'Terrains',             'compte' => '241000', 'libelle' => 'Terrains bâtis'],
            ['dep' => 'Terrains',             'compte' => '242000', 'libelle' => 'Terrains non bâtis'],
            ['dep' => 'Bâtiments',            'compte' => '231000', 'libelle' => 'Bâtiments administratifs'],
            ['dep' => 'Bâtiments',            'compte' => '232000', 'libelle' => 'Installations techniques'],
            ['dep' => 'Matériel de bureau',    'compte' => '244010', 'libelle' => 'Mobilier de bureau'],
            ['dep' => 'Matériel informatique', 'compte' => '244100', 'libelle' => 'Matériel informatique et logiciels'],
            ['dep' => 'Véhicules',            'compte' => '245000', 'libelle' => 'Matériel de transport'],
            ['dep' => 'Achats de fournitures', 'compte' => '601100', 'libelle' => 'Matières premières'],
            ['dep' => 'Achats de fournitures', 'compte' => '604000', 'libelle' => 'Fournitures de bureau'],
            ['dep' => 'Services extérieurs',   'compte' => '621000', 'libelle' => 'Sous-traitance générale'],
            ['dep' => 'Services extérieurs',   'compte' => '622000', 'libelle' => 'Locations et charges locatives'],
            ['dep' => 'Charges de personnel',  'compte' => '661000', 'libelle' => 'Rémunérations du personnel'],
            ['dep' => 'Charges de personnel',  'compte' => '664000', 'libelle' => 'Charges sociales'],
            ['dep' => 'Entretien',            'compte' => '624000', 'libelle' => 'Entretien et maintenance'],
            ['dep' => 'Frais de déplacement', 'compte' => '625000', 'libelle' => 'Transport et missions'],
        ];
        foreach ($imps as $i) {
            $dep = Depense::where('libelle', $i['dep'])->first();
            if ($dep) {
                Imputation::firstOrCreate(['compte' => $i['compte']], [
                    'depense_id' => $dep->id, 'libelle' => $i['libelle'],
                ]);
            }
        }

        // 6. DOSSIERS DÉMO
        $dossiers = [
            ['op' => 'OP-2026-INV-001', 'benef' => 'SCI Immobilière du Wouri',    'montant' => 45000000,  'dep' => 'Terrains',             'imp' => '241000', 'date' => '2026-01-15'],
            ['op' => 'OP-2026-INV-002', 'benef' => 'SARL Bâti Plus Cameroun',      'montant' => 120000000, 'dep' => 'Bâtiments',            'imp' => '231000', 'date' => '2026-02-01'],
            ['op' => 'OP-2026-INV-003', 'benef' => 'Dell Technologies Cameroun',   'montant' => 8500000,   'dep' => 'Matériel informatique', 'imp' => '244100', 'date' => '2026-02-10'],
            ['op' => 'OP-2026-FON-001', 'benef' => 'Librairie Le Savoir',          'montant' => 750000,    'dep' => 'Achats de fournitures', 'imp' => '604000', 'date' => '2026-01-20'],
            ['op' => 'OP-2026-FON-002', 'benef' => 'Cabinet Audit International',  'montant' => 15000000,  'dep' => 'Services extérieurs',   'imp' => '621000', 'date' => '2026-01-25'],
            ['op' => 'OP-2026-FON-003', 'benef' => 'Employés (paie janvier)',      'montant' => 25000000,  'dep' => 'Charges de personnel',  'imp' => '661000', 'date' => '2026-01-31'],
            ['op' => 'OP-2026-FON-004', 'benef' => 'SARL Entretien Pro',           'montant' => 2500000,   'dep' => 'Entretien',            'imp' => '624000', 'date' => '2026-02-05'],
            ['op' => 'OP-2026-FON-005', 'benef' => 'Agence Voyage Express',        'montant' => 3200000,   'dep' => 'Frais de déplacement', 'imp' => '625000', 'date' => '2026-02-12'],
        ];
        foreach ($dossiers as $d) {
            $dep = Depense::where('libelle', $d['dep'])->first();
            $imp = Imputation::where('compte', $d['imp'])->first();
            if ($dep && $imp) {
                Dossier::firstOrCreate(['ordre_paiement' => $d['op']], [
                    'depense_id' => $dep->id, 'exercice_id' => $ex2026->id, 'imputation_id' => $imp->id,
                    'date_dossier' => $d['date'], 'beneficiaire' => $d['benef'],
                    'montant_engage' => $d['montant'], 'created_by' => $admin?->id,
                ]);
            }
        }

        // 7. PARAMÈTRES
        Parametre::set('app.nom_cabinet', 'Cabinet Comptable WOUASSI & Associés', 'string', 'Nom du cabinet');
        Parametre::set('app.seuil_alerte_sans_pdf', 5, 'integer', 'Seuil alerte dossiers sans PDF');
        Parametre::set('app.devise', 'FCFA', 'string', 'Devise monétaire');
        Parametre::set('scanner.enabled', true, 'boolean', 'Scanner Larascan actif');

        $this->command->info('✅ Seeding OK : 4 rôles, 4 utilisateurs, 2 exercices, 10 dépenses, 15 imputations (6 chiffres), 8 dossiers');
    }
}
