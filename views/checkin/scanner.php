<!-- Check-in Scanner -->
<div class="page-header">
    <div>
        <h2><?= e($event['name']) ?></h2>
        <p class="text-muted">Escáner de check-in • <?= formatDate($event['start_date']) ?></p>
    </div>
    <a href="/admin/events/<?= $event['id'] ?>/checkin/list" class="btn btn-ghost btn-sm">📋 Ver registro</a>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="stat-card stat-card--green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['checked_in'] ?? 0) ?></div>
            <div class="stat-label">Check-ins</div>
        </div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-icon">⏳</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['registered'] ?? 0) ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>
    <div class="stat-card stat-card--orange">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['total'] ?? 0) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
</div>

<div class="checkin-grid">
    <!-- QR Scanner Panel -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📷 Escáner QR</h3>
            <div class="method-toggle">
                <button class="btn btn-sm btn-primary" onclick="setMethod('qr_code')" id="btn-qr">QR</button>
                <button class="btn btn-sm btn-ghost" onclick="setMethod('manual')" id="btn-manual">Manual</button>
            </div>
        </div>
        <div class="card-body">
            <!-- QR Input Mode -->
            <div id="qr-mode">
                <p class="text-muted text-small mb-3">Enfoca el escáner en el código QR del ticket del asistente, o escribe el código manualmente.</p>
                <form id="checkin-form" action="/admin/events/<?= $event['id'] ?>/checkin" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="method" id="method-input" value="qr_code">
                    <div class="form-group">
                        <label class="form-label">Código QR / Identificador</label>
                        <input
                            type="text"
                            id="checkin-code"
                            name="code"
                            class="form-control"
                            placeholder="Escanea el QR o escribe el código..."
                            autofocus
                            autocomplete="off"
                            style="font-family:monospace;font-size:16px;letter-spacing:.05em;"
                        >
                    </div>
                    <button type="submit" class="btn btn-success btn-full mt-3" id="checkin-btn">
                        ✅ Registrar Check-in
                    </button>
                </form>
            </div>

            <!-- Manual Mode -->
            <div id="manual-mode" style="display:none;">
                <p class="text-muted text-small mb-3">Busca al asistente por correo electrónico para hacer check-in manual.</p>
                <div class="form-group">
                    <label class="form-label">Email del asistente</label>
                    <input
                        type="email"
                        id="manual-email"
                        class="form-control"
                        placeholder="asistente@ejemplo.com"
                        autocomplete="off"
                    >
                </div>
                <button onclick="doManualCheckin(<?= $event['id'] ?>)" class="btn btn-primary btn-full mt-3" id="manual-btn">
                    🔍 Buscar y Hacer Check-in
                </button>
            </div>
        </div>
    </div>

    <!-- Result Panel -->
    <div class="card" id="result-panel">
        <div class="card-header">
            <h3 class="card-title">📋 Último resultado</h3>
        </div>
        <div class="card-body" id="result-body">
            <div class="empty-state" style="padding: 32px;">
                <div class="empty-icon">📟</div>
                <p>El resultado del check-in aparecerá aquí.</p>
            </div>
        </div>
    </div>
</div>

