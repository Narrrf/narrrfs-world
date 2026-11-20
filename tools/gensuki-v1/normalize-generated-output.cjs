// Keep the generated browser asset deterministic and compatible with git diff --check.
const fs = require('fs');
const path = require('path');

const outputPath = path.join(__dirname, '../../public/js/gensuki-v1-codec.js');
const source = fs.readFileSync(outputPath, 'utf8');
const normalized = source.replace(/[\t ]+(?=\r?\n)/g, '').replace(/\r\n/g, '\n');

fs.writeFileSync(outputPath, normalized, 'utf8');
