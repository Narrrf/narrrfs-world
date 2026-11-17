'use strict';
// Verify published-offer links use existing bounded pagination, including the first offer.
const fs = require('fs'), vm = require('vm'), assert = require('assert');
const source = fs.readFileSync(__dirname + '/owner-control/v1/lab-store-live-offers.php', 'utf8');
const load = source.split('\n').find(line => line.startsWith('async function load(next)'));
assert(load, 'live offers loader exists');
async function check(after, expectedUrl, expectedTarget) {
  let requested, scrolled = false;
  const context = {initialAfter: after, cursor: null, location: {}, status: {}, more: {},
    cards: {replaceChildren() {}, append() {}}, valid: () => true, card: () => ({}),
    document: {createDocumentFragment: () => ({append() {}}), getElementById: id => {
      assert.equal(id, expectedTarget); return {scrollIntoView() {scrolled = true;}};
    }}, fetch: async url => { requested = url; return {ok: true, json: async () => ({success: true, writes: false, page: {next_cursor: null}, offers: [{}]})}; }};
  vm.createContext(context); vm.runInContext(load, context); await context.load(false);
  assert.equal(requested, expectedUrl); assert(scrolled, 'newly rendered offer was scrolled into view');
}
(async () => {
  await check('0', '/api/owner-control/v1/lab-store/live-offers.php?limit=25', 'offer-1');
  await check('6', '/api/owner-control/v1/lab-store/live-offers.php?limit=25&cursor=6', 'offer-7');
  console.log('PASS: published-offer bounded navigation and asynchronous scroll');
})().catch(error => {console.error(error); process.exitCode = 1;});
