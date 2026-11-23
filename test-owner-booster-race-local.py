"""Exercise the real Lab endpoint and owner helper using disposable SQLite."""
import json, pathlib, shutil, sqlite3, subprocess, tempfile, time
ROOT=pathlib.Path(__file__).resolve().parent
PHP=r'C:\xampp-server\php\php.exe'
USER='111111111111111111'
def create(root):
    for d in ('api/user','api/config','api/lib','db'): (root/d).mkdir(parents=True,exist_ok=True)
    for f in ('api/user/use-lab-booster.php','api/user/use-genetic-item-booster.php','api/config/database.php','api/lib/owner-player-adjustments-v1.php'): shutil.copyfile(ROOT/f,root/f)
    p=root/'db/narrrf_world.sqlite'; db=sqlite3.connect(p)
    db.executescript('''CREATE TABLE tbl_users(discord_id TEXT PRIMARY KEY);
    CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY,item_name TEXT,is_active INTEGER);
    CREATE TABLE tbl_user_inventory(inventory_id INTEGER PRIMARY KEY,user_id TEXT,item_id INTEGER,quantity INTEGER,acquired_at TEXT,last_used_at TEXT, UNIQUE(user_id,item_id));
    CREATE TABLE tbl_nft_ownership(user_id TEXT,token_id TEXT,collection TEXT,is_verified INTEGER,traits_json TEXT);
    CREATE TABLE tbl_nft_trait_upgrades(upgrade_id INTEGER PRIMARY KEY,token_id TEXT,collection TEXT,trait_type TEXT,trait_value TEXT,upgrade_status TEXT,upgrade_ends_at TEXT,active_booster_item_id INTEGER,active_booster_used_at TEXT,updated_at TEXT);
    CREATE TABLE tbl_item_usage_history(user_id TEXT,item_id INTEGER,item_name TEXT,quantity INTEGER,reason TEXT,status TEXT,approved_by TEXT,used_at TEXT,approved_at TEXT);
    INSERT INTO tbl_users VALUES('111111111111111111'),('222222222222222222');
    INSERT INTO tbl_store_items VALUES(33,'Green Elixir',1);
    INSERT INTO tbl_user_inventory(user_id,item_id,quantity) VALUES('111111111111111111',33,3);
    INSERT INTO tbl_nft_ownership VALUES('111111111111111111','test-mouse','genesis',1,'[{"trait_type":"Fur","trait_value":"Blue"}]');
    INSERT INTO tbl_nft_trait_upgrades VALUES(1,'test-mouse','genesis','Fur','Blue','upgrading','2099-01-01 00:00:00',NULL,NULL,NULL);''')
    db.executescript((ROOT/'db/migrations/20261123_owner_control_player_adjustments_v1.sql').read_text());db.commit();db.close();return p
def spawn(root, code): return subprocess.Popen([PHP,'-r',code],cwd=root,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,encoding='utf-8',errors='replace')
def use(root): return spawn(root,"$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_METHOD']='POST';$_POST=['user_id'=>'"+USER+"','token_id'=>'test-mouse','trait_type'=>'Fur','trait_value'=>'Blue','item_id'=>33];require 'api/user/use-lab-booster.php';")
def add(root): return spawn(root,"require 'api/lib/owner-player-adjustments-v1.php';$db=new SQLite3('db/narrrf_world.sqlite');$p=['user_id'=>'"+USER+"','action'=>'add','amount'=>2,'item_id'=>33,'reason'=>'fixture reason','idempotency_key'=>'fixture_owner_key_0001'];try{$r=owner_player_adjust($db,$p,'222222222222222222','inventory');echo json_encode(['success'=>true]+$r);}catch(Throwable $e){echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}")
def result(p):
    out,err=p.communicate(timeout=20);assert p.returncode==0,(out,err);return json.loads(out)
def verify(db):
    assert db.execute('SELECT quantity FROM tbl_user_inventory').fetchone()[0]==4
    assert db.execute('SELECT COUNT(*) FROM tbl_item_usage_history').fetchone()[0]==1
    assert db.execute('SELECT COUNT(*) FROM tbl_owner_player_adjustment_audit').fetchone()[0]==1
    assert db.execute('SELECT COUNT(*) FROM tbl_owner_player_adjustment_receipts').fetchone()[0]==1
for order in ('use-first','add-first','concurrent'):
    with tempfile.TemporaryDirectory(prefix='booster-fixture-') as tmp:
        root=pathlib.Path(tmp);p=create(root);db=sqlite3.connect(p,timeout=10)
        if order=='use-first': assert result(use(root))['success'];assert result(add(root))['success']
        elif order=='add-first': assert result(add(root))['success'];assert result(use(root))['success']
        else:
            db.execute('BEGIN IMMEDIATE');a=use(root);b=add(root);time.sleep(.5);assert a.poll() is None and b.poll() is None
            db.commit(); ar=result(a);br=result(b);assert ar['success'] and br['success'],(ar,br)
        verify(db);db.close();print('PASS: actual Lab booster and owner adjustment',order)
