<div id="larascan-widget" class="space-y-3">

    {{-- ═══ HEADER STATUS ═══ --}}
    <div id="larascan-status" class="p-4 rounded-xl border bg-amber-50 border-amber-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span id="larascan-icon" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-500 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                </span>
                <div>
                    <h4 id="larascan-title" class="text-sm font-bold text-amber-800">⏳ Détection du scanner...</h4>
                    <p id="larascan-subtitle" class="text-xs text-gray-500">Vérification du service Asprise en cours</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="larascanInit()"
                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                    🔄 Actualiser
                </button>
                <a href="https://asprise.com/scan/applet/download" target="_blank"
                   id="btn-install-service" style="display:none"
                   class="text-xs px-3 py-1.5 rounded-lg border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 transition">
                    ⬇️ Installer le service
                </a>
            </div>
        </div>
        <div id="larascan-windows-help" style="display:none"
             class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-yellow-800">
            ⚠️ <strong>Service Asprise non détecté.</strong>
            Sur Windows, le scanner nécessite le <strong>Asprise Scanner Service</strong> installé et démarré.<br>
            <span class="text-gray-600">1. Cliquez "Installer le service" → 2. Lancez "Asprise Scanner Service" → 3. Cliquez Actualiser</span>
        </div>
    </div>

    {{-- ═══ CONTRÔLES DE SCAN ═══ --}}
    <div id="larascan-controls" class="p-4 bg-white rounded-xl border border-gray-200 space-y-3" style="display:none">
        <div class="grid grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📡 Scanner</label>
                <select id="larascan-device" class="w-full text-xs rounded-lg border-gray-200 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Auto-détection</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🎯 Résolution</label>
                <select id="larascan-dpi" class="w-full text-xs rounded-lg border-gray-200">
                    <option value="150">150 DPI</option>
                    <option value="200" selected>200 DPI</option>
                    <option value="300">300 DPI</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📄 Source</label>
                <select id="larascan-source" class="w-full text-xs rounded-lg border-gray-200">
                    <option value="auto">Auto-détection</option>
                    <option value="feeder">Chargeur ADF</option>
                    <option value="flatbed">Vitre (flatbed)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🎨 Couleur</label>
                <select id="larascan-color" class="w-full text-xs rounded-lg border-gray-200">
                    <option value="TWPT_RGB">Couleur</option>
                    <option value="TWPT_GRAY">Niveaux de gris</option>
                    <option value="TWPT_BW">Noir et blanc</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <button type="button" id="btn-scan" onclick="larascanScan()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition disabled:opacity-50">
                🖨️ Numériser
            </button>
            <button type="button" onclick="larascanScanAdf()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-xl hover:bg-indigo-100 border border-indigo-200 transition">
                📑 Toutes les pages (ADF)
            </button>
            <span id="larascan-count" class="text-xs font-medium px-3 py-1 rounded-full bg-gray-100 text-gray-600" style="display:none">0 page(s)</span>
        </div>
    </div>

    {{-- ═══ APERÇU MINIATURES ═══ --}}
    <div id="larascan-preview" class="hidden p-3 bg-indigo-50 rounded-xl border border-indigo-200">
        <div class="flex items-center justify-between mb-2">
            <h5 class="text-xs font-bold text-indigo-700">📎 Pages numérisées :</h5>
            <button type="button" onclick="larascanClearAll()" class="text-xs text-red-500 hover:text-red-700 transition">🗑️ Tout supprimer</button>
        </div>
        <div id="larascan-thumbs" class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2"></div>
    </div>

    {{-- ═══ LISTE FICHIERS SERVEUR ═══ --}}
    <div id="larascan-files-list" class="hidden space-y-1"></div>

</div>

