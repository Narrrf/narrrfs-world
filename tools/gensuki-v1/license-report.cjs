// Gather notices from the exact packages included in the browser bundle.
const fs = require('fs');
const path = require('path');
const esbuild = require('esbuild');
const result = esbuild.buildSync({
  absWorkingDir: __dirname, entryPoints: ['src.js'], bundle: true, write: false,
  platform: 'browser', format: 'iife', globalName: 'NarrrfsGensukiV1',
  minify: true, legalComments: 'eof', outfile: 'codec.js', metafile: true
});
const roots = new Set();
for (const name of Object.keys(result.metafile.inputs)) {
  const parts = name.replace(/\\/g, '/').split('/');
  const index = parts.lastIndexOf('node_modules');
  if (index < 0 || index + 1 >= parts.length) continue;
  const end = parts[index + 1].startsWith('@') ? index + 3 : index + 2;
  roots.add(parts.slice(0, end).join('/'));
}
const entries = new Map();
for (const relative of roots) {
  const directory = path.resolve(__dirname, relative);
  const pkg = JSON.parse(fs.readFileSync(path.join(directory, 'package.json'), 'utf8'));
  const files = fs.readdirSync(directory).filter(name => /^(licen[cs]e|copying|notice)(\.|$|-)/i.test(name)).sort();
  // bs58 4.0.1 publishes its MIT declaration and credits in README.md, with no LICENSE file.
  if (!files.length && pkg.name === 'bs58' && pkg.version === '4.0.1') files.push('README.md');
  if (!files.length) throw Error(`No license notice found for ${pkg.name}@${pkg.version}`);
  const key = `${pkg.name}@${pkg.version}`;
  let text = `\n===== ${key} (${pkg.license || 'see notices'}) =====\n`;
  for (const name of files) text += `\n--- ${name} ---\n${fs.readFileSync(path.join(directory, name), 'utf8')}\n`;
  if (entries.has(key) && entries.get(key) !== text) throw Error(`Conflicting notices for ${key}`);
  entries.set(key, text);
}
let output = 'Third-party notices for gensuki-v1-codec.js\nGenerated from the exact bundled package inputs.\n';
for (const key of [...entries.keys()].sort()) output += entries.get(key);
fs.writeFileSync(path.join(__dirname, '../../public/js/gensuki-v1-codec.licenses.txt'), output.replace(/\n+$/, '\n'), 'utf8');
console.log(`Wrote notices for ${entries.size} bundled packages.`);
