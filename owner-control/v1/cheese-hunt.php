<?php
declare(strict_types=1);

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../api/config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.cheese_hunt.ui.v1');

$writeAvailable = getenv('NARRRFS_OWNER_CHEESE_HUNT_WRITE_ENABLED') === '1';
$reviewAvailable = getenv('NARRRFS_OWNER_CHEESE_HUNT_CLAIM_REVIEW_ENABLED') === '1';
$playerCaptureAvailable = getenv('NARRRFS_CHEESE_HUNT_PLAYER_ENABLED') === '1';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Owner Control — Cheese Hunt</title>
<style>
  :root { color-scheme: dark; font-family: system-ui, -apple-system, sans-serif; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #10161d; color: #f4f7fb; padding: 24px; line-height: 1.5; }
  main { max-width: 1120px; margin: 0 auto; }
  a { color: #f6cc65; text-decoration: none; }
  a:hover { text-decoration: underline; }
  h1, h2, h3 { margin: 0 0 10px; }
  section { background: #19222c; border: 1px solid #2e3b48; border-radius: 14px; padding: 18px; margin: 18px 0; }
  .lede, .muted { color: #b6c3cf; font-size: 0.92rem; }
  .gates { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 14px 0; }
  .pill { padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; border: 1px solid #354352; background: #121a22; }
  .pill-on { border-color: #2d6a4f; background: #163326; color: #8ee6a5; }
  .pill-off { border-color: #495057; background: #1d242c; color: #b6c3cf; }
  .status-bar { padding: 12px 14px; border-left: 3px solid #f6cc65; background: #151e27; border-radius: 8px; min-height: 1.5em; }
  .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); }
  .form-grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
  .card { background: #141c24; border: 1px solid #2b3844; border-radius: 12px; padding: 14px; }
  .card-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; }
  .badge { padding: 3px 9px; border-radius: 999px; font-size: 0.74rem; font-weight: 800; text-transform: uppercase; }
  .badge-active, .badge-approved { background: #173a27; color: #8ee6a5; }
  .badge-draft, .badge-pending { background: #22384c; color: #9cd2ff; }
  .badge-inactive, .badge-rejected { background: #3b2626; color: #ffb3ae; }
  label { display: flex; flex-direction: column; gap: 6px; font-size: 0.88rem; color: #d5dfe8; }
  .check-row { flex-direction: row; align-items: center; gap: 8px; margin-top: 24px; }
  input, select, textarea, button { font: inherit; color: #f4f7fb; background: #0e141b; border: 1px solid #3a4a59; border-radius: 8px; padding: 8px 10px; }
  input[type="checkbox"] { width: auto; padding: 0; }
  button { cursor: pointer; background: #24313f; font-weight: 700; }
  button:hover:not(:disabled) { background: #2f3f50; }
  button:disabled { opacity: 0.5; cursor: not-allowed; }
  .btn-primary { background: #3b2f16; border-color: #a88332; color: #ffe39b; }
  .btn-primary:hover:not(:disabled) { background: #4d3d1d; }
  .btn-approve { background: #183a27; border-color: #2f7d52; color: #9cf0b4; }
  .btn-reject { background: #442222; border-color: #8f3a3a; color: #ffb4ae; }
  .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; align-items: center; }
  .meta-line { margin: 4px 0; font-size: 0.86rem; color: #b6c3cf; word-break: break-word; }
  .hidden { display: none; }
</style>
</head>
<body>
<main>
  <p><a href="/owner-control/v1/index.php">← Owner Control</a> · <a href="/owner-control/v1/quest-hunt-overview.php">Quest / Hunt Overview</a></p>
  <h1>🧀 Owner Control · Cheese Hunt</h1>
  <p class="lede">Manage Cheese Hunt missions, inspect active status, and approve or reject completed claims. Every mutation requires an owner session, one-time scoped CSRF token, and idempotency key; approved Discord roles are queued in the durable role-delivery outbox.</p>

  <div class="gates" aria-label="Cheese Hunt server gates">
    <span class="pill pill-on">Read: ON</span>
    <span class="pill <?= $writeAvailable ? 'pill-on' : 'pill-off' ?>">Mission Write: <?= $writeAvailable ? 'ON' : 'OFF' ?></span>
    <span class="pill <?= $reviewAvailable ? 'pill-on' : 'pill-off' ?>">Claim Review: <?= $reviewAvailable ? 'ON' : 'OFF' ?></span>
    <span class="pill <?= $playerCaptureAvailable ? 'pill-on' : 'pill-off' ?>">Homepage Player Capture: <?= $playerCaptureAvailable ? 'ON' : 'OFF' ?></span>
    <button type="button" id="refresh_btn">Refresh workspace</button>
  </div>

  <p id="status" class="status-bar" role="status" aria-live="polite">Loading Cheese Hunt workspace…</p>

  <section aria-labelledby="claims-heading">
    <h2 id="claims-heading">Pending Claims Queue</h2>
    <p class="muted">Approving credits the configured DSPOINC reward inside the ledger transaction and queues any optional role into <code>tbl_owner_cheese_hunt_role_delivery</code>.</p>
    <div id="pending_claims" class="grid"></div>
  </section>

  <?php if ($writeAvailable): ?>
  <section aria-labelledby="create-heading">
    <h2 id="create-heading">Create Cheese Hunt Mission</h2>
    <form id="create_mission_form">
      <div class="form-grid">
        <label>
          Description (3–500 chars)
          <input id="m_description" name="description" minlength="3" maxlength="500" required placeholder="Catch 3 sneaky cheeses around Narrrfs World!">
        </label>
        <label>
          Reward ($DSPOINC)
          <input id="m_reward" name="reward_amount" type="number" min="1" max="1000000000" step="1" value="500" required>
        </label>
        <label>
          Required Clicks (1–100)
          <input id="m_clicks" name="required_clicks" type="number" min="1" max="100" step="1" value="3" required>
        </label>
        <label>
          Movement Pattern
          <select id="m_pattern" name="movement_pattern">
            <option value="random">Random Jump (random)</option>
            <option value="edge_hunter">Edge Hunter (edge_hunter)</option>
            <option value="sneaky">Sneaky (sneaky)</option>
            <option value="corner_lurker">Corner Lurker (corner_lurker)</option>
            <option value="scatter_zone">Scatter Zone (scatter_zone)</option>
            <option value="fair_shuffle">Fair Shuffle (fair_shuffle)</option>
          </select>
        </label>
        <label>
          Movement Speed
          <select id="m_speed" name="movement_speed">
            <option value="normal" selected>Normal</option>
            <option value="fast">Fast</option>
            <option value="slow">Slow</option>
          </select>
        </label>
        <label>
          Optional Discord Role Reward
          <select id="m_role_select">
            <option value="">None (DSPOINC only)</option>
            <option value="1399651053682692208">Cheese Hunter (1399651053682692208)</option>
            <option value="1332017770937847809">Alpha Caller (1332017770937847809)</option>
            <option value="1332017420591697972">Champion (1332017420591697972)</option>
            <option value="1332017969181622342">Community Member (1332017969181622342)</option>
            <option value="1332017063610548445">Corny Companion (1332017063610548445)</option>
            <option value="1332017858342944808">Engager (1332017858342944808)</option>
            <option value="1332016854390280306">Kaleido Supporter (1332016854390280306)</option>
            <option value="1332049628300054679">Moderator (1332049628300054679)</option>
            <option value="1332017533800284211">Poker OG (1332017533800284211)</option>
            <option value="1332017205667299489">Bunny Buddy (1332017205667299489)</option>
            <option value="1332017710351122482">Rumble Champ (1332017710351122482)</option>
            <option value="1356296242757369898">Server Booster (1356296242757369898)</option>
            <option value="1332016526848692345">VIP Cheese Lord (1332016526848692345)</option>
            <option value="1333347801408737323">Verified (1333347801408737323)</option>
            <option value="1332017340392538123">Weedery (1332017340392538123)</option>
            <option value="1332108350518857842">WL (1332108350518857842)</option>
            <option value="custom">Custom Discord Role ID…</option>
          </select>
        </label>
        <label id="m_role_custom_wrap" class="hidden">
          Custom Discord Role ID (17–20 digits)
          <input id="m_role_custom" inputmode="numeric" pattern="[0-9]{17,20}" placeholder="1399651053682692208" disabled>
        </label>
        <label>
          Optional Expiration (Local Time → UTC)
          <input id="m_expires_at" type="datetime-local">
        </label>
        <label class="check-row">
          <input id="m_hidden_areas" type="checkbox" checked>
          Allow hidden / full-page cheese spots
        </label>
      </div>
      <label style="margin-top:12px;">
        Winner Message (1–500 chars)
        <input id="m_winner_message" name="winner_message" minlength="1" maxlength="500" required value="Congratulations! You caught the cheese and submitted your claim for review!">
      </label>
      <p class="muted">Optional role rewards are queued safely into the durable role-delivery outbox upon claim approval (never granted directly from web endpoints). New missions start as <strong>draft</strong> until you click Activate.</p>
      <div class="actions">
        <button id="create_submit_btn" type="submit" class="btn-primary">Create Mission Draft</button>
      </div>
    </form>
  </section>
  <?php endif; ?>

  <section aria-labelledby="missions-heading">
    <h2 id="missions-heading">Missions (Latest 25)</h2>
    <p class="muted">Only one mission may be active at a time. Activating a draft or inactive mission automatically deactivates any currently active mission.</p>
    <div id="missions_list" class="grid"></div>
  </section>

  <section aria-labelledby="recent-heading">
    <h2 id="recent-heading">Recent Reviewed Claims (Latest 25)</h2>
    <div id="recent_claims" class="grid"></div>
  </section>
</main>
<script>
'use strict';

const CAN_WRITE = <?= $writeAvailable ? 'true' : 'false' ?>;
const CAN_REVIEW = <?= $reviewAvailable ? 'true' : 'false' ?>;
const statusEl = document.getElementById('status');
const pendingEl = document.getElementById('pending_claims');
const missionsEl = document.getElementById('missions_list');
const recentEl = document.getElementById('recent_claims');

let pendingMissionDraft = null;
const pendingLifecycleKeys = new Map();
const pendingReviewKeys = new Map();

function randomKey() {
  const bytes = new Uint8Array(18);
  crypto.getRandomValues(bytes);
  return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
}

function makeBadge(label, variant) {
  const span = document.createElement('span');
  span.className = 'badge badge-' + variant;
  span.textContent = label;
  return span;
}

function addMeta(parent, text) {
  const p = document.createElement('p');
  p.className = 'meta-line';
  p.textContent = text;
  parent.append(p);
}

async function fetchCsrfToken(action) {
  const response = await fetch('/api/owner-control/v1/cheese-hunt/csrf.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action })
  });
  const data = await response.json();
  if (!response.ok || !data.success || typeof data.csrf_token !== 'string') {
    throw new Error(data.error || 'csrf_unavailable');
  }
  return data.csrf_token;
}

async function postMission(body) {
  const token = await fetchCsrfToken('mission');
  const response = await fetch('/api/owner-control/v1/cheese-hunt/mission.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token
    },
    body: JSON.stringify(body)
  });
  const data = await response.json();
  if (!response.ok || !data.success) {
    throw new Error(data.error || 'mission_failed');
  }
  return data.result;
}

async function postReview(body) {
  const token = await fetchCsrfToken('review');
  const response = await fetch('/api/owner-control/v1/cheese-hunt/claim-review.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token
    },
    body: JSON.stringify(body)
  });
  const data = await response.json();
  if (!response.ok || !data.success) {
    throw new Error(data.error || 'review_failed');
  }
  return data.result;
}

function renderPendingClaims(claims) {
  pendingEl.replaceChildren();
  if (!Array.isArray(claims) || claims.length === 0) {
    addMeta(pendingEl, 'No pending Cheese Hunt claims right now.');
    return;
  }

  for (const claim of claims) {
    const card = document.createElement('article');
    card.className = 'card';

    const head = document.createElement('div');
    head.className = 'card-head';
    const title = document.createElement('strong');
    title.textContent = 'Claim #' + String(claim.claim_id) + ' · Mission #' + String(claim.mission_id);
    head.append(title, makeBadge(String(claim.status || 'pending'), 'pending'));
    card.append(head);

    const playerLabel = claim.username
      ? String(claim.username) + ' (' + String(claim.player_id) + ')'
      : String(claim.player_id);
    addMeta(card, 'Player: ' + playerLabel);
    addMeta(card, 'Mission: ' + String(claim.mission_description || ''));
    addMeta(card, 'Reward: ' + String(claim.reward_amount) + ' DSPOINC' + (claim.role_id ? ' · Outbox Role: ' + String(claim.role_id) : ''));
    addMeta(card, 'Submitted: ' + String(claim.created_at || ''));

    if (CAN_REVIEW) {
      const reasonInput = document.createElement('input');
      reasonInput.type = 'text';
      reasonInput.minLength = 3;
      reasonInput.maxLength = 256;
      reasonInput.placeholder = 'Review reason (3–256 chars)';
      reasonInput.value = 'Verified completion';
      reasonInput.style.marginTop = '8px';
      reasonInput.setAttribute('aria-label', 'Review reason for claim #' + String(claim.claim_id));
      card.append(reasonInput);

      const actions = document.createElement('div');
      actions.className = 'actions';

      const approveBtn = document.createElement('button');
      approveBtn.type = 'button';
      approveBtn.className = 'btn-approve';
      approveBtn.textContent = 'Approve (+' + String(claim.reward_amount) + ' DSPOINC)';

      const rejectBtn = document.createElement('button');
      rejectBtn.type = 'button';
      rejectBtn.className = 'btn-reject';
      rejectBtn.textContent = 'Reject';

      async function handleReview(action) {
        const reason = reasonInput.value.trim();
        if (reason.length < 3 || reason.length > 256) {
          statusEl.textContent = 'Enter a review reason between 3 and 256 characters.';
          reasonInput.focus();
          return;
        }
        const confirmText = action === 'approve'
          ? 'Approve Claim #' + claim.claim_id + ' and credit ' + claim.reward_amount + ' DSPOINC to ' + playerLabel + '?'
          : 'Reject Claim #' + claim.claim_id + ' for ' + playerLabel + '?';
        if (!confirm(confirmText)) {
          return;
        }

        const keyId = String(claim.claim_id) + ':' + action + ':' + reason;
        const idempotencyKey = pendingReviewKeys.get(keyId) || randomKey();
        pendingReviewKeys.set(keyId, idempotencyKey);

        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        statusEl.textContent = (action === 'approve' ? 'Approving' : 'Rejecting') + ' claim #' + claim.claim_id + '…';

        try {
          const result = await postReview({
            claim_id: Number(claim.claim_id),
            action,
            reason,
            idempotency_key: idempotencyKey
          });
          pendingReviewKeys.delete(keyId);
          statusEl.textContent = 'Claim #' + claim.claim_id + ' ' + (action === 'approve' ? 'approved' : 'rejected') +
            (result.role_delivery ? ' (role queued in outbox).' : '.');
          await loadOverview();
        } catch (err) {
          approveBtn.disabled = false;
          rejectBtn.disabled = false;
          if (err.message === 'claim_not_pending' || err.message === 'idempotency_key_reused') {
            pendingReviewKeys.delete(keyId);
            statusEl.textContent = 'Claim #' + claim.claim_id + ' changed state (' + err.message + '). Reloading…';
            await loadOverview();
            return;
          }
          statusEl.textContent = 'Claim #' + claim.claim_id + ' review not confirmed (' + err.message + '). Retrying unchanged reuses the same request key.';
        }
      }

      approveBtn.onclick = () => handleReview('approve');
      rejectBtn.onclick = () => handleReview('reject');
      actions.append(approveBtn, rejectBtn);
      card.append(actions);
    }

    pendingEl.append(card);
  }
}

function renderMissions(missions) {
  missionsEl.replaceChildren();
  if (!Array.isArray(missions) || missions.length === 0) {
    addMeta(missionsEl, 'No Cheese Hunt missions created yet.');
    return;
  }

  for (const m of missions) {
    const card = document.createElement('article');
    card.className = 'card';

    const head = document.createElement('div');
    head.className = 'card-head';
    const title = document.createElement('strong');
    title.textContent = 'Mission #' + String(m.mission_id);
    const status = String(m.status || 'draft');
    head.append(title, makeBadge(status, status));
    card.append(head);

    addMeta(card, String(m.description || ''));
    addMeta(
      card,
      'Reward: ' + String(m.reward_amount) + ' DSPOINC · Clicks: ' + String(m.required_clicks) +
      ' · Pattern: ' + String(m.movement_pattern) + ' (' + String(m.movement_speed) + ')'
    );
    addMeta(
      card,
      'Hidden areas: ' + (Number(m.hidden_areas) === 1 ? 'Yes' : 'No') +
      (m.role_id ? ' · Role Outbox: ' + String(m.role_id) : ' · No role') +
      (m.expires_at ? ' · Expires (UTC): ' + String(m.expires_at) : '')
    );
    addMeta(card, 'Winner message: ' + String(m.winner_message || ''));

    if (CAN_WRITE) {
      const actions = document.createElement('div');
      actions.className = 'actions';
      const action = status === 'active' ? 'deactivate' : 'activate';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = action === 'activate' ? 'btn-primary' : 'btn-reject';
      btn.textContent = action === 'activate' ? 'Activate Mission' : 'Deactivate Mission';

      btn.onclick = async () => {
        const promptText = action === 'activate'
          ? 'Activate Mission #' + m.mission_id + '? Any currently active Cheese Hunt mission will be deactivated.'
          : 'Deactivate Mission #' + m.mission_id + '?';
        if (!confirm(promptText)) {
          return;
        }

        const keyId = String(m.mission_id) + ':' + action + ':' + String(m.version);
        const idempotencyKey = pendingLifecycleKeys.get(keyId) || randomKey();
        pendingLifecycleKeys.set(keyId, idempotencyKey);

        btn.disabled = true;
        statusEl.textContent = (action === 'activate' ? 'Activating' : 'Deactivating') + ' Mission #' + m.mission_id + '…';

        try {
          await postMission({
            action,
            idempotency_key: idempotencyKey,
            mission: { mission_id: Number(m.mission_id) }
          });
          pendingLifecycleKeys.delete(keyId);
          statusEl.textContent = 'Mission #' + m.mission_id + ' ' + (action === 'activate' ? 'activated.' : 'deactivated.');
          await loadOverview();
        } catch (err) {
          btn.disabled = false;
          if (err.message === 'mission_not_activatable' || err.message === 'mission_not_active' || err.message === 'idempotency_key_reused') {
            pendingLifecycleKeys.delete(keyId);
            statusEl.textContent = 'Mission #' + m.mission_id + ' state changed (' + err.message + '). Reloading…';
            await loadOverview();
            return;
          }
          statusEl.textContent = 'Mission #' + m.mission_id + ' update not confirmed (' + err.message + '). Retrying unchanged reuses the same request key.';
        }
      };

      actions.append(btn);
      card.append(actions);
    }

    missionsEl.append(card);
  }
}

function renderRecentClaims(claims) {
  recentEl.replaceChildren();
  if (!Array.isArray(claims) || claims.length === 0) {
    addMeta(recentEl, 'No reviewed claims recorded yet.');
    return;
  }

  for (const claim of claims) {
    const card = document.createElement('article');
    card.className = 'card';

    const head = document.createElement('div');
    head.className = 'card-head';
    const title = document.createElement('strong');
    title.textContent = 'Claim #' + String(claim.claim_id) + ' · Mission #' + String(claim.mission_id);
    const status = String(claim.status || '');
    head.append(title, makeBadge(status, status));
    card.append(head);

    const playerLabel = claim.username
      ? String(claim.username) + ' (' + String(claim.player_id) + ')'
      : String(claim.player_id);
    addMeta(card, 'Player: ' + playerLabel + ' · Reward: ' + String(claim.reward_amount) + ' DSPOINC');
    addMeta(card, 'Reason: ' + String(claim.review_reason || '—'));
    addMeta(card, 'Reviewed by: ' + String(claim.reviewed_by || '—') + ' · At: ' + String(claim.reviewed_at || '—'));
    if (claim.role_id) {
      addMeta(card, 'Role Outbox (' + String(claim.role_id) + '): ' + String(claim.role_delivery_status || 'not_queued'));
    }

    recentEl.append(card);
  }
}

async function loadOverview() {
  try {
    const response = await fetch('/api/owner-control/v1/cheese-hunt/overview.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.error || 'overview_unavailable');
    }
    renderPendingClaims(payload.pending_claims || []);
    renderMissions(payload.missions || []);
    renderRecentClaims(payload.recent_claims || []);
    statusEl.textContent = 'Cheese Hunt workspace loaded (' +
      String((payload.pending_claims || []).length) + ' pending claim(s), ' +
      String((payload.missions || []).length) + ' mission(s)).';
  } catch (err) {
    pendingEl.replaceChildren();
    missionsEl.replaceChildren();
    recentEl.replaceChildren();
    statusEl.textContent = 'Cheese Hunt workspace unavailable (' + err.message + ').';
  }
}

document.getElementById('refresh_btn').addEventListener('click', () => {
  statusEl.textContent = 'Refreshing workspace…';
  loadOverview();
});

const createForm = document.getElementById('create_mission_form');
if (createForm) {
  const roleSelect = document.getElementById('m_role_select');
  const customWrap = document.getElementById('m_role_custom_wrap');
  const customInput = document.getElementById('m_role_custom');

  function syncRoleInput() {
    const isCustom = roleSelect.value === 'custom';
    customWrap.classList.toggle('hidden', !isCustom);
    customInput.disabled = !isCustom;
    if (!isCustom) {
      customInput.value = '';
    }
  }

  roleSelect.addEventListener('change', () => {
    syncRoleInput();
    pendingMissionDraft = null;
  });
  createForm.addEventListener('input', () => {
    pendingMissionDraft = null;
  });
  createForm.addEventListener('change', () => {
    pendingMissionDraft = null;
  });
  syncRoleInput();

  createForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const roleVal = roleSelect.value === 'custom' ? customInput.value.trim() : roleSelect.value.trim();
    if (roleVal !== '' && !/^\d{17,20}$/.test(roleVal)) {
      statusEl.textContent = 'Role ID must be 17–20 digits when specified.';
      return;
    }

    let expiresAtUtc = null;
    const rawExpires = document.getElementById('m_expires_at').value.trim();
    if (rawExpires !== '') {
      const parsedDate = new Date(rawExpires);
      if (Number.isNaN(parsedDate.getTime())) {
        statusEl.textContent = 'Enter a valid expiration date/time or leave blank.';
        return;
      }
      expiresAtUtc = parsedDate.toISOString().slice(0, 19).replace('T', ' ');
    }

    const missionPayload = {
      description: document.getElementById('m_description').value.trim(),
      reward_amount: Number(document.getElementById('m_reward').value),
      required_clicks: Number(document.getElementById('m_clicks').value),
      movement_pattern: document.getElementById('m_pattern').value,
      movement_speed: document.getElementById('m_speed').value,
      hidden_areas: Boolean(document.getElementById('m_hidden_areas').checked),
      winner_message: document.getElementById('m_winner_message').value.trim(),
      role_id: roleVal === '' ? null : roleVal,
      expires_at: expiresAtUtc
    };

    const fingerprint = JSON.stringify(missionPayload);
    if (!pendingMissionDraft || pendingMissionDraft.fingerprint !== fingerprint) {
      pendingMissionDraft = { fingerprint, key: randomKey() };
    }

    if (!confirm('Create Cheese Hunt draft mission (' + missionPayload.reward_amount + ' DSPOINC, ' + missionPayload.required_clicks + ' clicks)?')) {
      return;
    }

    const submitBtn = document.getElementById('create_submit_btn');
    submitBtn.disabled = true;
    statusEl.textContent = 'Creating Cheese Hunt draft mission…';

    try {
      const result = await postMission({
        action: 'create',
        idempotency_key: pendingMissionDraft.key,
        mission: missionPayload
      });
      pendingMissionDraft = null;
      statusEl.textContent = 'Created draft Mission #' + String(result.mission_id) + '. Click Activate Mission below when ready to go live.';
      await loadOverview();
    } catch (err) {
      statusEl.textContent = 'Mission creation not confirmed (' + err.message + '). Retrying unchanged reuses the same request key.';
    } finally {
      submitBtn.disabled = false;
    }
  });
}

loadOverview();
</script>
</body>
</html>
