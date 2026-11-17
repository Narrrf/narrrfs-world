'use strict';
// History filtering is local to the existing bounded owner-only response.
const fs=require('fs'), vm=require('vm'), assert=require('assert');
const source=fs.readFileSync(__dirname+'/owner-control/v1/lab-store-drafts.php','utf8');
assert(source.includes('latest 50') && source.includes('Filter applies only to loaded events.'));
const line=source.split('\n').find(x=>x.startsWith('let historyEvents=[],'));
assert(line,'history rendering and loading exist');
const filter={value:''}, output={items:[],replaceChildren(){this.items=[];},append(e){this.items.push(e.textContent);},textContent:''};
const ctx={publishEnabled:true,document:{getElementById:id=>id==='history_filter'?filter:id==='history_result'?output:{},createElement:()=>({textContent:''})}};
vm.createContext(ctx);vm.runInContext(line,ctx);
output.textContent='Not loaded';filter.value='22';ctx.renderHistory();assert.equal(output.textContent,'Not loaded','filter does not imply history was loaded');
vm.runInContext("historyEvents=[{offer_id:22,action:'published',created_at:'date'},{offer_id:22,action:'disabled',created_at:'date'},{offer_id:21,action:'price_stock_changed',created_at:'date'}];historyState='loaded'",ctx);
filter.value='22';ctx.renderHistory();assert.equal(output.items.length,2);
filter.value='price_stock';ctx.renderHistory();assert.equal(output.items.length,1);assert(output.items[0].includes('offer #21'));
filter.value='missing';ctx.renderHistory();assert.equal(output.textContent,'No matching loaded events.');
vm.runInContext("historyState='error'",ctx);output.textContent='History unavailable.';filter.value='22';ctx.renderHistory();assert.equal(output.textContent,'History unavailable.','filter cannot hide a fetch error');
console.log('PASS: bounded local history filter by offer and action');
