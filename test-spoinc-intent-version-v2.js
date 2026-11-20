// Copies endpoint source into an isolated fixture. Network and auth are stubbed;
// all SQL is executed in a fresh SQLite :memory: database in a child PHP process.
const fs = require('fs');
const os = require('os');
const path = require('path');
const assert = require('assert');
const { spawnSync } = require('child_process');
const root = __dirname;
const tempRoot = path.resolve(os.tmpdir());
const temp = fs.mkdtempSync(path.join(tempRoot, 'narrrfs-intent-version-'));
if (path.dirname(path.resolve(temp)) !== tempRoot) throw Error('Fixture escaped temporary root');
const php = process.env.NARRRFS_TEST_PHP || 'C:/xampp-server/php/php.exe';
const endpointDir = path.join(root, 'api/partner/spoinc');
const files = ['create-gensuki-buy-intent.php', 'create-spoinc-to-dspoinc-deposit-intent.php'];
function replaceOnce(source, before, after) {
  assert.strictEqual(source.split(before).length - 1, 1, 'unexpected fixture anchor: ' + before);
  return source.replace(before, after);
}
const stub = `<?php
const SPOINC_BRIDGE_PARTNER_NAME = 'fixture';
const SPOINC_BRIDGE_CONVERSION_RATE_DSPOINC_PER_SPOINC = 10000;
function spoinc_bridge_boot_json_api($methods) {}
function spoinc_bridge_get_request_data() { return json_decode(getenv('FIXTURE_REQUEST'), true); }
function spoinc_bridge_json_response(array $payload, int $code=200): void {
  $rows = isset($GLOBALS['fixturePdo']) ? $GLOBALS['fixturePdo']->query('SELECT status,narrrfs_status,raw_request_json,raw_response_json FROM tbl_spoinc_bridge_intents')->fetchAll(PDO::FETCH_ASSOC) : [];
  echo json_encode(['status'=>$code,'payload'=>$payload,'forwarded'=>$GLOBALS['fixtureForwarded'] ?? null,'rows'=>$rows]); exit;
}
function spoinc_bridge_open_database(): PDO {
  $p = new PDO('sqlite::memory:'); $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $p->exec('CREATE TABLE tbl_spoinc_bridge_intents (intent_id INTEGER PRIMARY KEY AUTOINCREMENT, idempotency_id TEXT, partner_name TEXT, project_id TEXT, discord_id TEXT, wallet TEXT, route_key TEXT, direction TEXT, input_token TEXT, output_token TEXT, input_amount TEXT, expected_output_amount TEXT, minimum_output_amount TEXT, dspoinc_amount TEXT, spoinc_amount TEXT, conversion_rate_dspoinc_per_spoinc TEXT, latest_blockhash TEXT, last_valid_block_height TEXT, status TEXT, gensuki_status TEXT, narrrfs_status TEXT, raw_request_json TEXT, expires_at TEXT, transaction_payload TEXT, transaction_payload_hash TEXT, lookup_table_account TEXT, raw_response_json TEXT, error_message TEXT, updated_at TEXT)');
  $p->exec("CREATE TABLE tbl_spoinc_bridge_routes (route_key TEXT); INSERT INTO tbl_spoinc_bridge_routes VALUES ('SPOINC_TO_DSPOINC')");
  $GLOBALS['fixturePdo'] = $p; return $p;
}
function spoinc_bridge_require_public_route_access($pdo,$data,$route,$settlement) { return 'fixture-user'; }
function spoinc_bridge_is_internal_tester_user($user) { return true; }
function spoinc_bridge_is_valid_solana_address($value) { return is_string($value) && $value !== ''; }
function spoinc_bridge_load_config($pdo) { return ['project_id'=>'fixture-project','token_mint'=>'fixture-mint','api_base_url'=>'https://fixture.invalid']; }
function fixture_gensuki_post($request) {
  $GLOBALS['fixtureForwarded']=$request;
  $v=getenv('FIXTURE_RETURN_VERSION');
  $wireVersion=getenv('FIXTURE_WIRE_VERSION') ?: $v;
  $header=chr(1).str_repeat(chr(0),2); $key=str_repeat(chr(0),32); $blockhash=str_repeat(chr(3),32); $signature=str_repeat(chr(0),64);
  $wire=base64_encode($wireVersion === 'v1'
      ? chr(129).$header.str_repeat(chr(0),4).$blockhash.chr(0).chr(1).$key.$signature
      : chr(1).$signature.chr(128).$header.chr(1).$key.$blockhash.chr(0).chr(0));
  $json=['success'=>true, 'transaction'=>$wire];
  if ($v !== 'missing') { $json['transactionVersion']=$v; }
  return ['success'=>true,'http_code'=>200,'json'=>$json,'raw_body'=>'','error'=>''];
}
`;
try {
  fs.writeFileSync(path.join(temp, 'bridge-helpers.php'), stub);
  fs.writeFileSync(path.join(temp, 'bridge-tester-helpers.php'), '<?php\n');
  fs.copyFileSync(path.join(endpointDir, 'transaction-version.php'), path.join(temp, 'transaction-version.php'));
  fs.copyFileSync(path.join(endpointDir, 'transaction-binding.php'), path.join(temp, 'transaction-binding.php'));
  for (const file of files) {
    let source = fs.readFileSync(path.join(endpointDir, file), 'utf8');
    source = replaceOnce(source, '$apiKey = spoinc_bridge_get_gensuki_outbound_api_key();', "$apiKey = 'fixture-key';");
    source = replaceOnce(source, '$blockhashResult = spoinc_bridge_fetch_latest_blockhash();', "$blockhashResult = ['success'=>true,'blockhash'=>'fixture-blockhash','last_valid_block_height'=>1];");
    const requestVar = file.includes('create-gensuki-buy-') ? '$gensukiBuyRequest' : '$gensukiClaimRequest';
    source = replaceOnce(source, `spoinc_bridge_post_gensuki_json($gensukiEndpoint, $apiKey, ${requestVar})`, `fixture_gensuki_post(${requestVar})`);
    fs.writeFileSync(path.join(temp, file), source);
  }
  function run(file, request, returned, wireVersion = '') {
    const env = { SystemRoot: process.env.SystemRoot, PATH: process.env.PATH, FIXTURE_REQUEST: JSON.stringify(request), FIXTURE_RETURN_VERSION: returned, FIXTURE_WIRE_VERSION: wireVersion };
    const result = spawnSync(php, [path.join(temp, file)], { encoding: 'utf8', env });
    if (result.status !== 0) throw Error(result.stderr || result.stdout);
    try { return JSON.parse(result.stdout); } catch (_) { throw Error('Invalid fixture output: ' + result.stdout + result.stderr); }
  }
  for (const file of files) {
    const base = file.includes('create-gensuki-buy-') ? { wallet: '11111111111111111111111111111111', payment_amount: '1' } : { wallet: '11111111111111111111111111111111', amount: '1' };
    for (const version of ['v0', 'v1']) {
      const result = run(file, { ...base, transactionVersion: version }, version);
      assert.strictEqual(result.status, 200, JSON.stringify(result));
      assert.strictEqual(result.forwarded.transactionVersion, version);
      assert.strictEqual(result.payload.data.transactionVersion, version);
      assert.strictEqual(JSON.parse(result.rows[0].raw_request_json)[file.includes('create-gensuki-buy-') ? 'gensuki_buy_request' : 'gensuki_claim_request'].transactionVersion, version);
    }
    const defaulted = run(file, base, 'missing');
    assert.strictEqual(defaulted.status, 200, JSON.stringify(defaulted));
    assert.strictEqual(defaulted.forwarded.transactionVersion, 'v0');
    const mismatched = run(file, { ...base, transactionVersion: 'v1' }, 'v0');
    assert.strictEqual(mismatched.status, 502, JSON.stringify(mismatched));
    assert.strictEqual(mismatched.payload.data.transaction, '');
    assert.match(mismatched.rows[0].status, /pending/);
    const missing = run(file, { ...base, transactionVersion: 'v1' }, 'missing');
    assert.strictEqual(missing.status, 502, JSON.stringify(missing));
    assert.match(missing.rows[0].status, /pending/);
    for (const [requested, wire] of [['v0','v1'],['v1','v0']]) {
      const wrongWire = run(file, { ...base, transactionVersion: requested }, requested, wire);
      assert.strictEqual(wrongWire.status, 502, JSON.stringify(wrongWire));
      assert.match(wrongWire.rows[0].status, /pending/);
      assert.strictEqual(wrongWire.payload.data.transaction, '');
    }
    const invalid = run(file, { ...base, transactionVersion: ['v1'] }, 'v1');
    assert.strictEqual(invalid.status, 400, JSON.stringify(invalid));
    assert.strictEqual(invalid.rows.length, 0);
    assert.strictEqual(invalid.forwarded, null);
    console.log('PASS ' + file + ': V0, V1, default, mismatch, missing and invalid');
  }
} finally {
  fs.rmSync(temp, { recursive: true, force: true });
}