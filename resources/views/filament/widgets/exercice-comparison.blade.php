<x-filament-widgets::widget>
    <x-filament::section heading="📊 Comparaison d'exercices" icon="heroicon-o-scale" collapsible>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Exercice A</label>
                <select wire:model.live="exercice1Id" class="w-full mt-1 rounded-lg border-gray-300 text-sm">
                    <option value="">— Choisir —</option>
                    @foreach(\App\Filament\Widgets\ExerciceComparisonWidget::getExerciceOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Exercice B</label>
                <select wire:model.live="exercice2Id" class="w-full mt-1 rounded-lg border-gray-300 text-sm">
                    <option value="">— Choisir —</option>
                    @foreach(\App\Filament\Widgets\ExerciceComparisonWidget::getExerciceOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(!empty($comparison) && isset($comparison['ex1']['annee']) && isset($comparison['ex2']['annee']))
            @php
                $ex1 = $comparison['ex1'];
                $ex2 = $comparison['ex2'];
                $variation = fn($a, $b) => $a > 0 ? round((($b - $a) / $a) * 100, 1) : ($b > 0 ? 100 : 0);
                $arrow = fn($v) => $v > 0 ? '▲' : ($v < 0 ? '▼' : '—');
                $color = fn($v) => $v > 0 ? 'text-emerald-600' : ($v < 0 ? 'text-rose-600' : 'text-gray-400');
                $fmt = fn($n) => number_format($n, 0, ',', ' ');
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left py-2 text-xs text-gray-500 uppercase">Indicateur</th>
                            <th class="text-center py-2 font-bold">{{ $ex1['annee'] }}</th>
                            <th class="text-center py-2 font-bold">{{ $ex2['annee'] }}</th>
                            <th class="text-center py-2 text-xs text-gray-500 uppercase">Variation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rows = [
                            ['Dossiers totaux', $ex1['total'], $ex2['total'], ''],
                            ['Montant total (FCFA)', $fmt($ex1['montant']), $fmt($ex2['montant']), $variation($ex1['montant'], $ex2['montant'])],
                            ['Taux archivage', $ex1['taux_archivage'].'%', $ex2['taux_archivage'].'%', $variation($ex1['taux_archivage'], $ex2['taux_archivage'])],
                            ['Dossiers sans PDF', $ex1['sans_pdf'], $ex2['sans_pdf'], ''],
                            ['Investissement (nb)', $ex1['invest_count'], $ex2['invest_count'], ''],
                            ['Investissement (FCFA)', $fmt($ex1['invest_montant']), $fmt($ex2['invest_montant']), $variation($ex1['invest_montant'], $ex2['invest_montant'])],
                            ['Fonctionnement (nb)', $ex1['fonct_count'], $ex2['fonct_count'], ''],
                            ['Fonctionnement (FCFA)', $fmt($ex1['fonct_montant']), $fmt($ex2['fonct_montant']), $variation($ex1['fonct_montant'], $ex2['fonct_montant'])],
                        ]; @endphp

                        @foreach($rows as $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 font-medium">{{ $row[0] }}</td>
                                <td class="text-center py-2">{{ $row[1] }}</td>
                                <td class="text-center py-2">{{ $row[2] }}</td>
                                <td class="text-center py-2">
                                    @if($row[3] !== '')
                                        <span class="{{ $color($row[3]) }} font-bold text-xs">
                                            {{ $arrow($row[3]) }} {{ abs($row[3]) }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-gray-400 py-6 text-sm">
                Sélectionnez deux exercices pour comparer.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
