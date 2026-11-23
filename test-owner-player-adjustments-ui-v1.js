// Exercise the rendered script with a fake DOM and no network or database.
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const html = fs.readFileSync('owner-control/v1/player-adjustments.php', 'utf8');
const source = html.match(/<script>([\s\S]*?)<\/script>/)[1];
const handlers = {};
const controls = [{disabled:false}];
const form = {querySelector:()=>controls[0], querySelectorAll:()=>controls, addEventListener:(name, cb)=>{handlers[name]=cb;}};
const status = {textContent:''};
let values = {user_id:'111111111111111111',kind:'points',action:'add',amount:'10',reason:'fixture reason'};
let count=0, calls=[], shouldFail=true, confirmed=true;
const context = {document:{getElementById:id=>id==='adjust'?form:status}, FormData:class {get(name){return values[name]}},
  crypto:{randomUUID:()=>`fixture-key-${++count}`}, confirm:()=>confirmed,
  fetch:async(url, options)=>{calls.push({url,body:JSON.parse(options.body)});if(url.includes('csrf')) return {ok:true,json:async()=>({success:true,csrf_token:'fixture'})};
    return shouldFail?{ok:false,json:async()=>({success:false,error:'fixture_error'})}:{ok:true,json:async()=>({success:true,result:{before:10,after:20,audit_id:1}})};}};
vm.runInNewContext(source,context);
async function submit(){await handlers.submit({preventDefault(){}})}
(async()=>{await submit(); assert.match(status.textContent,/Not confirmed/);const first=calls[1].body.idempotency_key;
  await submit(); assert.equal(calls[3].body.idempotency_key,first);
  values.amount='11';handlers.input();await submit();assert.notEqual(calls[5].body.idempotency_key,first);
  values.kind='inventory';values.item_id='33';handlers.change();shouldFail=false;await submit();assert.equal(calls[7].body.item_id,33);assert.match(status.textContent,/Saved/);
  confirmed=false;const before=calls.length;await submit();assert.equal(calls.length,before);
  console.log('PASS: UI confirmation, failed retry key, target change and inventory submission');
})().catch(err=>{console.error(err);process.exitCode=1});
