'use strict';
// The publish review may normalize only same-origin catalog image references.
const fs = require('fs'), vm = require('vm'), assert = require('assert');
const text = fs.readFileSync(__dirname + '/owner-control/v1/lab-store-drafts.php', 'utf8');
const fn = text.split('\n').find(line => line.startsWith('function reviewImage('));
assert(fn, 'review image function exists');
let image;
const ctx = {document: {getElementById() {return image;}}};
vm.createContext(ctx); vm.runInContext(fn, ctx);
function check(ref, expected) {
  let hidden = false;
  image = {classList: {add() {hidden=true;}, remove() {hidden=false;}}, removeAttribute() {this.src=null;}};
  ctx.reviewImage('catalog', ref);
  assert.equal(hidden, expected === null, String(ref));
  assert.equal(image.src || null, expected, String(ref));
}
check('img/space/cheese-bullet.png', '/public/img/space/cheese-bullet.png');
check('/public/img/lab/blue_elixir.png', '/public/img/lab/blue_elixir.png');
check(null, null); check('img/../secret.png', null); check('https://example.org/a.png', null);
check('img/a.png?x=1', null); check('img/%2e%2e/a.png', null); check('//example.org/a.png', null);
console.log('PASS: same-origin catalog preview normalization and rejection');
