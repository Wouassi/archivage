{{-- Section Scanner NAPS2 — Détection auto et scan direct --}}
<div x-data="scannerWidget()" x-init="checkService()" class="space-y-3">
    {{-- Header --}}
    <div class="p-4 rounded-xl border" :class="serviceOnline ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl" :class="serviceOnline ? 'bg-emerald-500' : 'bg-amber-500'">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-sm font-bold" :class="serviceOnline ? 'text-emerald-800' : 'text-amber-800'" x-text="serviceOnline ? '🟢 Scanner connecté' : '🟡 Service scanner non détecté'"></h4>
                    <p class="text-xs text-gray-500" x-show="serviceOnline" x-text="devices.length + ' scanner(s) détecté(s)'"></p>
                    <p class="text-xs text-amber-600" x-show="!serviceOnline">Lancez le service : <code class="bg-amber-100 px-1 rounded">cd scanner-api && node naps2-service.js</code></p>
                </div>
            </div>
            <button type="button" @click="checkService()" class="text-xs px-3 py-1.5 rounded-lg border transition hover:bg-gray-50" :class="serviceOnline ? 'border-emerald-300 text-emerald-700' : 'border-amber-300 text-amber-700'">
                🔄 Actualiser
            </button>
        </div>
    </div>

    {{-- Scanner controls --}}
    <div x-show="serviceOnline" x-transition class="p-4 bg-white rounded-xl border border-gray-200 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            {{-- Choix du scanner --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📡 Scanner</label>
                <select x-model="selectedDevice" class="w-full text-xs rounded-lg border-gray-200 focus:ring-indigo-500">
                    <template x-for="d in devices" :key="d.id">
                        <option :value="d.name" x-text="d.name"></option>
                    </template>
                    <option value="" x-show="devices.length === 0">Aucun scanner</option>
                </select>
            </div>
            {{-- DPI --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🎯 Résolution</label>
                <select x-model="dpi" class="w-full text-xs rounded-lg border-gray-200">
                    <option value="150">150 DPI (rapide)</option>
                    <option value="200" selected>200 DPI (normal)</option>
                    <option value="300">300 DPI (qualité)</option>
                    <option value="600">600 DPI (haute qualité)</option>
                </select>
            </div>
            {{-- Source --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📄 Source</label>
                <select x-model="source" class="w-full text-xs rounded-lg border-gray-200">
                    <option value="auto">Auto-détection</option>
                    <option value="flatbed">Vitre (flatbed)</option>
                    <option value="feeder">Chargeur (ADF)</option>
                </select>
            </div>
            {{-- Couleur --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🎨 Couleur</label>
                <select x-model="color" class="w-full text-xs rounded-lg border-gray-200">
                    <option value="color">Couleur</option>
                    <option value="gray">Niveaux de gris</option>
                    <option value="bw">Noir et blanc</option>
                </select>
            </div>
        </div>

        {{-- Bouton scan --}}
        <div class="flex items-center gap-3">
            <button type="button" @click="startScan()"
                    :disabled="scanning || devices.length === 0"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition disabled:opacity-50"
                    :class="scanning ? 'bg-amber-500 animate-pulse' : 'bg-indigo-600 hover:bg-indigo-700'">
                <template x-if="!scanning">
                    <span>🖨️ Numériser maintenant</span>
                </template>
                <template x-if="scanning">
                    <span>⏳ Numérisation en cours...</span>
                </template>
            </button>
            <span x-show="scannedFiles.length > 0" class="text-xs font-medium px-3 py-1 rounded-full bg-emerald-100 text-emerald-700" x-text="scannedFiles.length + ' page(s) numérisée(s)'"></span>
        </div>
    </div>

    {{-- Liste des fichiers scannés --}}
    <template x-if="scannedFiles.length > 0">
        <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 space-y-2">
            <h5 class="text-xs font-bold text-indigo-700">📎 Fichiers numérisés :</h5>
            <template x-for="(file, idx) in scannedFiles" :key="idx">
                <div class="flex items-center justify-between py-1.5 px-3 bg-white rounded-lg border border-indigo-100 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-indigo-500">📄</span>
                        <span class="font-medium" x-text="file.name"></span>
                        <span class="text-gray-400" x-text="file.size_human"></span>
                    </div>
                    <button type="button" @click="removeFile(idx)" class="text-red-400 hover:text-red-600 transition">✕</button>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function scannerWidget() {
    return {
        serviceOnline: false,
        devices: [],
        selectedDevice: '',
        dpi: '200',
        source: 'auto',
        color: 'color',
        scanning: false,
        scannedFiles: [],

        async checkService() {
            try {
                const r = await fetch('http://localhost:7780/api/scanner/health', { signal: AbortSignal.timeout(3000) });
                const data = await r.json();
                this.serviceOnline = data.status === 'running' && data.naps2_installed;
                if (this.serviceOnline) await this.loadDevices();
            } catch (e) {
                this.serviceOnline = false;
            }
        },

        async loadDevices() {
            try {
                const r = await fetch('http://localhost:7780/api/scanner/devices');
                const data = await r.json();
                this.devices = data.devices || [];
                if (this.devices.length > 0) this.selectedDevice = this.devices[0].name;
            } catch (e) {
                this.devices = [];
            }
        },

        async startScan() {
            if (this.scanning) return;
            this.scanning = true;
            try {
                const r = await fetch('http://localhost:7780/api/scanner/scan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        device: this.selectedDevice,
                        driver: this.devices.find(d => d.name === this.selectedDevice)?.driver || 'wia',
                        dpi: parseInt(this.dpi),
                        source: this.source,
                        color: this.color,
                        format: 'pdf'
                    })
                });
                const data = await r.json();
                if (data.success && data.file) {
                    this.scannedFiles.push(data.file);
                    this.syncToHiddenField();
                } else {
                    alert('Erreur de scan : ' + (data.error || 'Inconnu'));
                }
            } catch (e) {
                alert('Erreur de connexion au service scanner');
            }
            this.scanning = false;
        },

        removeFile(idx) {
            this.scannedFiles.splice(idx, 1);
            this.syncToHiddenField();
        },

        syncToHiddenField() {
            const paths = this.scannedFiles.map(f => f.path).join(',');
            const hidden = document.querySelector('input[name="fichiers_pdf_paths"]');
            if (hidden) {
                hidden.value = paths;
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    };
}
</script>
