<?php
declare(strict_types=1);

// Owner Control Quest/Hunt Overview V1: display-only aggregate operational view.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../api/config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.quest_hunt_overview.ui.v1');

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
<title>Owner Control — Quest / Hunt Overview</title>
<style>
  :root { color-scheme: dark; font-family: system-ui, sans-serif; }
  body { margin: 0; background: #111820; color: #f4f7fb; padding: 24px; }
  main { max-width: 900px; margin: auto; } a { color: #f6cc65; }
  .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
  .card { background: #1b2530; border: 1px solid #33404c; border-radius: 12px; padding: 16px; }
  .meta { color: #b8c6d2; } ul { padding-left: 1.2rem; }
</style>
</head>
<body>
<main>
  <p><a href="/owner-control/v1/index.php">← Owner Control</a></p>
  <h1>Quest / Hunt Overview</h1>
  <p class="meta">Owner-only read-only aggregate status. No player, claim, reward, configuration, or action controls are available.</p>
  <p id="status" role="status">Loading aggregate overview…</p>
  <section id="overview" class="grid" aria-label="Quest and Hunt aggregate overview"></section>
</main>
<script>
'use strict';
const status = document.getElementById('status');
const overview = document.getElementById('overview');
const expected = ['success', 'capability', 'version', 'mode', 'writes', 'quests', 'claims', 'hunt'];

function addCard(title, lines) {
  const card = document.createElement('article'); card.className = 'card';
  const heading = document.createElement('h2'); heading.textContent = title; card.append(heading);
  const list = document.createElement('ul');
  for (const line of lines) { const item = document.createElement('li'); item.textContent = line; list.append(item); }
  card.append(list); overview.append(card);
}

function valid(payload) {
  return payload && typeof payload === 'object' && Object.keys(payload).every((key) => expected.includes(key)) &&
    payload.success === true && payload.capability === 'owner_control_quest_hunt_overview' && payload.version === 1 &&
    payload.mode === 'read_only' && payload.writes === false && payload.quests && payload.claims && payload.hunt &&
    Number.isSafeInteger(payload.quests.active_count) && Array.isArray(payload.quests.active_type_counts) &&
    payload.quests.active_type_counts.every((item) => item && typeof item.type === 'string' && Number.isSafeInteger(item.count)) &&
    payload.claims.status_counts && ['pending', 'approved', 'rejected'].every((key) => Number.isSafeInteger(payload.claims.status_counts[key])) &&
    Number.isSafeInteger(payload.hunt.aggregate_click_count) && Number.isSafeInteger(payload.hunt.recent_window_click_count);
}

async function load() {
  try {
    const response = await fetch('/api/owner-control/v1/quests/overview.php', { credentials: 'same-origin', cache: 'no-store' });
    if (!response.ok) { throw new Error('unavailable'); }
    const payload = await response.json();
    if (!valid(payload)) { throw new Error('invalid'); }
    overview.replaceChildren();
    addCard('Active quests', ['Total: ' + payload.quests.active_count, ...payload.quests.active_type_counts.map((item) => item.type + ': ' + item.count)]);
    addCard('Claim status aggregates', ['Pending: ' + payload.claims.status_counts.pending, 'Approved: ' + payload.claims.status_counts.approved, 'Rejected: ' + payload.claims.status_counts.rejected]);
    addCard('Hunt aggregate activity', ['All recorded clicks: ' + payload.hunt.aggregate_click_count, 'Recent fixed-window clicks: ' + payload.hunt.recent_window_click_count]);
    status.textContent = 'Read-only aggregate overview loaded.';
  } catch (_) {
    overview.replaceChildren();
    status.textContent = 'Quest/Hunt overview is currently unavailable.';
  }
}
load();
</script>
</body>
</html>
