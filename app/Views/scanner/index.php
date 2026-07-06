<section class="scanner-page">
<div class="scanner-shell">
<div class="scanner-header">
<span class="badge">Protected Scanner</span>

<h1>Ticket Scanner</h1>

<p class="muted">
Logged in as <strong><?= htmlspecialchars($user->full_name) ?></strong>.
Only admin and scanner users can validate tickets.
</p>
</div>

<div class="scanner-layout">
<div class="scanner-card">
<h2>Scan QR Code</h2>

<p class="muted">
Point the camera at a Royal Splash ticket QR code.
</p>

<div id="scannerReader"></div>

<div class="scanner-actions">
<button type="button" class="btn primary" id="startScannerBtn">
Start Camera
</button>

<button type="button" class="btn gold" id="stopScannerBtn" disabled>
Stop Camera
</button>
</div>

<div class="manual-scan-box">
<label>
Manual Token / Ticket Link
<input
type="text"
id="manualCodeInput"
placeholder="Paste ticket link or token"
>
</label>

<button type="button" class="btn primary full-width" id="manualScanBtn">
Validate Manually
</button>
</div>
</div>

<div class="scanner-result-card" id="scanResultCard">
<span class="result-status neutral">Waiting</span>

<h2>Waiting for scan</h2>

<p class="muted">
Scan result will appear here.
</p>
</div>
</div>
</div>
</section>

<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const validateUrl = "<?= $appUrl ?>/scanner/validate";

const resultCard = document.getElementById('scanResultCard');
const startBtn = document.getElementById('startScannerBtn');
const stopBtn = document.getElementById('stopScannerBtn');
const manualBtn = document.getElementById('manualScanBtn');
const manualInput = document.getElementById('manualCodeInput');

let scanner = null;
let scannerRunning = false;
let lastScannedCode = '';
let lastScannedAt = 0;
let isProcessingScan = false;

function renderResult(response) {
    const ticket = response.ticket || null;

    let statusClass = 'neutral';

    if (response.status === 'success') {
        statusClass = 'success';
} else if (response.status === 'warning') {
statusClass = 'warning';
} else if (response.status === 'error') {
statusClass = 'error';
}

let html = `
<span class="result-status ${statusClass}">
${response.result_type ? response.result_type.toUpperCase() : response.status.toUpperCase()}
</span>

<h2>${escapeHtml(response.message)}</h2>
`;

if (ticket) {
    html += `
    <div class="scan-ticket-details">
    <div>
    <span>Ticket Number</span>
    <strong>${escapeHtml(ticket.ticket_number)}</strong>
    </div>

    <div>
    <span>Ticket Type</span>
    <strong>${escapeHtml(ticket.ticket_name)}</strong>
    </div>

    <div>
    <span>Buyer</span>
    <strong>${escapeHtml(ticket.buyer_name)}</strong>
    </div>

    <div>
    <span>Event</span>
    <strong>${escapeHtml(ticket.event_title)}</strong>
    </div>

    <div>
    <span>Date</span>
    <strong>${escapeHtml(ticket.event_date)} ${ticket.start_time ? '· ' + escapeHtml(ticket.start_time) : ''}</strong>
    </div>

    <div>
    <span>Venue</span>
    <strong>${escapeHtml(ticket.venue_name)}, ${escapeHtml(ticket.city)}</strong>
    </div>
    `;

    if (ticket.scanned_at) {
        html += `
        <div>
        <span>Scanned At</span>
        <strong>${escapeHtml(ticket.scanned_at)}</strong>
        </div>
        `;
    }

if (ticket.scanned_by_name) {
    html += `
    <div>
    <span>Scanned By</span>
    <strong>${escapeHtml(ticket.scanned_by_name)}</strong>
    </div>
    `;
}

html += `</div>`;
}

resultCard.innerHTML = html;
}

