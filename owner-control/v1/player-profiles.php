<?php
declare(strict_types=1);
// R5.1: Owner-only presentation. A player ID is not an authentication credential.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../api/config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.profiles_ui.v1');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') { exit; }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Owner Control — Player Profiles</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}body{margin:0;padding:24px;background:#111820;color:#f4f7fb}main{max-width:1100px;margin:auto}a{color:#f6cc65}.panel{background:#1b2530;border:1px solid #33404c;border-radius:12px;padding:20px;margin:18px 0}.note{color:#b8c6d2}input,button{font:inherit;padding:10px;border-radius:7px}input{width:min(100%,370px)}button{cursor:pointer}button:disabled{opacity:.5}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}.tile{background:#273440;border-radius:8px;padding:12px}.tile strong{display:block}table{border-collapse:collapse;width:100%;table-layout:fixed}td,th{text-align:left;padding:9px;border-bottom:1px solid #405263;overflow-wrap:anywhere}section[hidden]{display:none}h2{margin-top:0}
#profile-suggestions:not([hidden]){margin-top:6px;border:1px solid #506276;border-radius:7px}
#profile-suggestions button{display:block;text-align:left;width:100%;background:#243444;color:white;border:0;border-bottom:1px solid #405263;border-radius:0}
#profile-suggestions button:hover,#profile-suggestions button[aria-selected="true"]{background:#3b536c}
</style></head><body><main>
<p><a href="/owner-control/v1/index.php">← Owner Control</a> · <a href="/owner-control/v1/users.php">User Management</a></p>
<h1>Player Profile Center — R5.1</h1>
<p class="note">Read-only account, game activity, regular inventory, roles and Genetic Item research slot capacity. Does not grant, claim, or change anything.</p>
<form id="profile-form"><label for="player-id">Exact Discord ID</label> <input id="player-id" inputmode="numeric" pattern="[0-9]{17,20}" minlength="17" maxlength="20" required> <button type="submit">Load profile</button></form>
<div class="panel" aria-label="Find player by username"><label for="profile-lookup">Find by username</label> <input id="profile-lookup" type="text" minlength="2" maxlength="64" autocomplete="off" aria-label="Find player by username" role="combobox" aria-autocomplete="list" aria-controls="profile-suggestions" aria-expanded="false"><div id="profile-suggestions" role="listbox" hidden></div><p class="note" id="profile-lookup-status">Type at least 2 characters to find players.</p></div>