with tempfile.TemporaryDirectory(prefix='booster-fixture-') as tmp:
    root=pathlib.Path(tmp);p=create(root);db=sqlite3.connect(p)
    db.execute('UPDATE tbl_user_inventory SET quantity=0');db.commit();assert not result(use(root))['success']
    db.execute('UPDATE tbl_user_inventory SET quantity=3');db.execute("CREATE TRIGGER fail_usage BEFORE INSERT ON tbl_item_usage_history BEGIN SELECT RAISE(ABORT,'forced'); END");db.commit()
    assert not result(use(root))['success'];assert db.execute('SELECT quantity FROM tbl_user_inventory').fetchone()[0]==3
    assert db.execute('SELECT upgrade_ends_at FROM tbl_nft_trait_upgrades').fetchone()[0]=='2099-01-01 00:00:00'
    db.close();print('PASS: insufficient quantity and forced usage failure roll back')
with tempfile.TemporaryDirectory(prefix='booster-fixture-') as tmp:
    root=pathlib.Path(tmp);p=create(root);assert result(use(root))['success'];assert result(use(root))['success']
    db=sqlite3.connect(p);assert db.execute('SELECT quantity FROM tbl_user_inventory').fetchone()[0]==1
    assert db.execute('SELECT COUNT(*) FROM tbl_item_usage_history').fetchone()[0]==2
    db.close();print('OBSERVED: identical sequential booster requests each consume an item; endpoint has no idempotency key')
with tempfile.TemporaryDirectory(prefix='booster-fixture-') as tmp:
    root=pathlib.Path(tmp);p=create(root);db=sqlite3.connect(p,timeout=10)
    db.execute('BEGIN IMMEDIATE');a=use(root);b=use(root);time.sleep(.5);assert a.poll() is None and b.poll() is None
    db.commit();assert result(a)['success'];assert result(b)['success']
    assert db.execute('SELECT quantity FROM tbl_user_inventory').fetchone()[0]==1
    assert db.execute('SELECT upgrade_ends_at FROM tbl_nft_trait_upgrades').fetchone()[0]=='2098-12-31 12:00:00'
    db.close();print('PASS: concurrent Lab uses preserve both quantity decrements and timer reductions')

def genetic_fixture(db):
    db.executescript('''CREATE TABLE tbl_holder_verifications(user_id TEXT,collection TEXT,nft_count INTEGER,verified_at TEXT);
    INSERT INTO tbl_holder_verifications VALUES('111111111111111111','genesis',1,'2026-01-01');
    CREATE TABLE tbl_user_genetic_items(genetic_item_id INTEGER PRIMARY KEY,user_id TEXT,catalog_id INTEGER,trait_type TEXT,trait_value TEXT,current_level INTEGER,upgrade_status TEXT,upgrade_started_at TEXT,upgrade_ends_at TEXT,last_completed_at TEXT,ready_claim_notified_at TEXT,ready_claim_notification_count INTEGER,active_booster_item_id INTEGER,active_booster_used_at TEXT,acquired_method TEXT,is_listed_for_sale INTEGER,listed_listing_id INTEGER,created_at TEXT,updated_at TEXT,last_transfer_at TEXT,last_owner_user_id TEXT);
    CREATE TABLE tbl_genetic_trait_catalog(catalog_id INTEGER,display_title TEXT,description TEXT,rarity_tier TEXT,rarity_count INTEGER,base_price_dspoinc INTEGER,image_type TEXT,image_path TEXT,preview_path TEXT,source_origin TEXT,effect_metadata_json TEXT,is_active INTEGER,is_visible INTEGER);
    CREATE TABLE tbl_genetic_item_history(genetic_item_id INTEGER,listing_id INTEGER,user_id TEXT,action_type TEXT,old_value_json TEXT,new_value_json TEXT,admin_user_id TEXT,created_at TEXT);
    INSERT INTO tbl_user_genetic_items(genetic_item_id,user_id,catalog_id,trait_type,trait_value,current_level,upgrade_status,upgrade_ends_at,is_listed_for_sale) VALUES(1,'111111111111111111',1,'Fur','Blue',1,'upgrading','2099-01-01 00:00:00',0);
    INSERT INTO tbl_genetic_trait_catalog(catalog_id,display_title) VALUES(1,'Fixture');''');db.commit()
def genetic(root): return spawn(root,"$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_METHOD']='POST';$_POST=['user_id'=>'"+USER+"','genetic_item_id'=>1,'item_id'=>33];require 'api/user/use-genetic-item-booster.php';")
for order in ('use-first','add-first','concurrent'):
    with tempfile.TemporaryDirectory(prefix='genetic-fixture-') as tmp:
        root=pathlib.Path(tmp);p=create(root);db=sqlite3.connect(p,timeout=10);genetic_fixture(db)
        if order=='use-first': assert result(genetic(root))['success'];assert result(add(root))['success']
        elif order=='add-first': assert result(add(root))['success'];assert result(genetic(root))['success']
        else:
            db.execute('BEGIN IMMEDIATE');a=genetic(root);b=add(root);time.sleep(.5);db.commit();ar=result(a);br=result(b)
            assert br['success'],(ar,br)
            if not ar['success']:
                assert db.execute('SELECT quantity FROM tbl_user_inventory').fetchone()[0]==5,(ar,br)
                assert db.execute('SELECT COUNT(*) FROM tbl_item_usage_history').fetchone()[0]==0
                db.close();print('PASS: genetic contention fails without inventory loss',ar.get('error'));continue
        verify(db);db.close();print('PASS: actual Genetic booster and owner adjustment',order)