@push('scripts')
<script>
(function() {
    'use strict';

    var scannedImages   = [];
    var uploadedFiles   = [];
    var UPLOAD_URL      = @json(route('scan.upload'));
    var CSRF_TOKEN      = @json(csrf_token());
    var scannerJsUrl    = @json(config('larascan.scanner_js_url', 'https://cdn.asprise.com/scannerjs/scanner.js'));
    var scannerJsLoaded = false;
    var ASPRISE_PORT    = 1234;

    // ═══ BUG 2 FIX : Trouver le champ hidden généré par Filament ═══
    // Filament génère wire:model et non name="...", on cherche les deux.
    function findHiddenField() {
        var selectors = [
            '[wire\\:model="data.fichiers_pdf_paths"]',
            '[wire\\:model\\.defer="data.fichiers_pdf_paths"]',
            '[wire\\:model\\.live="data.fichiers_pdf_paths"]',
            'input[name="fichiers_pdf_paths"]',
            'input[id*="fichiers_pdf_paths"]',
        ];
        for (var i = 0; i < selectors.length; i++) {
            var el = document.querySelector(selectors[i]);
            if (el) return el;
        }
        return null;
    }

    // ═══ BUG 1 FIX : Vérification réelle du service Asprise Windows ═══
    // Le service tourne sur 127.0.0.1:1234. On teste via une Image (pas de CORS).
    function checkAspriseService(callback) {
        var done = false;
        var timer = setTimeout(function() {
            if (!done) { done = true; callback(false); }
        }, 3000);

        var img = new Image();
        img.onload = function() {
            if (!done) { done = true; clearTimeout(timer); callback(true); }
        };
        img.onerror = function() {
            if (!done) {
                done = true;
                clearTimeout(timer);
                // onerror peut signifier "serveur répond mais image 404" = service présent,
                // ou "connexion refusée" = service absent.
                // On délègue à scanner.getScanners pour trancher.
                if (typeof scanner !== 'undefined') {
                    try {
                        var t2 = setTimeout(function() { callback(false); }, 2000);
                        scanner.getScanners(function(list) {
                            clearTimeout(t2);
                            callback(Array.isArray(list));
                        });
                    } catch(e) { callback(false); }
                } else {
                    callback(false);
                }
            }
        };
        img.src = 'http://127.0.0.1:' + ASPRISE_PORT + '/favicon.ico?_t=' + Date.now();
    }

    // ─── Charger Scanner.js dynamiquement ───
    function loadScannerJs() {
        if (typeof scanner !== 'undefined') {
            scannerJsLoaded = true;
            verifyAndInit();
            return;
        }
        var s = document.createElement('script');
        s.src = scannerJsUrl;
        s.onload = function() {
            scannerJsLoaded = true;
            setTimeout(verifyAndInit, 1000);
        };
        s.onerror = function() {
            setStatus('error', '❌ Scanner.js non chargé', 'Impossible de charger depuis : ' + scannerJsUrl);
        };
        document.head.appendChild(s);
    }

    function verifyAndInit() {
        if (typeof scanner === 'undefined') {
            setStatus('error', '❌ Scanner.js non disponible', 'Le module de scan ne peut pas se charger');
            return;
        }
        setStatus('info', '🔍 Connexion au service scanner...', 'Test sur le port ' + ASPRISE_PORT);
        checkAspriseService(function(serviceOk) {
            if (!serviceOk) {
                setStatus('error',
                    '❌ Service Asprise non démarré',
                    'Le service scanner Windows n\'est pas actif sur le port ' + ASPRISE_PORT);
                document.getElementById('larascan-windows-help').style.display = '';
                document.getElementById('btn-install-service').style.display = '';
                return;
            }
            onServiceReady();
        });
    }

    function onServiceReady() {
        document.getElementById('larascan-windows-help').style.display = 'none';
        document.getElementById('btn-install-service').style.display = 'none';
        try {
            scanner.getScanners(function(scanners) {
                var sel = document.getElementById('larascan-device');
                while (sel.options.length > 1) sel.remove(1);
                if (scanners && scanners.length > 0) {
                    scanners.forEach(function(sc, i) {
                        var opt = document.createElement('option');
                        opt.value       = sc.name || String(i);
                        opt.textContent = sc.name || ('Scanner ' + (i + 1));
                        sel.appendChild(opt);
                    });
                    setStatus('success',
                        '🟢 ' + scanners.length + ' scanner(s) détecté(s)',
                        'Sélectionnez un scanner et cliquez Numériser');
                } else {
                    setStatus('warning', '⚠️ Aucun scanner trouvé', 'Branchez votre scanner puis cliquez Actualiser');
                }
                document.getElementById('larascan-controls').style.display = '';
            });
        } catch(e) {
            setStatus('success', '🟢 Service scanner actif', 'Prêt à numériser');
            document.getElementById('larascan-controls').style.display = '';
        }
    }

    window.larascanInit = function() {
        document.getElementById('larascan-windows-help').style.display = 'none';
        document.getElementById('btn-install-service').style.display = 'none';
        if (!scannerJsLoaded) { loadScannerJs(); } else { verifyAndInit(); }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(loadScannerJs, 500); });
    } else {
        setTimeout(loadScannerJs, 500);
    }

    // ─── SCAN SIMPLE ───
    window.larascanScan = function() {
        if (typeof scanner === 'undefined') return;
        var dpi    = document.getElementById('larascan-dpi').value;
        var color  = document.getElementById('larascan-color').value;
        var source = document.getElementById('larascan-source').value;
        var device = document.getElementById('larascan-device').value;
        var req = {
            "use_asprise_dialog": true,
            "show_scanner_ui": false,
            "twain_cap_setting": {
                "ICAP_PIXELTYPE": color,
                "ICAP_XRESOLUTION": parseInt(dpi),
                "ICAP_YRESOLUTION": parseInt(dpi),
                "ICAP_SUPPORTEDSIZES": "TWSS_A4"
            },
            "output_settings": [{ "type": "return-base64", "format": "pdf" }]
        };
        if (device) req["scanner"] = device;
        if (source === 'feeder') {
            req.twain_cap_setting["CAP_FEEDERENABLED"] = true;
            req.twain_cap_setting["CAP_AUTOFEED"] = true;
        } else if (source === 'flatbed') {
            req.twain_cap_setting["CAP_FEEDERENABLED"] = false;
        }
        setBtnScanning(true);
        scanner.scan(handleScanResult, req);
    };

    // ─── SCAN ADF MULTI-PAGES ───
    window.larascanScanAdf = function() {
        if (typeof scanner === 'undefined') return;
        var dpi    = document.getElementById('larascan-dpi').value;
        var color  = document.getElementById('larascan-color').value;
        var device = document.getElementById('larascan-device').value;
        var req = {
            "use_asprise_dialog": true,
            "show_scanner_ui": false,
            "twain_cap_setting": {
                "ICAP_PIXELTYPE": color,
                "ICAP_XRESOLUTION": parseInt(dpi),
                "ICAP_YRESOLUTION": parseInt(dpi),
                "ICAP_SUPPORTEDSIZES": "TWSS_A4",
                "CAP_FEEDERENABLED": true,
                "CAP_AUTOFEED": true,
                "CAP_DUPLEXENABLED": false
            },
            "output_settings": [{ "type": "return-base64", "format": "pdf" }]
        };
        if (device) req["scanner"] = device;
        setBtnScanning(true);
        scanner.scan(handleScanResult, req);
    };

    // ─── TRAITEMENT RÉSULTAT ───
    function handleScanResult(successful, mesg, response) {
        setBtnScanning(false);
        if (!successful) { setStatus('error', '❌ Erreur de scan', mesg || 'Erreur inconnue'); return; }
        if (mesg && mesg.toLowerCase().indexOf('user cancel') >= 0) { setStatus('warning', '⚠️ Annulé', ''); return; }
        var images = scanner.getScannedImages(response, true, false);
        if (!images || images.length === 0) { setStatus('warning', '⚠️ Aucune page reçue', 'Vérifiez que le document est bien placé'); return; }
        for (var i = 0; i < images.length; i++) { scannedImages.push(images[i]); }
        setStatus('info', '⏳ Upload en cours...', images.length + ' page(s) envoyée(s) au serveur');
        uploadScannedImages(images);
        updatePreview();
    }

    // ─── UPLOAD AU SERVEUR ───
    function uploadScannedImages(images) {
        var b64 = [];
        for (var i = 0; i < images.length; i++) { b64.push(images[i].src); }
        fetch(UPLOAD_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ images_base64: b64 })
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status + ' — ' + r.statusText);
            return r.json();
        })
        .then(function(data) {
            if (data.success && data.files && data.files.length > 0) {
                data.files.forEach(function(f) { uploadedFiles.push(f); });
                syncHiddenField();
                updateFilesList();
                setStatus('success', '✅ ' + scannedImages.length + ' page(s) prête(s)', 'Vous pouvez soumettre le formulaire');
            } else {
                setStatus('warning', '⚠️ Réponse inattendue du serveur', JSON.stringify(data).substring(0, 120));
                console.error('[Larascan] Réponse serveur:', data);
            }
        })
        .catch(function(err) {
            setStatus('error', '❌ Échec upload', err.message);
            console.error('[Larascan] Upload error:', err);
        });
    }

    function updatePreview() {
        var c = document.getElementById('larascan-thumbs');
        var w = document.getElementById('larascan-preview');
        if (scannedImages.length === 0) { w.classList.add('hidden'); return; }
        w.classList.remove('hidden');
        c.innerHTML = '';
        scannedImages.forEach(function(img, idx) {
            var d = document.createElement('div');
            d.className = 'relative group';
            d.innerHTML =
                '<div class="aspect-[3/4] rounded-lg overflow-hidden border-2 border-indigo-200 bg-white shadow-sm">'
                + '<img src="' + img.src + '" class="w-full h-full object-cover" alt="Page ' + (idx+1) + '"/></div>'
                + '<span class="absolute top-1 left-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold bg-indigo-600 text-white rounded-full">' + (idx+1) + '</span>'
                + '<button type="button" onclick="larascanRemove(' + idx + ')" class="absolute top-1 right-1 w-5 h-5 text-[10px] bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition">✕</button>';
            c.appendChild(d);
        });
        var counter = document.getElementById('larascan-count');
        counter.textContent = scannedImages.length + ' page(s)';
        counter.style.display = '';
    }

    function updateFilesList() {
        var c = document.getElementById('larascan-files-list');
        if (uploadedFiles.length === 0) { c.classList.add('hidden'); return; }
        c.classList.remove('hidden');
        c.innerHTML = '<h5 class="text-xs font-bold text-gray-600 mb-1">📁 Fichiers envoyés au serveur :</h5>';
        uploadedFiles.forEach(function(f) {
            var d = document.createElement('div');
            d.className = 'flex items-center gap-2 py-1 px-3 bg-white rounded-lg border border-gray-100 text-xs';
            d.innerHTML = '<span class="text-emerald-500">✓</span>'
                + '<span class="font-medium text-gray-700">' + (f.name || f.path) + '</span>'
                + '<span class="text-gray-400 ml-auto">' + (f.size_human || '') + '</span>';
            c.appendChild(d);
        });
    }

    window.larascanRemove = function(idx) {
        scannedImages.splice(idx, 1);
        if (uploadedFiles[idx]) uploadedFiles.splice(idx, 1);
        syncHiddenField(); updatePreview(); updateFilesList();
    };

    window.larascanClearAll = function() {
        scannedImages = []; uploadedFiles = [];
        syncHiddenField(); updatePreview(); updateFilesList();
        document.getElementById('larascan-count').style.display = 'none';
        fetch('/scan/clear', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' } });
    };

    // ═══ BUG 2 FIX : syncHiddenField robuste pour Filament/Livewire ═══
    function syncHiddenField() {
        var paths  = uploadedFiles.map(function(f) { return f.path; }).join(',');
        var hidden = findHiddenField();
        if (!hidden) {
            console.warn('[Larascan] Champ "fichiers_pdf_paths" introuvable. paths:', paths);
            return;
        }
        // Setter natif pour contourner les proxies Alpine/Livewire
        var desc = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
        if (desc && desc.set) { desc.set.call(hidden, paths); } else { hidden.value = paths; }
        // Événements pour Livewire/Alpine
        hidden.dispatchEvent(new Event('input',  { bubbles: true }));
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
        // Mise à jour directe Livewire v3
        if (window.Livewire) {
            try {
                var wireEl = hidden.closest('[wire\\:id]');
                if (wireEl) {
                    var comp = window.Livewire.find(wireEl.getAttribute('wire:id'));
                    if (comp && typeof comp.set === 'function') {
                        comp.set('data.fichiers_pdf_paths', paths);
                    }
                }
            } catch(e) { /* Livewire v2 : l'event input suffit */ }
        }
    }

    // ─── UI HELPERS ───
    function setStatus(type, title, subtitle) {
        var colors = {
            success: { bg: '#ecfdf5', border: '#a7f3d0', iconBg: '#10b981', text: '#065f46' },
            error:   { bg: '#fef2f2', border: '#fecaca', iconBg: '#ef4444', text: '#991b1b' },
            warning: { bg: '#fffbeb', border: '#fde68a', iconBg: '#f59e0b', text: '#92400e' },
            info:    { bg: '#eff6ff', border: '#bfdbfe', iconBg: '#3b82f6', text: '#1e40af' }
        };
        var c = colors[type] || colors.info;
        var box = document.getElementById('larascan-status');
        box.style.background = c.bg; box.style.borderColor = c.border;
        document.getElementById('larascan-icon').style.background = c.iconBg;
        document.getElementById('larascan-title').textContent = title;
        document.getElementById('larascan-title').style.color = c.text;
        document.getElementById('larascan-subtitle').textContent = subtitle || '';
    }

    function setBtnScanning(active) {
        var btn = document.getElementById('btn-scan');
        if (!btn) return;
        if (active) {
            btn.disabled = true; btn.innerHTML = '⏳ Numérisation...';
            btn.classList.add('animate-pulse', 'bg-amber-500'); btn.classList.remove('bg-indigo-600');
        } else {
            btn.disabled = false; btn.innerHTML = '🖨️ Numériser';
            btn.classList.remove('animate-pulse', 'bg-amber-500'); btn.classList.add('bg-indigo-600');
        }
    }

})();
</script>
@endpush
