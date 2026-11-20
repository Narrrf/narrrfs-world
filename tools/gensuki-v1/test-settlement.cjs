// Full endpoint flow with synthetic transactions, mocked partners and SQLite :memory:.
// The application endpoint is copied to a disposable directory; no real DB or network is used.
const fs = require('fs'), os = require('os'), path = require('path'), assert = require('assert');
const { spawnSync, spawn } = require('child_process');
const kit = require('@solana/kit'), web3 = require('@solana/web3.js');
const { getTransactionSerializer } = require('@metaplex-foundation/umi');
const { toWeb3JsTransaction } = require('@metaplex-foundation/umi-web3js-adapters');

const root = path.join(__dirname, '../..'), tempRoot = path.resolve(os.tmpdir());
const temp = fs.mkdtempSync(path.join(tempRoot, 'narrrfs-settle-test-'));
if (path.dirname(path.resolve(temp)) !== tempRoot) throw Error('Fixture escaped temporary root');
const php = process.env.NARRRFS_TEST_PHP || 'C:/xampp-server/php/php.exe';
function once(source, from, to) { assert.strictEqual(source.split(from).length - 1, 1, from); return source.replace(from, to); }
function fixture(version) {
  const kp = web3.Keypair.generate(), wallet = kp.publicKey.toBase58();
  let message = kit.createTransactionMessage({ version });
  message = kit.setTransactionMessageFeePayer(kit.address(wallet), message);
  message = kit.setTransactionMessageLifetimeUsingBlockhash({blockhash:kit.blockhash('11111111111111111111111111111111'),lastValidBlockHeight:100n},message);
  if (version === 1) message = kit.setTransactionMessageComputeUnitLimit(200000, message);
  message = kit.appendTransactionMessageInstruction({programAddress:kit.address('11111111111111111111111111111111'),data:new Uint8Array(version ? 1800 : 8).fill(7)},message);
  const prepared = Buffer.from(kit.getBase64EncodedWireTransaction(kit.compileTransaction(message)), 'base64');
  const tx = toWeb3JsTransaction(getTransactionSerializer().deserialize(prepared)[0]); tx.sign([kp]);
  return { wallet, prepared: prepared.toString('base64'), observed: Buffer.from(tx.serialize()).toString('base64'), signature: kit.getBase58Decoder().decode(tx.signatures[0]) };
}
const stub = `<?php
const SPOINC_BRIDGE_PARTNER_NAME='fixture';
const SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC=10000;
function spoinc_bridge_boot_json_api($x) {}
function spoinc_bridge_get_request_data() { return json_decode(getenv('FIXTURE_REQUEST'),true); }
function spoinc_bridge_json_response(array $payload,int $code=200): void {
 $p=$GLOBALS['fixturePdo']??null; $counts=[];
 if($p) foreach(['tbl_user_scores','tbl_score_adjustments','tbl_spoinc_bridge_transactions','tbl_spoinc_bridge_ledger_audit'] as $t) $counts[$t]=(int)$p->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
 echo json_encode(['status'=>$code,'payload'=>$payload,'counts'=>$counts,'intent_status'=>$p ? $p->query('SELECT status FROM tbl_spoinc_bridge_intents WHERE intent_id=1')->fetchColumn() : null,'intent_response'=>$p ? $p->query('SELECT raw_response_json FROM tbl_spoinc_bridge_intents WHERE intent_id=1')->fetchColumn() : null,'partner_calls'=>$GLOBALS['partnerCalls']??0]); exit;
}
function spoinc_bridge_open_database(): PDO {
 $testDb = getenv('FIXTURE_DB');
 $p=new PDO($testDb !== false && $testDb !== '' ? 'sqlite:' . $testDb : 'sqlite::memory:');
 $p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $p->exec('PRAGMA busy_timeout=5000');
 if ($testDb && (int)$p->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_spoinc_bridge_intents'")->fetchColumn() > 0) { $GLOBALS['fixturePdo']=$p; return $p; }
 $p->exec('CREATE TABLE tbl_spoinc_bridge_intents(intent_id INTEGER PRIMARY KEY,discord_id TEXT,route_key TEXT,status TEXT,narrrfs_status TEXT,gensuki_status TEXT,project_id TEXT,wallet TEXT,spoinc_amount TEXT,dspoinc_amount INTEGER,raw_request_json TEXT,raw_response_json TEXT,expires_at TEXT,idempotency_id TEXT,submitted_at TEXT,confirmed_at TEXT,settled_at TEXT,updated_at TEXT,failed_at TEXT,error_message TEXT)');
 $p->exec('CREATE TABLE tbl_spoinc_bridge_transactions(intent_id INTEGER,idempotency_id TEXT,partner_name TEXT,project_id TEXT,transaction_hash TEXT,signature TEXT,wallet TEXT,route_key TEXT,direction TEXT,gensuki_status TEXT,narrrfs_status TEXT,raw_get_transaction_response_json TEXT,created_at TEXT,updated_at TEXT,confirmed_at TEXT)');
 $p->exec('CREATE TABLE tbl_user_scores(user_id TEXT,score INTEGER,game TEXT,source TEXT,season TEXT)');
 $p->exec('CREATE TABLE tbl_score_adjustments(user_id TEXT,admin_id TEXT,amount INTEGER,action TEXT,reason TEXT)');
 $p->exec('CREATE TABLE tbl_spoinc_bridge_ledger_audit(intent_id INTEGER,transaction_id INTEGER,idempotency_id TEXT,transaction_hash TEXT,discord_id TEXT,wallet TEXT,route_key TEXT,direction TEXT,ledger_action TEXT,dspoinc_delta INTEGER,spoinc_amount TEXT,conversion_rate_dspoinc_per_spoinc INTEGER,tbl_user_scores_id INTEGER,tbl_score_adjustment_id INTEGER,status TEXT,metadata_json TEXT,processed_at TEXT)');
 $p->exec("CREATE TABLE tbl_seasons(season_name TEXT,is_active INTEGER); INSERT INTO tbl_seasons VALUES ('fixture',1)");
 $x=json_decode(getenv('FIXTURE_INTENT'),true);$stmt=$p->prepare("INSERT INTO tbl_spoinc_bridge_intents(intent_id,discord_id,route_key,status,narrrfs_status,project_id,wallet,spoinc_amount,dspoinc_amount,raw_request_json,raw_response_json,idempotency_id) VALUES(1,'user','SPOINC_TO_DSPOINC','claim_payload_ready','awaiting_user_signature','project',?,'1.2500',12500,?,?, 'fixture-id')");
 $stmt->execute([$x['wallet'],json_encode(['token_mint'=>'mint']),json_encode(['safe_response'=>['transaction'=>$x['prepared']]])]);$GLOBALS['fixturePdo']=$p;return $p;
}
function spoinc_bridge_require_public_route_access($pdo,$data,$route,$settlement){return 'user';}
function spoinc_bridge_is_valid_solana_address($value){return is_string($value) && $value !== '';}
function spoinc_bridge_load_config($pdo){return ['project_id'=>'project','token_mint'=>'mint','token_decimals'=>6,'api_base_url'=>'https://fixture.invalid'];}
function fixture_partner_confirm($url,$key,$payload){$GLOBALS['partnerCalls']=($GLOBALS['partnerCalls']??0)+1; if(getenv('FIXTURE_PARTNER_FAIL')==='1') return ['success'=>false,'json'=>null,'http_code'=>0,'raw_body'=>'','error'=>'timeout']; return ['success'=>true,'json'=>['success'=>true],'http_code'=>200,'raw_body'=>'','error'=>''];}
`;
async function main() {
try {
  fs.writeFileSync(path.join(temp, 'bridge-helpers.php'), stub);
  fs.writeFileSync(path.join(temp, 'bridge-tester-helpers.php'), '<?php\n');
  fs.copyFileSync(path.join(root, 'api/partner/spoinc/transaction-binding.php'), path.join(temp, 'transaction-binding.php'));
  let source = fs.readFileSync(path.join(root, 'api/partner/spoinc/confirm-spoinc-to-dspoinc-deposit.php'), 'utf8');
  source = once(source, '$apiKey = spoinc_bridge_get_gensuki_outbound_api_key();', "$apiKey = 'fixture-key';");
  source = once(source, '$solanaSignatureStatus = spoinc_bridge_wait_for_solana_signature_status($transactionHash);', "$solanaSignatureStatus = getenv('FIXTURE_CHAIN_FAILED') === '1' ? ['rpc_success'=>true,'found'=>true,'confirmed_success'=>false,'confirmed_failed'=>true] : ['rpc_success'=>true,'found'=>true,'confirmed_success'=>true,'confirmed_failed'=>false];");
  source = once(source, '$bindingRpc = spoinc_binding_fetch_rpc($transactionHash);', "$bindingRpc = json_decode(getenv('FIXTURE_RPC'), true);");
  source = once(source, 'spoinc_bridge_post_gensuki_json($confirmEndpoint, $apiKey, $confirmRequest)', 'fixture_partner_confirm($confirmEndpoint, $apiKey, $confirmRequest)');
  source = once(source, '    $reserveStmt = $pdo->prepare("UPDATE tbl_spoinc_bridge_intents SET status', '    if (getenv("FIXTURE_CONCURRENT") === "1") usleep(300000);\n    $reserveStmt = $pdo->prepare("UPDATE tbl_spoinc_bridge_intents SET status');
  fs.writeFileSync(path.join(temp, 'confirm.php'), source);
  function makeEnv(tx, mode, fixtureDb = '') {
    const item = amount => ({accountIndex:3, mint:'mint', owner:tx.wallet, uiTokenAmount:{amount,decimals:6}});
    const rpc = {result:{meta:{err:mode.startsWith('chain-failed') ? {InstructionError:[0,'failed']} : null,preTokenBalances:[item('3000000')],postTokenBalances:[item(mode === 'amount' ? '1700000' : '1750000')]},transaction:[tx.observed,'base64']}};
    const intent = {wallet:tx.wallet,prepared:mode === 'missing' ? '' : tx.prepared};
    if (mode === 'message' || mode === 'chain-failed-message') { const b=Buffer.from(tx.observed,'base64'); b[b.length - (b[0] === 0x81 ? 65 : 3)] ^= 1; rpc.result.transaction[0]=b.toString('base64'); }
    if (mode === 'rpc-missing') delete rpc.result;
    const env={SystemRoot:process.env.SystemRoot,PATH:process.env.PATH,FIXTURE_REQUEST:JSON.stringify({intent_id:1,transactionHash:tx.signature,status:'complete'}),FIXTURE_INTENT:JSON.stringify(intent),FIXTURE_RPC:JSON.stringify(rpc),FIXTURE_PARTNER_FAIL:(mode === 'partner-fail' || mode === 'chain-failed-partner-fail') ? '1' : '0',FIXTURE_CHAIN_FAILED:mode.startsWith('chain-failed') ? '1' : '0',FIXTURE_DB:fixtureDb,FIXTURE_CONCURRENT:fixtureDb.includes('concurrent-') ? '1' : '0'};
    return env;
  }
  function run(tx, mode, fixtureDb = '') {
    const r=spawnSync(php,[path.join(temp,'confirm.php')],{encoding:'utf8',env:makeEnv(tx,mode,fixtureDb)});if(r.status!==0)throw Error(r.stderr || r.stdout);
    try{return JSON.parse(r.stdout)}catch(_){throw Error(r.stdout+r.stderr)}
  }
  function runAsync(tx, fixtureDb) {
    return new Promise((resolve,reject) => {
      const child=spawn(php,[path.join(temp,'confirm.php')],{env:makeEnv(tx,'ok',fixtureDb)});
      let out='',err=''; child.stdout.on('data',c=>out+=c); child.stderr.on('data',c=>err+=c);
      child.on('error',reject); child.on('close',code=>{if(code!==0)return reject(Error(err||out));try{resolve({pid:child.pid,result:JSON.parse(out)})}catch(e){reject(Error(out+err))}});
    });
  }
  for (const v of [0,1]) {
    const tx=fixture(v), ok=run(tx,'ok');
    assert.strictEqual(ok.status,200,JSON.stringify(ok)); assert.strictEqual(ok.partner_calls,1);
    for(const count of Object.values(ok.counts)) assert.strictEqual(count,1);
    for(const mode of ['message','amount','missing','rpc-missing']) {
      const bad=run(tx,mode); assert.strictEqual(bad.status,mode === 'rpc-missing' ? 503 : 409,JSON.stringify(bad));
      assert.strictEqual(bad.partner_calls,0); for(const count of Object.values(bad.counts)) assert.strictEqual(count,0);
    }
    const partnerFail=run(tx,'partner-fail');
    assert.strictEqual(partnerFail.status,502,JSON.stringify(partnerFail));
    assert.strictEqual(partnerFail.partner_calls,1);
    assert.strictEqual(partnerFail.intent_status,'claim_confirm_pending');
    for(const count of Object.values(partnerFail.counts)) assert.strictEqual(count,0);
    console.log('PASS V'+v+' settlement: matching credit; mismatches and unavailable RPC cause zero writes');
    console.log('PASS V'+v+' partner timeout: intent retained, no credit');
    const failedChain=run(tx,'chain-failed');
    assert.strictEqual(failedChain.status,200,JSON.stringify(failedChain));
    assert.strictEqual(failedChain.intent_status,'failed');
    for(const count of Object.values(failedChain.counts)) assert.strictEqual(count,0);
    const failedAndTimedOut=run(tx,'chain-failed-partner-fail');
    assert.strictEqual(failedAndTimedOut.status,502,JSON.stringify(failedAndTimedOut));
    assert.strictEqual(failedAndTimedOut.intent_status,'claim_confirm_pending');
    for(const count of Object.values(failedAndTimedOut.counts)) assert.strictEqual(count,0);
    const wrongFailed=run(tx,'chain-failed-message');
    assert.strictEqual(wrongFailed.status,409,JSON.stringify(wrongFailed));
    assert.strictEqual(wrongFailed.partner_calls,0);
    assert.strictEqual(wrongFailed.intent_status,'claim_payload_ready');
    for(const count of Object.values(wrongFailed.counts)) assert.strictEqual(count,0);
    console.log('PASS V'+v+' failed-chain status and partner-timeout handling: zero credit');
    console.log('PASS V'+v+' unrelated failed signature cannot close the intent');
    const pendingDb=path.join(temp,'pending-v'+v+'.sqlite');
    const timedOut=run(tx,'partner-fail',pendingDb), blocked=run(tx,'ok',pendingDb);
    assert.strictEqual(timedOut.status,502,JSON.stringify(timedOut));
    const reserved=JSON.parse(timedOut.intent_response);
    assert.strictEqual(reserved.confirmation_attempt.signature,tx.signature);
    assert.strictEqual(reserved.safe_response.transaction,tx.prepared);
    assert.strictEqual(blocked.status,409,JSON.stringify(blocked));
    assert.strictEqual(blocked.partner_calls,0);
    for(const count of Object.values(blocked.counts)) assert.strictEqual(count,0);
    console.log('PASS V'+v+' uncertain partner confirmation: retry blocked pending reconciliation');
    const replayDb = path.join(temp, 'replay-v' + v + '.sqlite');
    const first = run(tx, 'ok', replayDb), replay = run(tx, 'ok', replayDb);
    assert.strictEqual(first.status, 200, JSON.stringify(first));
    assert.strictEqual(replay.status, 404, JSON.stringify(replay));
    assert.strictEqual(replay.partner_calls, 0);
    for (const count of Object.values(replay.counts)) assert.strictEqual(count, 1);
    console.log('PASS V'+v+' settled intent replay: no second credit or partner call');
    const concurrentDb=path.join(temp,'concurrent-v'+v+'.sqlite');
    assert.strictEqual(run(tx,'rpc-missing',concurrentDb).status,503);
    const parallel=await Promise.all([runAsync(tx,concurrentDb),runAsync(tx,concurrentDb)]);
    assert.notStrictEqual(parallel[0].pid,parallel[1].pid);
    assert.strictEqual(parallel.filter(x=>x.result.status===200).length,1,JSON.stringify(parallel));
    assert.strictEqual(parallel.reduce((sum,x)=>sum+x.result.partner_calls,0),1,JSON.stringify(parallel));
    const final=run(tx,'ok',concurrentDb); assert.strictEqual(final.status,404,JSON.stringify(final));
    for(const count of Object.values(final.counts))assert.strictEqual(count,1);
    console.log('PASS V'+v+' concurrent separate PHP processes: exactly one durable credit (statuses '+parallel.map(x=>x.result.status).join(',')+'; partner calls '+parallel.map(x=>x.result.partner_calls).join(',')+')');
  }
} finally { fs.rmSync(temp,{recursive:true,force:true}); }
}
main().catch(error=>{console.error(error);process.exitCode=1});