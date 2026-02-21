<div>
    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BARRE DE STATUT + ACTUALISER --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-4 p-3 rounded-lg border
        {{ count($availableScanners) > 0 ? 'bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800' : 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950 dark:border-yellow-800' }}">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-sm font-medium">
                {{ $scannerStatus }}
            </span>

            <div class="flex items-center gap-2">
                @if($message)
                    <span class="text-sm font-medium
                        {{ str_starts_with($message, '✅') ? 'text-green-600 dark:text-green-400' : '' }}
                        {{ str_starts_with($message, '❌') ? 'text-red-600 dark:text-red-400' : '' }}
                        {{ str_starts_with($message, '⚠️') ? 'text-yellow-600 dark:text-yellow-400' : '' }}
                        {{ str_starts_with($message, '🔄') ? 'text-blue-600 dark:text-blue-400' : '' }}
                        {{ str_starts_with($message, '⏹️') ? 'text-orange-600 dark:text-orange-400' : '' }}
                        {{ str_starts_with($message, '🗑️') ? 'text-gray-600 dark:text-gray-400' : '' }}
                    ">
                        {{ $message }}
                    </span>
                @endif

                {{-- ══ BOUTON ACTUALISER ══ --}}
                {{-- type="button" OBLIGATOIRE sinon ça soumet le formulaire Filament parent --}}
                <button type="button"
                        wire:click="refreshScanners"
                        wire:loading.attr="disabled"
                        wire:target="refreshScanners"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold
                               bg-blue-100 text-blue-700 hover:bg-blue-200
                               dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800
                               transition-colors border border-blue-300 dark:border-blue-700"
                        title="Rechercher les scanners connectés">
                    <span wire:loading.remove wire:target="refreshScanners">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="refreshScanners">
                        <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="refreshScanners">Actualiser</span>
                    <span wire:loading wire:target="refreshScanners">Recherche...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- COLONNE GAUCHE : SCANNER + UPLOAD --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="space-y-4">

            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    🖨️ Scanner
                </h4>

                @if(count($availableScanners) > 0)
                    {{-- Scanner + Résolution + Couleur — sur une ligne --}}
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Scanner</label>
                            <select wire:model.live="selectedScanner"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                @foreach($availableScanners as $scanner)
                                    <option value="{{ $scanner['id'] }}">
                                        {{ $scanner['name'] ?? $scanner['id'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Résolution</label>
                            <select wire:model="resolution"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="75">75 DPI (rapide)</option>
                                <option value="100">100 DPI</option>
                                <option value="150">150 DPI (standard)</option>
                                <option value="200">200 DPI</option>
                                <option value="300">300 DPI (qualité)</option>
                                <option value="600">600 DPI (max)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Couleur</label>
                            <select wire:model="colorMode"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="Gray">Niveaux de gris</option>
                                <option value="Color">Couleur</option>
                                <option value="Lineart">Noir & Blanc</option>
                            </select>
                        </div>
                    </div>

                    {{-- ═══ BOUTONS DE SCAN ═══ --}}
                    <div class="grid grid-cols-2 gap-2">

                        {{-- Bouton SCAN SIMPLE (1 page) --}}
                        <button type="button"
                                wire:click="scanDocument"
                                wire:loading.attr="disabled"
                                wire:target="scanDocument, scanBatchAdf"
                                @if($isBatchScanning) disabled @endif
                                class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed
                                       text-white font-medium py-2.5 px-4 rounded-lg transition-colors
                                       flex items-center justify-center gap-2 text-sm">
                            <span wire:loading.remove wire:target="scanDocument">
                                🖨️ Numériser (1 page)
                            </span>
                            <span wire:loading wire:target="scanDocument" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Numérisation...
                            </span>
                        </button>

                        {{-- Bouton SCAN MULTIPLE ADF --}}
                        @if(!$isBatchScanning)
                            <button type="button"
                                    wire:click="scanBatchAdf"
                                    wire:loading.attr="disabled"
                                    wire:target="scanDocument, scanBatchAdf"
                                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed
                                           text-white font-medium py-2.5 px-4 rounded-lg transition-colors
                                           flex items-center justify-center gap-2 text-sm">
                                <span wire:loading.remove wire:target="scanBatchAdf">
                                    📚 Scan Multiple (ADF)
                                </span>
                                <span wire:loading wire:target="scanBatchAdf" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    ADF en cours...
                                </span>
                            </button>
                        @else
                            {{-- Bouton ARRÊTER --}}
                            <button type="button"
                                    wire:click="stopBatchScan"
                                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 px-4 rounded-lg
                                           transition-colors flex items-center justify-center gap-2 text-sm animate-pulse">
                                ⏹️ Arrêter ({{ $batchProgress }} pages)
                            </button>
                        @endif
                    </div>

                    {{-- Info scan ADF en cours --}}
                    @if($isBatchScanning)
                        <div class="mt-2 p-2 bg-purple-50 dark:bg-purple-950 rounded-md border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-2 text-sm text-purple-700 dark:text-purple-300">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="font-medium">
                                    Scan ADF — {{ $batchProgress }} page(s) numérisée(s)...
                                </span>
                            </div>
                            <p class="text-xs text-purple-500 dark:text-purple-400 mt-1">
                                S'arrête automatiquement quand le bac est vide.
                            </p>
                        </div>
                    @endif

                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        <strong>Numériser</strong> : 1 page &nbsp;|&nbsp;
                        <strong>Scan Multiple</strong> : tout le bac ADF
                    </p>

                @else
                    <div class="text-center py-4 text-gray-400">
                        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 7.125H5.25" />
                        </svg>
                        <p class="text-sm font-medium">Aucun scanner détecté</p>
                        <p class="text-xs mt-1">Branchez un scanner puis cliquez <strong>Actualiser</strong></p>
                    </div>
                @endif
            </div>

            {{-- Upload manuel --}}
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    📤 Upload de fichiers
                </h4>

                <div class="relative">
                    <input type="file"
                           wire:model="uploadFiles"
                           multiple
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                  file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900 dark:file:text-blue-300
                                  hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                                  cursor-pointer" />

                    <div wire:loading wire:target="uploadFiles" class="mt-2">
                        <div class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Chargement en cours...
                        </div>
                    </div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    PDF, JPG, PNG — max <strong>400 Mo</strong> par fichier.
                    Fusionnés avec les scans au clic sur « Créer ».
                </p>
            </div>

            {{-- Info fusion --}}
            @if(count($scannedDocuments) > 0 && count($uploadedDocuments) > 0)
                <div class="p-3 bg-indigo-50 dark:bg-indigo-950 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center gap-2 text-sm text-indigo-700 dark:text-indigo-300">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            <strong>{{ count($scannedDocuments) }} scan(s)</strong> +
                            <strong>{{ count($uploadedDocuments) }} upload(s)</strong>
                            → fusionnés en <strong>1 PDF</strong> au clic « Créer »
                        </span>
                    </div>
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- COLONNE DROITE : LISTE DES DOCUMENTS --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    📄 Documents ({{ $totalDocuments }})
                </h4>

                @if($totalDocuments > 0)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">
                            {{ $totalSizeFormatted }}
                        </span>
                        <button type="button"
                                wire:click="clearAll"
                                wire:confirm="Supprimer tous les documents ?"
                                class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium
                                       px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
                            🗑️ Tout supprimer
                        </button>
                    </div>
                @endif
            </div>

            @if($totalDocuments === 0)
                <div class="text-center py-8 text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <p class="mt-2 text-sm">Aucun document</p>
                    <p class="text-xs mt-1">Numérisez ou uploadez des fichiers.<br>
                        Tout sera <strong>fusionné en 1 PDF</strong> au clic sur « Créer ».</p>
                </div>
            @else
                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    @foreach($this->getAllDocuments() as $doc)
                        <div class="flex items-center justify-between p-2.5 rounded-md
                            {{ $doc['type'] === 'scanned' ? 'bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800' : 'bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800' }}">

                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="text-lg flex-shrink-0">
                                    {{ $doc['type'] === 'scanned' ? '🖨️' : '📤' }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate"
                                       title="{{ $doc['name'] }}">
                                        {{ $doc['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        @php
                                            $s = $doc['size'] ?? 0;
                                            if ($s >= 1048576) $sizeStr = round($s / 1048576, 1) . ' Mo';
                                            elseif ($s >= 1024) $sizeStr = round($s / 1024, 1) . ' Ko';
                                            else $sizeStr = $s . ' o';
                                        @endphp
                                        {{ $sizeStr }} — {{ $doc['created_at'] }}
                                    </p>
                                </div>
                            </div>

                            <button type="button"
                                    wire:click="removeDocument('{{ $doc['id'] }}')"
                                    class="flex-shrink-0 text-red-400 hover:text-red-600 dark:hover:text-red-300 p-1.5 rounded
                                           hover:bg-red-100 dark:hover:bg-red-900 transition-colors ml-2"
                                    title="Supprimer ce document">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Résumé --}}
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>🖨️ {{ count($scannedDocuments) }} numérisé(s)</span>
                        <span>📤 {{ count($uploadedDocuments) }} uploadé(s)</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ $totalDocuments }} total
                        </span>
                    </div>
                    <p class="text-xs text-center text-indigo-500 dark:text-indigo-400 mt-2 font-medium">
                        → Tout sera fusionné en 1 seul PDF au clic sur « Créer »
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
