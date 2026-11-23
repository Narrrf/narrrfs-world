<?php
declare(strict_types=1);
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__.'/../../api/config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.player_adjustment.ui.v1');
header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') exit;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Player adjustments</title>
<style>body{font:16px system-ui;background:#111820;color:#fff;margin:2rem}main{max-width:40rem;margin:auto}label{display:block;margin:1rem 0}input,select,button{font:inherit;padding:.5rem}a{color:#f6cc65}#status{min-height:3rem}</style></head><body><main>
<p><a href="/owner-control/v1/player-profiles.php">← Player Profiles</a></p>
<h1>Player adjustments</h1><p>Owner-only adjustments. Inventory support is limited to Green, Blue and Red Elixirs.</p>
<form id="adjust"><label>Discord user ID <input name="user_id" inputmode="numeric" required pattern="[0-9]{17,20}"></label>
<label>Type <select name="kind"><option value="points">DSPOINC</option><option value="inventory">Elixir inventory</option></select></label>
<label>Action <select name="action"><option value="add">Add</option><option value="remove">Remove</option></select></label>
<label>Elixir (inventory only) <select name="item_id"><option value="33">Green</option><option value="34">Blue</option><option value="35">Red</option></select></label>
<label>Amount <input name="amount" type="number" min="1" max="1000000000" step="1" required></label>
<label>Reason <input name="reason" minlength="4" maxlength="256" required></label><button type="submit">Review and apply</button></form>
<p id="status" role="status" aria-live="polite"></p></main>
<script>
const form=document.getElementById('adjust'), status=document.getElementById('status'), button=form.querySelector('button');let pending=null;
form.addEventListener('input',()=>{pending=null;});form.addEventListener('change',()=>{pending=null;});
form.addEventListener('submit',async event=>{event.preventDefault();const f=new FormData(form),kind=String(f.get('kind'));const payload={user_id:String(f.get('user_id')).trim(),action:String(f.get('action')),amount:Number(f.get('amount')),reason:String(f.get('reason')).trim()};if(kind==='inventory')payload.item_id=Number(f.get('item_id'));const fingerprint=JSON.stringify([kind,payload]);if(!pending||pending.fingerprint!==fingerprint)pending={fingerprint,key:crypto.randomUUID()};if(!confirm(`Apply ${payload.action} ${payload.amount} ${kind==='points'?'DSPOINC':'elixir item '+payload.item_id} for ${payload.user_id}?`)){pending=null;return;}const requestKey=pending.key;const controls=[...form.querySelectorAll('input,select,button')];controls.forEach(control=>control.disabled=true);status.textContent='Submitting…';try{const csrf=await fetch('/api/owner-control/v1/player-adjustment-csrf.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:kind})});const c=await csrf.json();if(!csrf.ok||!c.success)throw Error('Authorization unavailable');const url=kind==='points'?'/api/owner-control/v1/player-points-adjust.php':'/api/owner-control/v1/player-inventory-adjust.php';const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':c.csrf_token},body:JSON.stringify({...payload,idempotency_key:requestKey})});const data=await response.json();if(!response.ok||!data.success)throw Error(data.error||'Adjustment failed');status.textContent=`Saved. Before: ${data.result.before}; after: ${data.result.after}; audit: ${data.result.audit_id}.`;pending=null;}catch(e){status.textContent='Not confirmed: '+e.message+'. Retry unchanged to reuse the request key.';}finally{controls.forEach(control=>control.disabled=false);}});
</script></body></html>
