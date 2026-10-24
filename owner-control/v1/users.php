<?php
declare(strict_types=1);
// Owner Control R4.1: owner-only, read-only user search presentation.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../api/config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.users_ui.v1');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') { exit; }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Owner Control — User Management</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}body{margin:0;background:#111820;color:#f4f7fb;padding:24px}
main{max-width:1050px;margin:auto}a{color:#f6cc65}.panel{background:#1b2530;border:1px solid #33404c;border-radius:12px;padding:20px;margin:18px 0}
form{display:flex;flex-wrap:wrap;gap:10px}input,button{font:inherit;padding:10px;border-radius:7px}input{flex:1;min-width:220px}button{cursor:pointer}
table{border-collapse:collapse;width:100%;table-layout:fixed}th,td{text-align:left;padding:12px;border-bottom:1px solid #33404c;overflow-wrap:anywhere}
.note{color:#b8c6d2}.pager{display:flex;align-items:center;gap:12px;margin-top:15px}.suggestions{margin-top:8px;border:1px solid #506276;border-radius:8px;overflow:hidden}.suggestions[hidden]{display:none}.suggestion{display:block;width:100%;background:#202f3e;color:#f4f7fb;border:0;border-bottom:1px solid #405263;text-align:left;border-radius:0;padding:10px}.suggestion:hover,.suggestion[aria-selected="true"]{background:#344c64}.suggestion small{display:block;color:#bdcbd9}button:disabled{opacity:.5;cursor:not-allowed}
</style></head><body><main>
<p><a href="/owner-control/v1/index.php">← Owner Control</a></p>
<h1>User Management</h1><p class="note">Owner-only, read-only lookup. No balance, inventory, or account modifications.</p>
<section class="panel" aria-label="User lookup">
<form id="search-form"><input id="query" name="q" minlength="2" maxlength="64" required placeholder="Username or exact Discord ID" aria-label="Search users" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="user-suggestions" autocomplete="off"><button type="submit" id="search-button">Search</button></form><div id="user-suggestions" class="suggestions" role="listbox" aria-label="Matching players" hidden></div>
<p id="status" role="status">Enter a username or exact Discord ID.</p>
<div style="overflow-x:auto"><table><thead><tr><th>Username</th><th>Discord ID</th><th>Account created</th></tr></thead><tbody id="results"></tbody></table></div>
<div class="pager"><button id="previous" type="button" disabled>Previous</button><span id="page-info"></span><button id="next" type="button" disabled>Next</button></div>
</section></main>
<script>
'use strict';
const form = document.getElementById('search-form');
const queryInput = document.getElementById('query');
const statusElement = document.getElementById('status');
const results = document.getElementById('results');
const previous = document.getElementById('previous');
const next = document.getElementById('next');
const pageInfo = document.getElementById('page-info');
// narrrfs_r42_autocomplete: bounded suggestions reuse the existing owner-only search endpoint.
const suggestions = document.getElementById('user-suggestions');
let suggestionController = null;
let suggestionTimer = null;
let suggestionSerial = 0;
let suggestionMatches = [];
let activeSuggestionIndex = -1;
let currentQuery = '';
let currentOffset = 0;
let canAdvance = false;
let requestSerial = 0;
let controller = null;
const pageSize = 25;
function clearResults() { results.replaceChildren(); previous.disabled = true; next.disabled = true; pageInfo.textContent = ''; }
function textCell(row, text) { const cell = document.createElement('td'); cell.textContent = text; row.append(cell); }
async function loadUsers(query, offset) {
  const serial = ++requestSerial;
  if (controller) controller.abort();
  controller = new AbortController();
  clearResults();
  statusElement.textContent = 'Searching users…';
  try {
    const params = new URLSearchParams({q: query, offset: String(offset)});
    const response = await fetch('/api/owner-control/v1/user-search.php?' + params.toString(),
      {credentials: 'same-origin', cache: 'no-store', signal: controller.signal});
    if (!response.ok) throw new Error('User search unavailable');
    const payload = await response.json();
    if (!payload || payload.success !== true || payload.capability !== 'owner_control_user_search' ||
        !Array.isArray(payload.users) || !payload.pagination || payload.users.length > pageSize ||
        payload.pagination.offset !== offset || payload.pagination.limit !== pageSize ||
        typeof payload.pagination.has_more !== 'boolean') throw new Error('Invalid response');
    if (serial !== requestSerial) return;
    const fragment = document.createDocumentFragment();
    for (const user of payload.users) {
      if (!user || typeof user.discord_id !== 'string' || typeof user.username !== 'string' ||
          (user.created_at !== null && typeof user.created_at !== 'string')) throw new Error('Invalid row');
      const row = document.createElement('tr');
      textCell(row, user.username);
      textCell(row, user.discord_id);
      textCell(row, user.created_at || 'Not recorded');
      fragment.append(row);
    }
    results.replaceChildren(fragment);
    currentOffset = offset;
    canAdvance = payload.pagination.has_more;
    previous.disabled = offset === 0;
    next.disabled = !canAdvance;
    pageInfo.textContent = 'Page ' + (Math.floor(offset / pageSize) + 1);
    statusElement.textContent = payload.users.length ? payload.users.length + ' users shown.' : 'No matching users found.';
  } catch (error) {
    if (serial !== requestSerial) return;
    clearResults();
    statusElement.textContent = 'User search is currently unavailable.';
  }
}

// Autocomplete uses its own cancellation state: submitting/paging continues to use loadUsers().
function closeSuggestions() {
  if (suggestionTimer !== null) clearTimeout(suggestionTimer);
  suggestionTimer = null;
  ++suggestionSerial;
  if (suggestionController) suggestionController.abort();
  suggestionController = null;
  suggestionMatches = [];
  activeSuggestionIndex = -1;
  suggestions.replaceChildren();
  suggestions.hidden = true;
  queryInput.setAttribute('aria-expanded', 'false');
  queryInput.removeAttribute('aria-activedescendant');
}
function selectSuggestion(user) {
  closeSuggestions();
  queryInput.value = user.username;
  currentQuery = user.discord_id;
  currentOffset = 0;
  loadUsers(currentQuery, 0);
}
function renderSuggestions() {
  const fragment = document.createDocumentFragment();
  suggestionMatches.forEach((user, index) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'suggestion';
    button.id = 'user-suggestion-' + index;
    button.setAttribute('role', 'option');
    button.setAttribute('aria-selected', String(index === activeSuggestionIndex));
    button.textContent = user.username;
    const secondary = document.createElement('small');
    secondary.textContent = user.discord_id;
    button.append(secondary);
    button.addEventListener('click', () => selectSuggestion(user));
    fragment.append(button);
  });
  suggestions.replaceChildren(fragment);
  suggestions.hidden = suggestionMatches.length === 0;
  queryInput.setAttribute('aria-expanded', String(suggestionMatches.length > 0));
  if (activeSuggestionIndex >= 0) {
    queryInput.setAttribute('aria-activedescendant', 'user-suggestion-' + activeSuggestionIndex);
  } else {
    queryInput.removeAttribute('aria-activedescendant');
  }
}
async function fetchSuggestions(query) {
  const serial = ++suggestionSerial;
  if (suggestionController) suggestionController.abort();
  suggestionController = new AbortController();
  try {
    const params = new URLSearchParams({q: query, offset: '0'});
    const response = await fetch('/api/owner-control/v1/user-search.php?' + params.toString(),
      {credentials: 'same-origin', cache: 'no-store', signal: suggestionController.signal});
    if (!response.ok) throw new Error('Suggestion endpoint unavailable');
    const payload = await response.json();
    if (serial !== suggestionSerial || queryInput.value.trim() !== query) return;
    if (!payload || payload.success !== true || payload.capability !== 'owner_control_user_search' ||
        !Array.isArray(payload.users) || payload.users.length > 25) throw new Error('Invalid suggestions');
    const matches = [];
    for (const user of payload.users) {
      if (!user || typeof user.username !== 'string' || typeof user.discord_id !== 'string' ||
          !/^\d{17,20}$/.test(user.discord_id)) throw new Error('Invalid suggestion item');
      matches.push({username: user.username, discord_id: user.discord_id});
    }
    suggestionMatches = matches;
    activeSuggestionIndex = -1;
    renderSuggestions();
  } catch (error) {
    if (serial === suggestionSerial) closeSuggestions();
  }
}
queryInput.addEventListener('input', () => {
  closeSuggestions();
  const query = queryInput.value.trim();
  if (query.length < 2 || query.length > 64 || /[\x00-\x1f\x7f]/.test(query)) return;
  suggestionTimer = setTimeout(() => { suggestionTimer = null; fetchSuggestions(query); }, 250);
});
queryInput.addEventListener('keydown', (event) => {
  if (suggestions.hidden || !suggestionMatches.length) return;
  if (event.key === 'Escape') { event.preventDefault(); closeSuggestions(); return; }
  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    event.preventDefault();
    activeSuggestionIndex = event.key === 'ArrowDown'
      ? (activeSuggestionIndex + 1) % suggestionMatches.length
      : (activeSuggestionIndex - 1 + suggestionMatches.length) % suggestionMatches.length;
    renderSuggestions();
  } else if (event.key === 'Enter' && activeSuggestionIndex >= 0) {
    event.preventDefault();
    selectSuggestion(suggestionMatches[activeSuggestionIndex]);
  }
});
document.addEventListener('pointerdown', (event) => {
  if (event.target !== queryInput && !suggestions.contains(event.target)) closeSuggestions();
});

form.addEventListener('submit', (event) => {
  closeSuggestions();
  event.preventDefault();
  const query = queryInput.value.trim();
  if (query.length < 2 || query.length > 64) {
    ++requestSerial;
    if (controller) controller.abort();
    currentQuery = '';
    currentOffset = 0;
    canAdvance = false;
    clearResults();
    statusElement.textContent = 'Enter 2–64 characters.';
    return;
  }
  currentQuery = query;
  loadUsers(currentQuery, 0);
});
previous.addEventListener('click', () => { if (currentOffset >= pageSize) loadUsers(currentQuery, currentOffset - pageSize); });
next.addEventListener('click', () => { if (canAdvance && currentOffset <= 100000 - pageSize) loadUsers(currentQuery, currentOffset + pageSize); });
</script></body></html>
