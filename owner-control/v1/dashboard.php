<?php
declare(strict_types=1);

// Narrrfs World Owner Control R3 - Dashboard UI.
// Presentation only. No SQLite, legacy Admin API, or browser-derived authority.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../api/config/admin-authorization.php';

narrrfs_require_admin_request(
    'owner_control.dashboard.ui.v1'
);

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
<title>Owner Control — Dashboard</title>
<style>
  :root {color-scheme:dark;font-family:system-ui,sans-serif}
  body {margin:0;background:#111820;color:#f4f7fb;padding:24px}
  main {max-width:1050px;margin:auto}
  a {color:#f6cc65}
  .grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:24px}
  .card {background:#1b2530;border:1px solid #33404c;border-radius:12px;padding:20px}
  .label {color:#b8c6d2;font-size:.9rem}
  .value {font-size:2rem;font-weight:750;margin-top:8px}
  .note {color:#b8c6d2}
</style>
</head>
<body>
<main>
  <p><a href="/owner-control/v1/index.php">← Owner Control</a></p>
  <h1>Operational Dashboard</h1>
  <p class="note">Owner-only, read-only summary. Economy accounting is separate.</p>
  <p id="status" role="status">Loading verified metrics…</p>
  <section id="metrics" class="grid" aria-label="Dashboard metrics"></section>
</main>
<script>
'use strict';

const dashboardFields = [
  ['totalUsers', 'Total Users'],
  ['totalScores', 'Score Records'],
  ['totalItems', 'Active Store Items'],
  ['activeLabOffers', 'Active Lab Store Offers'],
  ['publicLabOffers', 'Public Lab Store Offers'],
  ['activeQuests', 'Active Quests']
];

async function loadDashboard() {
  const status = document.getElementById('status');
  const container = document.getElementById('metrics');

  try {
    const response = await fetch(
      '/api/owner-control/v1/dashboard-summary.php',
      {credentials: 'same-origin', cache: 'no-store'}
    );

    if (!response.ok) {
      throw new Error('Dashboard is unavailable.');
    }

    const payload = await response.json();

    if (!payload || payload.success !== true ||
        payload.capability !== 'owner_control_dashboard_summary') {
      throw new Error('Invalid dashboard response.');
    }

    // Render only validated numeric metrics; never insert server text as HTML.
    const fragment = document.createDocumentFragment();

    for (const [field, label] of dashboardFields) {
      const value = payload[field];

      if (!Number.isSafeInteger(value) || value < 0) {
        throw new Error('Invalid dashboard metric.');
      }

      const card = document.createElement('article');
      card.className = 'card';

      const heading = document.createElement('div');
      heading.className = 'label';
      heading.textContent = label;

      const number = document.createElement('div');
      number.className = 'value';
      number.textContent = value.toLocaleString();

      card.append(heading, number);
      fragment.append(card);
    }

    container.replaceChildren(fragment);
    status.textContent = 'Read-only metrics loaded.';
  } catch (error) {
    container.replaceChildren();
    status.textContent = 'Dashboard metrics are currently unavailable.';
  }
}

loadDashboard();
</script>
</body>
</html>
