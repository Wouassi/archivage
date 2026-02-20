<div>
@php
    $record = $getRecord();
    $pdfUrl = $record->pdf_url ?? null;
@endphp

@if($pdfUrl)
    <div class="pdf-embed-container mt-4">
        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-indigo-50 to-sky-50 border-b border-indigo-100">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-sm font-semibold text-indigo-700">{{ $record->pdf_name }}</span>
                <span class="text-xs text-gray-500">({{ $record->pdf_size }})</span>
            </div>
            <a href="{{ $pdfUrl }}" target="_blank" download
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-500 rounded-lg hover:bg-indigo-600 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Télécharger
            </a>
        </div>
        <iframe src="{{ $pdfUrl }}#toolbar=1&navpanes=0&scrollbar=1" title="Document PDF"></iframe>
    </div>
@else
    <div class="mt-4 p-6 text-center rounded-xl bg-gray-50 border-2 border-dashed border-gray-200">
        <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="mt-2 text-sm text-gray-500">Aucun document PDF attaché à ce dossier.</p>
    </div>
@endif
</div>
