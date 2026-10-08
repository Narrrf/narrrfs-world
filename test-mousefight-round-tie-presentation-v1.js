'use strict';

/**
 * MouseFight score-tie presentation regression coverage.
 *
 * This test extracts only presentation helpers. It does not start Discord,
 * call APIs, write SQLite, or alter resolved MouseFight history.
 */

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = __dirname;
const mousefightPath = path.join(root, 'discord', 'commands', 'mousefight.js');
const publicPath = path.join(root, 'public', 'mousefights.html');
const mousefightSource = fs.readFileSync(mousefightPath, 'utf8');
const publicSource = fs.readFileSync(publicPath, 'utf8');

function extractedFunction(source, name, nextSignature) {
    const start = source.indexOf(`function ${name}(`);
    const end = source.indexOf(nextSignature, start);

    assert(start >= 0 && end > start, `Could not extract ${name}.`);
    return source.slice(start, end);
}

function evaluate(functionSource, name, context = {}) {
    const sandbox = { ...context };
    vm.createContext(sandbox);
    vm.runInContext(`${functionSource}; this.result = ${name};`, sandbox);
    return sandbox.result;
}

const buildScoreTieText = evaluate(
    extractedFunction(
        mousefightSource,
        'buildMouseFightScoreTieText',
        'function buildMouseFightRoundText('
    ),
    'buildMouseFightScoreTieText'
);

const attacker = { mouseProfile: { customName: 'Don Parmesan' } };

// Equal scores retain the existing attacker-wins rule and explain it.
const tieText = buildScoreTieText({
    attacker,
    attackerScore: 176,
    defenderScore: 176
});
assert.match(tieText, /Score Tie: 176–176/);
assert.match(tieText, /Don Parmesan.*round attacker/);

// Normal higher-score rounds remain free of tie text.
assert.strictEqual(buildScoreTieText({ attacker, attackerScore: 177, defenderScore: 176 }), null);
assert.strictEqual(buildScoreTieText({ attacker, attackerScore: 175, defenderScore: 176 }), null);

// Initiative is deliberately absent from the helper: a defender initiative
// advantage must not be presented as a score-tie breaker.
assert.doesNotMatch(tieText, /initiative/i);

assert.match(
    mousefightSource,
    /attackerScore >= defenderScore[\s\S]*?\? attacker[\s\S]*?: defender/
);
assert.match(
    mousefightSource,
    /round\.winner\?\.id === round\.attacker\.id \? 'attacker_win' : 'defender_win'/
);
assert.match(mousefightSource, /round\.eventText \|\| null/);

const publicTieNotice = evaluate(
    extractedFunction(publicSource, 'roundScoreTieNotice', 'async function openFight('),
    'roundScoreTieNotice',
    {
        mouseName: fighter => fighter.username || fighter.mouse_name || 'Unknown Mouse',
        number: value => Number(value || 0)
    }
);

assert.match(
    publicTieNotice({
        attacker_score: 176,
        defender_score: 176,
        attacker: { username: 'Don Parmesan' },
        event_text: '⚖️ **Score Tie: 176–176**'
    }),
    /Don Parmesan wins as the round attacker/
);
assert.strictEqual(
    publicTieNotice({ attacker_score: 177, defender_score: 176, attacker: { username: 'Don Parmesan' }, event_text: '' }),
    ''
);

console.log('MouseFight round tie presentation tests passed.');
