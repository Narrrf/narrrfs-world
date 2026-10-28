<?php
declare(strict_types=1);

// Owner Control Genetic Catalog V1: display-only metadata view.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../api/config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.genetic_catalog.ui.v1');

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
<title>Owner Control — Genetic Catalog</title>
<style>
  :root { color-scheme: dark; font-family: system-ui, sans-serif; }
  body { margin: 0; background: #111820; color: #f4f7fb; padding: 24px; }
  main { max-width: 1050px; margin: auto; } a { color: #f6cc65; }
  .toolbar, .cards { display: grid; gap: 12px; } .toolbar { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
  .cards { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-top: 20px; }
  .card { background: #1b2530; border: 1px solid #33404c; border-radius: 12px; padding: 16px; }
  .meta { color: #b8c6d2; font-size: .9rem; } button, select { font: inherit; padding: 8px; }
</style>
</head>
<body>
<main>
  <p><a href="/owner-control/v1/index.php">← Owner Control</a></p>
  <h1>Genetic Catalog</h1>
  <p class="meta">Owner-only, read-only catalog metadata. No prices, stock, ownership, inventory, or editing controls are available.</p>
  <div class="toolbar">
    <label>Type <select id="traitType"><option value="all">All</option><option value="outfit">Outfit</option><option value="accessories">Accessories</option></select></label>
    <button id="previous" type="button" disabled>Previous</button>
    <button id="next" type="button" disabled>Next</button>
  </div>
  <p id="status" role="status">Loading catalog metadata…</p>
  <section id="cards" class="cards" aria-label="Genetic catalog metadata"></section>
</main>
<script>
'use strict';

const state = { cursor: null, previous: [], next: null };
const allowedFields = ['catalog_id', 'trait_type', 'trait_value', 'display_title', 'description', 'rarity_tier', 'image_type', 'presentation_ref', 'is_active', 'is_visible'];
const traitType = document.getElementById('traitType');
const previous = document.getElementById('previous');
const next = document.getElementById('next');
const status = document.getElementById('status');
const cards = document.getElementById('cards');

function validItem(item) {
  return item && typeof item === 'object' && Object.keys(item).every((key) => allowedFields.includes(key)) &&
    Number.isSafeInteger(item.catalog_id) && typeof item.trait_type === 'string' &&
    typeof item.trait_value === 'string' && typeof item.display_title === 'string' &&
    typeof item.description === 'string' && typeof item.rarity_tier === 'string' &&
    typeof item.image_type === 'string' && (typeof item.presentation_ref === 'string' || item.presentation_ref === null) &&
    typeof item.is_active === 'boolean' && typeof item.is_visible === 'boolean';
}

function addText(container, label, value) {
  const line = document.createElement('p');
  line.className = 'meta';
  line.textContent = label + ': ' + value;
  container.append(line);
}

function render(payload) {
  if (!payload || payload.success !== true || payload.capability !== 'owner_control_genetic_catalog_read' ||
      !Array.isArray(payload.items) || !payload.page || !Number.isSafeInteger(payload.page.limit) ||
      typeof payload.page.has_more !== 'boolean' || !(typeof payload.page.next_cursor === 'string' || payload.page.next_cursor === null) ||
      !payload.items.every(validItem)) {
    throw new Error('Invalid catalog response.');
  }
  const fragment = document.createDocumentFragment();
  for (const item of payload.items) {
    const card = document.createElement('article'); card.className = 'card';
    const heading = document.createElement('h2'); heading.textContent = item.display_title;
    card.append(heading);
    addText(card, 'Type', item.trait_type + ' · ' + item.trait_value);
    addText(card, 'Rarity', item.rarity_tier);
    addText(card, 'Visibility', item.is_active && item.is_visible ? 'Active and visible' : 'Not publicly visible');
    if (item.description !== '') { addText(card, 'Description', item.description); }
    fragment.append(card);
  }
  cards.replaceChildren(fragment);
  state.next = payload.page.next_cursor;
  previous.disabled = state.previous.length === 0;
  next.disabled = !payload.page.has_more || typeof state.next !== 'string';
  status.textContent = payload.items.length ? 'Read-only catalog metadata loaded.' : 'No catalog metadata matched this filter.';
}

async function load(cursor, pushCurrent) {
  const params = new URLSearchParams({ limit: '25', trait_type: traitType.value });
  if (cursor) { params.set('cursor', cursor); }
  try {
    const response = await fetch('/api/owner-control/v1/genetic/catalog.php?' + params.toString(), { credentials: 'same-origin', cache: 'no-store' });
    if (!response.ok) { throw new Error('Catalog unavailable.'); }
    const payload = await response.json();
    if (pushCurrent && state.cursor !== null) { state.previous.push(state.cursor); }
    state.cursor = cursor;
    render(payload);
  } catch (error) {
    cards.replaceChildren(); next.disabled = true; previous.disabled = state.previous.length === 0;
    status.textContent = 'Catalog metadata is currently unavailable.';
  }
}

traitType.addEventListener('change', () => { state.cursor = null; state.previous = []; load(null, false); });
next.addEventListener('click', () => load(state.next, true));
previous.addEventListener('click', () => load(state.previous.pop() ?? null, false));
load(null, false);
</script>
</body>
</html>