async function showScanAlert(response) {
    const ticket = response.ticket || null;

    const alertOptions = {
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false
    };

if (response.status === 'success') {
    if (navigator.vibrate) {
        navigator.vibrate(180);
    }

await Swal.fire({
        ...alertOptions,
        icon: 'success',
        title: 'Entry Allowed',
        html: `
        <strong>${escapeHtml(ticket?.ticket_name || 'Ticket')}</strong><br>
        ${escapeHtml(ticket?.buyer_name || '')}<br>
        <small>${escapeHtml(ticket?.ticket_number || '')}</small>
        `,
        confirmButtonText: 'Scan Next'
});

return;
}

if (response.result_type === 'used') {
    if (navigator.vibrate) {
        navigator.vibrate([250, 120, 250]);
    }

await Swal.fire({
        ...alertOptions,
        icon: 'warning',
        title: 'Ticket Already Used',
        html: `
        <strong>${escapeHtml(ticket?.ticket_name || 'Ticket')}</strong><br>
        ${escapeHtml(ticket?.buyer_name || '')}<br>
        <small>${escapeHtml(ticket?.ticket_number || '')}</small>
        ${ticket?.scanned_at ? `<br><br><strong>Scanned At:</strong> ${escapeHtml(ticket.scanned_at)}` : ''}
        ${ticket?.scanned_by_name ? `<br><strong>Scanned By:</strong> ${escapeHtml(ticket.scanned_by_name)}` : ''}
        `,
        confirmButtonText: 'OK'
});

return;
}

if (navigator.vibrate) {
    navigator.vibrate([300, 120, 300]);
}

await Swal.fire({
        ...alertOptions,
        icon: 'error',
        title: 'Ticket Not Accepted',
        html: `
        ${escapeHtml(response.message || 'This ticket cannot be accepted.')}
        ${ticket?.ticket_number ? `<br><br><small>${escapeHtml(ticket.ticket_number)}</small>` : ''}
        `,
        confirmButtonText: 'OK'
});
}

async function validateCode(code) {
    code = (code || '').trim();

    if (!code) {
        const emptyResponse = {
            status: 'error',
            result_type: 'empty',
            message: 'No QR code or token provided.'
        };

    renderResult(emptyResponse);
    await showScanAlert(emptyResponse);

    return;
}

const now = Date.now();

if (code === lastScannedCode && (now - lastScannedAt) < 3500) {
    return;
}

if (isProcessingScan) {
    return;
}

isProcessingScan = true;
lastScannedCode = code;
lastScannedAt = now;

try {
    const formData = new FormData();
    formData.append('code', code);

    const response = await fetch(validateUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
    });

const data = await response.json();

renderResult(data);
await showScanAlert(data);
} catch (error) {
const errorResponse = {
    status: 'error',
    result_type: 'network',
    message: 'Could not validate ticket. Please check connection.'
};

renderResult(errorResponse);
await showScanAlert(errorResponse);
} finally {
isProcessingScan = false;
}
}

async function startScanner() {
    if (scannerRunning) {
        return;
    }

if (!scanner) {
    scanner = new Html5Qrcode("scannerReader");
}

try {
    await scanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
    function(decodedText) {
        validateCode(decodedText);
    },
function(errorMessage) {
    // Ignore scan frame errors.
}
);

scannerRunning = true;
startBtn.disabled = true;
stopBtn.disabled = false;
} catch (error) {
const cameraError = {
    status: 'error',
    result_type: 'camera',
    message: 'Camera could not start. Make sure camera permission is allowed and the site is using HTTPS.'
};

renderResult(cameraError);
await showScanAlert(cameraError);
}
}

async function stopScanner() {
    if (!scanner || !scannerRunning) {
        return;
    }

await scanner.stop();

scannerRunning = false;
startBtn.disabled = false;
stopBtn.disabled = true;
}

function escapeHtml(value) {
    return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

startBtn.addEventListener('click', startScanner);
stopBtn.addEventListener('click', stopScanner);

manualBtn.addEventListener('click', function () {
        validateCode(manualInput.value);
});

manualInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            validateCode(manualInput.value);
        }
});
</script>