<style>
.checkin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 768px) { .checkin-grid { grid-template-columns: 1fr; } }
.method-toggle { display: flex; gap: 4px; }
.result-success { border: 2px solid #10B981; }
.result-warning { border: 2px solid #F59E0B; }
.result-error   { border: 2px solid #EF4444; }
.result-attendee { background: #F8FAFC; border-radius: 10px; padding: 16px; margin-top: 12px; }
.result-attendee h4 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.result-attendee p  { font-size: 13px; color: #64748B; }
.result-message { font-size: 16px; font-weight: 600; padding: 12px; border-radius: 8px; text-align: center; }
.result-message.success { background: #D1FAE5; color: #065F46; }
.result-message.warning { background: #FEF3C7; color: #92400E; }
.result-message.error   { background: #FEE2E2; color: #991B1B; }
</style>

<script>
const eventId   = <?= (int)$event['id'] ?>;
const csrfToken = '<?= csrfToken() ?>';

function setMethod(method) {
    document.getElementById('method-input').value = method;
    document.getElementById('qr-mode').style.display    = method === 'qr_code' ? 'block' : 'none';
    document.getElementById('manual-mode').style.display = method === 'manual'  ? 'block' : 'none';
    document.getElementById('btn-qr').className     = 'btn btn-sm ' + (method === 'qr_code' ? 'btn-primary' : 'btn-ghost');
    document.getElementById('btn-manual').className = 'btn btn-sm ' + (method === 'manual'  ? 'btn-primary' : 'btn-ghost');
    if (method === 'qr_code') document.getElementById('checkin-code').focus();
    else document.getElementById('manual-email').focus();
}

// Auto-submit al escribir código completo (32 chars)
document.getElementById('checkin-code').addEventListener('input', function() {
    if (this.value.length >= 32) {
        setTimeout(() => document.getElementById('checkin-form').dispatchEvent(new Event('submit')), 100);
    }
});

document.getElementById('checkin-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const code = document.getElementById('checkin-code').value.trim();
    if (!code) return;

    document.getElementById('checkin-btn').disabled = true;
    document.getElementById('checkin-btn').textContent = 'Procesando...';

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('code', code);
    formData.append('method', document.getElementById('method-input').value);

    try {
        const res = await fetch(`/admin/events/${eventId}/checkin`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await res.json();
        showResult(data);
    } catch (err) {
        showResult({ success: false, message: '❌ Error de conexión. Intenta de nuevo.' });
    }

    document.getElementById('checkin-btn').disabled = false;
    document.getElementById('checkin-btn').textContent = '✅ Registrar Check-in';
    document.getElementById('checkin-code').value = '';
    document.getElementById('checkin-code').focus();
});

async function doManualCheckin(eid) {
    const email = document.getElementById('manual-email').value.trim();
    if (!email) return;

    document.getElementById('manual-btn').disabled = true;
    document.getElementById('manual-btn').textContent = 'Buscando...';

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('email', email);
    formData.append('event_id', eid);

    try {
        const res = await fetch('/checkin/manual', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await res.json();
        showResult(data);
    } catch (err) {
        showResult({ success: false, message: '❌ Error de conexión.' });
    }

    document.getElementById('manual-btn').disabled = false;
    document.getElementById('manual-btn').textContent = '🔍 Buscar y Hacer Check-in';
    document.getElementById('manual-email').value = '';
}

function showResult(data) {
    const panel  = document.getElementById('result-panel');
    const body   = document.getElementById('result-body');
    const type   = data.success ? 'success' : (data.already_in ? 'warning' : 'error');

    panel.className = `card result-${type}`;

    let html = `<div class="result-message ${type}">${data.message || '—'}</div>`;

    if (data.attendee) {
        const a = data.attendee;
        html += `<div class="result-attendee">
            <h4>${escHtml(a.full_name || '')}</h4>
            <p>📧 ${escHtml(a.email || '')}</p>
            ${a.company ? `<p>🏢 ${escHtml(a.company)}</p>` : ''}
            ${a.phone   ? `<p>📱 ${escHtml(a.phone)}</p>` : ''}
        </div>`;
    }

    body.innerHTML = html;

    // Sonido de feedback (navegadores que lo soporten)
    const audio = new AudioContext();
    const osc   = audio.createOscillator();
    const gain  = audio.createGain();
    osc.connect(gain); gain.connect(audio.destination);
    osc.type = data.success ? 'sine' : 'square';
    osc.frequency.value = data.success ? 880 : 220;
    gain.gain.value = 0.1;
    osc.start(); osc.stop(audio.currentTime + 0.15);
}

function escHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}
</script>