<p id="status" role="status">Enter an exact Discord ID. User Management autocomplete can help find it.</p>
<section class="panel" id="profile" hidden><h2 id="player-name">Player</h2><p id="identity"></p><div class="grid" id="metrics"></div>
<h3>Genetic Item upgrade slots</h3><div class="grid" id="slots"></div><p class="note">Both upgrading and ready-to-claim items occupy capacity. Contest slot grants are not enabled in R5.1.</p>
<h3>Game activity (recorded score entries, not match counts)</h3><div style="overflow-x:auto"><table><thead><tr><th>Game</th><th>Entries</th><th>Last entry</th></tr></thead><tbody id="games"></tbody></table></div>
<h3>Recent score entries</h3><div style="overflow-x:auto"><table><thead><tr><th>When</th><th>Game</th><th>Score ledger entry</th><th>Season</th></tr></thead><tbody id="scores"></tbody></table></div>
<h3>Regular inventory (first 25)</h3><div style="overflow-x:auto"><table><thead><tr><th>Item</th><th>Quantity</th><th>Acquired</th></tr></thead><tbody id="inventory"></tbody></table></div>
<h3>Recorded roles (first 25)</h3><div style="overflow-x:auto"><table><thead><tr><th>Role</th><th>Recorded at</th></tr></thead><tbody id="roles"></tbody></table></div>
<h3>Season achievements (first 25)</h3><div style="overflow-x:auto"><table><thead><tr><th>Season</th><th>Game</th><th>Highest score</th><th>DSPOINC recorded</th></tr></thead><tbody id="achievements"></tbody></table></div>
</section></main><script>
'use strict';
const form=document.getElementById('profile-form');
const field=document.getElementById('player-id');
const status=document.getElementById('status');
const panel=document.getElementById('profile');
let serial=0,controller=null;
function renderCells(id,rows,keys){const target=document.getElementById(id);const fragment=document.createDocumentFragment();for(const value of rows){const tr=document.createElement('tr');for(const key of keys){const td=document.createElement('td');td.textContent=value[key]==null?'Not recorded':String(value[key]);tr.append(td);}fragment.append(tr);}target.replaceChildren(fragment);if(!rows.length){const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=keys.length;td.textContent='No recorded entries';tr.append(td);target.append(tr);}}
function tiles(id,values){const target=document.getElementById(id);target.replaceChildren();for(const [label,value] of values){const div=document.createElement('div');div.className='tile';const h=document.createElement('strong');h.textContent=label;const p=document.createElement('p');p.textContent=String(value);div.append(h,p);target.append(div);}}
async function load(id){const request=++serial;if(controller)controller.abort();controller=new AbortController();panel.hidden=true;status.textContent='Loading profile…';try{const response=await fetch('/api/owner-control/v1/player-profile.php?'+new URLSearchParams({user_id:id}),{credentials:'same-origin',cache:'no-store',signal:controller.signal});if(!response.ok)throw new Error('Profile unavailable');const data=await response.json();if(request!==serial)return;if(!data||data.success!==true||data.capability!=='owner_control_player_profile'||data.player?.discord_id!==id||!Array.isArray(data.roles)||!Array.isArray(data.game_summary)||!Array.isArray(data.regular_inventory)||!Array.isArray(data.recent_score_entries)||!Array.isArray(data.season_achievements)||!data.genetic_upgrade_slots)throw new Error('Invalid response');document.getElementById('player-name').textContent=data.player.username||'Unknown player';document.getElementById('identity').textContent='Discord ID: '+id+' · Account created: '+(data.player.created_at||'Not recorded');tiles('metrics',[['Game categories',data.game_summary.length],['Recent score entries shown',data.recent_score_entries.length],['Regular inventory rows shown',data.regular_inventory.length],['Recorded roles shown',data.roles.length]]);const slots=data.genetic_upgrade_slots;tiles('slots',[['Capacity',slots.limit+' / 3'],['Occupied',slots.occupied],['Available',slots.available]]);renderCells('games',data.game_summary,['game','entry_count','latest_record']);renderCells('scores',data.recent_score_entries,['timestamp','game','score','season']);renderCells('inventory',data.regular_inventory,['item_name','quantity','acquired_at']);renderCells('roles',data.roles,['role_name','timestamp']);renderCells('achievements',data.season_achievements,['season_id','game','highest_score','dspoinc_earned']);panel.hidden=false;status.textContent='Read-only profile loaded. Lists are capped at 25 records each.';}catch(error){if(request!==serial)return;panel.hidden=true;status.textContent='Profile unavailable or feature disabled.';}}
form.addEventListener('submit',event=>{event.preventDefault();const id=field.value.trim();if(!/^\d{17,20}$/.test(id)){status.textContent='Enter an exact 17–20 digit Discord ID.';return;}load(id);});
// r51_profile_lookup_v2: owner-only autocomplete uses the existing guarded user-search API.
const lookup = document.getElementById('profile-lookup');
const suggestions = document.getElementById('profile-suggestions');
const lookupStatus = document.getElementById('profile-lookup-status');
let lookupTimer = null, lookupSerial = 0, lookupController = null, lookupItems = [], lookupIndex = -1;
function closeLookup() { suggestions.hidden = true; suggestions.replaceChildren(); lookup.setAttribute('aria-expanded','false'); lookupIndex = -1; }
function cancelLookup() { if(lookupTimer!==null)clearTimeout(lookupTimer);lookupTimer=null;++lookupSerial;if(lookupController)lookupController.abort();closeLookup(); }
function pickLookup(player) { cancelLookup(); lookup.value=player.username;field.value=player.discord_id;load(player.discord_id); }
function renderLookup(items) { lookupItems=items;lookupIndex=-1;suggestions.replaceChildren();for(const player of items){const b=document.createElement('button');b.type='button';b.setAttribute('role','option');b.setAttribute('aria-selected','false');b.textContent=player.username+' ('+player.discord_id+')';b.addEventListener('mousedown',e=>e.preventDefault());b.addEventListener('click',()=>pickLookup(player));suggestions.append(b);}suggestions.hidden=items.length===0;lookup.setAttribute('aria-expanded',String(items.length>0));lookupStatus.textContent=items.length?items.length+' matching players. Select one to load profile.':'No matching players.';}
async function searchLookup(query){const serial=++lookupSerial;if(lookupController)lookupController.abort();lookupController=new AbortController();try{const response=await fetch('/api/owner-control/v1/user-search.php?'+new URLSearchParams({q:query,offset:'0'}),{credentials:'same-origin',cache:'no-store',signal:lookupController.signal});if(!response.ok)throw new Error('Search unavailable');const data=await response.json();if(serial!==lookupSerial)return;if(!data||data.success!==true||data.capability!=='owner_control_user_search'||!Array.isArray(data.users)||data.users.length>25)throw new Error('Invalid search result');renderLookup(data.users.filter(item=>item&&/^\d{17,20}$/.test(item.discord_id)&&typeof item.username==='string'));}catch(error){if(serial!==lookupSerial)return;closeLookup();lookupStatus.textContent='Player suggestions unavailable.';}}
lookup.addEventListener('input',()=>{cancelLookup();const query=lookup.value.trim();if(query.length<2||query.length>64){lookupStatus.textContent='Type 2–64 characters.';return;}lookupStatus.textContent='Finding players…';lookupTimer=setTimeout(()=>{lookupTimer=null;searchLookup(query);},250);});
lookup.addEventListener('keydown',e=>{if(e.key==='Escape'){cancelLookup();return;}if(suggestions.hidden||!lookupItems.length)return;if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();lookupIndex=(lookupIndex+(e.key==='ArrowDown'?1:-1)+lookupItems.length)%lookupItems.length;Array.from(suggestions.children).forEach((el,i)=>el.setAttribute('aria-selected',String(i===lookupIndex)));}else if(e.key==='Enter'&&lookupIndex>=0){e.preventDefault();pickLookup(lookupItems[lookupIndex]);}});
lookup.addEventListener('blur',()=>{setTimeout(closeLookup,120);});

const initial=new URLSearchParams(location.search).get('user_id');if(initial&&/^\d{17,20}$/.test(initial)){field.value=initial;load(initial);}
</script></body></html>
