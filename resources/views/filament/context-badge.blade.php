<div>
@php
    $ctx = \App\Services\WorkContextService::class;
    $exercice = $ctx::getExercice();
    $depense = $ctx::getDepense();
@endphp

@if($exercice)
    <div class="mx-3 mb-3 p-3 rounded-xl bg-gradient-to-r from-indigo-500/10 to-emerald-500/10 border border-indigo-200/50 dark:border-indigo-700/50">
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-indigo-500 text-white text-xs font-bold">{{ substr($exercice->annee, -2) }}</span>
            <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">Exercice {{ $exercice->annee }}</span>
            @if($exercice->statut === 'actif')
                <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-700">Actif</span>
            @else
                <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-600">Clos</span>
            @endif
        </div>
        @if($depense)
        <div class="flex items-center gap-2 mt-1.5">
            @if($depense->type === 'INVESTISSEMENT')
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-sky-500 text-white text-[10px]">INV</span>
                <span class="text-[11px] text-sky-700 dark:text-sky-300 truncate">{{ $depense->libelle }}</span>
            @else
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-500 text-white text-[10px]">FON</span>
                <span class="text-[11px] text-amber-700 dark:text-amber-300 truncate">{{ $depense->libelle }}</span>
            @endif
        </div>
        @endif
    </div>
@else
    <div class="mx-3 mb-3 p-3 rounded-xl bg-amber-50/80 border border-amber-200/60 dark:bg-amber-900/20 dark:border-amber-700/50">
        <div class="flex items-center gap-2">
            <span class="text-amber-500 text-lg">⚠️</span>
            <span class="text-xs text-amber-700 dark:text-amber-300">Contexte non défini</span>
        </div>
    </div>
@endif
</div>
