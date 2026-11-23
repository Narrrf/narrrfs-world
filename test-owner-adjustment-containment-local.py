"""Check the Apache expressions against exact allowed and denied paths."""
import pathlib,re
text=(pathlib.Path(__file__).resolve().parent/'deploy/apache/narrrfs-emergency-containment.conf').read_text()
expressions=re.findall(r'<LocationMatch "([^"]+)">\s*Require all denied\s*</LocationMatch>',text)
assert expressions, 'containment directives not found'
def denied(path): return any(re.search(expr,path) for expr in expressions)
for name in ('player-adjustment-csrf.php','player-points-adjust.php','player-inventory-adjust.php'):
    route='/api/owner-control/v1/'+name
    assert not denied(route),route
    assert denied(route+'/extra'),route
    assert denied(route+'x'),route
for route in ('/api/owner-control/v1/player-adjust.php','/api/admin/point-management.php','/api/admin/manage-inventory.php'):
    assert denied(route),route
print('PASS: exact owner adjustment exceptions and neighboring owner/admin denial')
