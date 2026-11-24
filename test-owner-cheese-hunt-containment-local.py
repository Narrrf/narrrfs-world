"""Check Apache containment expressions for exact Cheese Hunt Owner Control routes and legacy denial."""
import pathlib
import re

text = (pathlib.Path(__file__).resolve().parent / 'deploy/apache/narrrfs-emergency-containment.conf').read_text(encoding='utf-8')
expressions = re.findall(r'<LocationMatch "([^"]+)">\s*Require all denied\s*</LocationMatch>', text)
assert expressions, 'containment directives not found'


def denied(path):
    return any(re.search(expr, path) for expr in expressions)


for name in ('overview.php', 'csrf.php', 'mission.php', 'claim-review.php'):
    route = '/api/owner-control/v1/cheese-hunt/' + name
    assert not denied(route), f'expected allowed: {route}'
    assert denied(route + '/extra'), f'expected path-info denied: {route}/extra'
    assert denied(route + 'x'), f'expected suffix denied: {route}x'

for route in (
    '/api/owner-control/v1/cheese-hunt/other.php',
    '/admin-interface.html',
    '/api/admin/create-quest.php',
    '/api/track-egg-click.php',
):
    assert denied(route), f'expected denied: {route}'

print('PASS: exact Cheese Hunt Owner Control exceptions and legacy/neighboring denial')
