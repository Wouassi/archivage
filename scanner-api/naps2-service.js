/**
 * Service local NAPS2 Scanner Bridge
 * Fait le pont entre l'application web (Laravel/Filament) et NAPS2 CLI
 * Écoute sur http://localhost:7780
 *
 * INSTALLATION :
 * 1. Installer Node.js 18+
 * 2. Installer NAPS2 Portable : https://www.naps2.com/download
 * 3. Extraire NAPS2 dans C:\NAPS2\ (ou ajuster NAPS2_PATH ci-dessous)
 * 4. cd scanner-api && npm init -y && npm install express cors
 * 5. node naps2-service.js
 */

const express = require('express');
const cors = require('cors');
const { exec, execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(cors({ origin: '*' }));
app.use(express.json());

// ═══════════ CONFIGURATION ═══════════
const PORT = 7780;
const NAPS2_PATH = process.env.NAPS2_PATH || 'C:\\NAPS2\\NAPS2.Console.exe';
const OUTPUT_DIR = process.env.SCAN_OUTPUT || path.join(__dirname, '..', 'storage', 'app', 'public', 'uploads-tmp');

// Créer le dossier de sortie si nécessaire
if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// ═══════════ ROUTES ═══════════

// Health check
app.get('/api/scanner/health', (req, res) => {
    const naps2Exists = fs.existsSync(NAPS2_PATH);
    res.json({
        status: 'running',
        naps2_installed: naps2Exists,
        naps2_path: NAPS2_PATH,
        output_dir: OUTPUT_DIR,
        timestamp: new Date().toISOString()
    });
});

// Lister les scanners détectés
app.get('/api/scanner/devices', (req, res) => {
    if (!fs.existsSync(NAPS2_PATH)) {
        return res.status(500).json({ error: 'NAPS2 non trouvé', path: NAPS2_PATH });
    }

    const listFile = path.join(OUTPUT_DIR, `scanners_${Date.now()}.txt`);
    const cmd = `"${NAPS2_PATH}" --listdevices --driver wia --output "${listFile}"`;

    exec(cmd, { timeout: 15000 }, (error, stdout, stderr) => {
        let devices = [];

        // Parser stdout pour extraire les noms de scanners
        const output = (stdout || '') + (stderr || '');
        const lines = output.split('\n').filter(l => l.trim() && !l.includes('NAPS2'));

        if (lines.length > 0) {
            devices = lines.map((name, idx) => ({
                id: idx,
                name: name.trim(),
                driver: 'wia'
            }));
        }

        // Essayer aussi TWAIN si WIA ne donne rien
        if (devices.length === 0) {
            const cmdTwain = `"${NAPS2_PATH}" --listdevices --driver twain`;
            try {
                const twainOut = execSync(cmdTwain, { timeout: 15000 }).toString();
                const twainLines = twainOut.split('\n').filter(l => l.trim() && !l.includes('NAPS2'));
                devices = twainLines.map((name, idx) => ({
                    id: idx,
                    name: name.trim(),
                    driver: 'twain'
                }));
            } catch (e) { /* silencieux */ }
        }

        // Cleanup
        try { if (fs.existsSync(listFile)) fs.unlinkSync(listFile); } catch (e) {}

        res.json({ devices, count: devices.length });
    });
});

// Lancer un scan
app.post('/api/scanner/scan', (req, res) => {
    if (!fs.existsSync(NAPS2_PATH)) {
        return res.status(500).json({ error: 'NAPS2 non trouvé' });
    }

    const {
        device = '',
        driver = 'wia',
        dpi = 200,
        source = 'auto',     // auto | flatbed | feeder
        color = 'color',     // color | gray | bw
        pages = 0,           // 0 = toutes les pages du chargeur
        format = 'pdf'
    } = req.body;

    const timestamp = Date.now();
    const filename = `scan_${timestamp}.${format}`;
    const outputPath = path.join(OUTPUT_DIR, filename);

    // Construire la commande NAPS2
    let cmd = `"${NAPS2_PATH}"`;
    cmd += ` -o "${outputPath}"`;
    cmd += ` --driver ${driver}`;

    if (device) cmd += ` --device "${device}"`;
    if (dpi) cmd += ` --dpi ${dpi}`;

    // Source du scan
    if (source === 'feeder') cmd += ' --source feeder';
    else if (source === 'flatbed') cmd += ' --source glass';
    else cmd += ' --source auto';

    // Mode couleur
    if (color === 'gray') cmd += ' --bitdepth gray';
    else if (color === 'bw') cmd += ' --bitdepth bw';

    // Nombre de pages (0 = toutes)
    if (pages > 0) cmd += ` --numpages ${pages}`;

    // Force pour écraser
    cmd += ' --force';

    console.log(`[SCAN] Commande: ${cmd}`);

    exec(cmd, { timeout: 120000 }, (error, stdout, stderr) => {
        if (error && !fs.existsSync(outputPath)) {
            console.error(`[SCAN] Erreur:`, error.message);
            return res.status(500).json({
                error: 'Échec du scan',
                details: error.message,
                stderr: stderr || ''
            });
        }

        if (fs.existsSync(outputPath)) {
            const stats = fs.statSync(outputPath);
            const relativePath = `uploads-tmp/${filename}`;

            console.log(`[SCAN] OK: ${filename} (${(stats.size / 1024).toFixed(1)} Ko)`);

            res.json({
                success: true,
                file: {
                    name: filename,
                    path: relativePath,
                    absolute_path: outputPath,
                    size: stats.size,
                    size_human: `${(stats.size / 1024).toFixed(1)} Ko`
                }
            });
        } else {
            res.status(500).json({ error: 'Fichier non créé après le scan' });
        }
    });
});

// Scan rapide (profil par défaut)
app.get('/api/scanner/quick-scan', (req, res) => {
    const timestamp = Date.now();
    const filename = `scan_${timestamp}.pdf`;
    const outputPath = path.join(OUTPUT_DIR, filename);

    const cmd = `"${NAPS2_PATH}" -o "${outputPath}" --driver wia --source auto --force`;

    exec(cmd, { timeout: 120000 }, (error, stdout, stderr) => {
        if (fs.existsSync(outputPath)) {
            const stats = fs.statSync(outputPath);
            res.json({
                success: true,
                file: {
                    name: filename,
                    path: `uploads-tmp/${filename}`,
                    size: stats.size,
                    size_human: `${(stats.size / 1024).toFixed(1)} Ko`
                }
            });
        } else {
            res.status(500).json({ error: error?.message || 'Scan échoué' });
        }
    });
});

// ═══════════ DÉMARRAGE ═══════════
app.listen(PORT, () => {
    console.log(`\n═══════════════════════════════════════════`);
    console.log(`  🖨️  NAPS2 Scanner Bridge — Port ${PORT}`);
    console.log(`  📂 NAPS2: ${NAPS2_PATH}`);
    console.log(`  💾 Output: ${OUTPUT_DIR}`);
    console.log(`  ✅ API prête: http://localhost:${PORT}`);
    console.log(`═══════════════════════════════════════════\n`);
});
