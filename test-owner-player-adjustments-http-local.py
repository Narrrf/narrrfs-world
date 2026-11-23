"""Real HTTP endpoints against a temporary DB and synthetic local sessions."""
import importlib.util,json,os,pathlib,shutil,socket,sqlite3,subprocess,tempfile,time,urllib.request,urllib.error
ROOT=pathlib.Path(__file__).resolve().parent;PHP=r'C:\xampp-server\php\php.exe'
spec=importlib.util.spec_from_file_location('stake_fixture',ROOT/'test-create-stake-transaction-local.py');fixture=importlib.util.module_from_spec(spec);spec.loader.exec_module(fixture)
with tempfile.TemporaryDirectory(prefix='adjust-http-fixture-') as tmp:
    root=pathlib.Path(tmp);path=fixture.setup(root);sessions=root/'sessions';sessions.mkdir()
    db=sqlite3.connect(path);db.execute('CREATE TABLE tbl_dspoinc_stakes(user_id TEXT, amount INTEGER, status TEXT)');db.execute('ALTER TABLE tbl_store_items ADD COLUMN item_name TEXT');db.execute("INSERT INTO tbl_store_items VALUES(33,1,'Green Elixir')");db.commit();db.close()
    for f in ('api/config/admin-authorization.php','api/config/admin-session.php','api/config/admin-route-policies.php','api/config/session.php','api/owner-control/v1/player-adjustment-csrf.php','api/owner-control/v1/player-points-adjust.php','api/owner-control/v1/player-inventory-adjust.php','owner-control/v1/player-adjustments.php'):
        (root/f).parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(ROOT/f,root/f)
    for sid,user in [('ownerfixture','222222222222222222'),('otherfixture','333333333333333333')]:
        code="session_id('"+sid+"');session_start();$n=time();$_SESSION=['discord_id'=>'"+user+"','auth_provenance'=>'discord_oauth_v1','narrrfs_admin_auth_issued_at'=>$n,'narrrfs_admin_auth_last_seen_at'=>$n,'narrrfs_admin_auth_expires_at'=>$n+43200];session_write_close();"
        subprocess.run([PHP,'-d','session.save_path='+str(sessions),'-r',code],check=True,capture_output=True)
    sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
    env=dict(os.environ,NARRRFS_ADMIN_DISCORD_IDS='222222222222222222',NARRRFS_OWNER_PLAYER_ADJUSTMENT_UI_ENABLED='1',NARRRFS_OWNER_PLAYER_ADJUSTMENT_CSRF_ENABLED='1',NARRRFS_OWNER_PLAYER_POINTS_ADJUST_ENABLED='1',NARRRFS_OWNER_PLAYER_INVENTORY_ADJUST_ENABLED='1')
    server=subprocess.Popen([PHP,'-d','session.save_path='+str(sessions),'-S',f'127.0.0.1:{port}','-t',str(root)],env=env,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    def call(path,body=None,sid=None,token=None,origin='http://localhost'):
        headers={'Origin':origin};data=None
        if body is not None: headers['Content-Type']='application/json';data=json.dumps(body).encode()
        if sid: headers['Cookie']='PHPSESSID='+sid
        if token: headers['X-CSRF-Token']=token
        req=urllib.request.Request(f'http://127.0.0.1:{port}'+path,data=data,headers=headers)
        try:
            with urllib.request.urlopen(req,timeout=5) as response:return response.status,response.read().decode()
        except urllib.error.HTTPError as e:return e.code,e.read().decode()
    try:
        for _ in range(50):
            try: socket.create_connection(('127.0.0.1',port),.1).close();break
            except OSError:time.sleep(.1)
        ui='/owner-control/v1/player-adjustments.php';csrf='/api/owner-control/v1/player-adjustment-csrf.php';points='/api/owner-control/v1/player-points-adjust.php'
        for sid,expected in [(None,401),('otherfixture',403),('ownerfixture',200)]:
            response=call(ui,sid=sid);assert response[0]==expected,(sid,response)
        assert call(csrf,{'action':'points'})[0]==401;assert call(csrf,{'action':'points'},'otherfixture')[0]==403
        assert call(csrf,{'action':'points'},'ownerfixture',origin='http://evil.invalid')[0]==403
        status,body=call(csrf,{'action':'points'},'ownerfixture');assert status==200,(status,body);token=json.loads(body)['csrf_token']
        payload={'user_id':fixture.USER,'action':'add','amount':5,'reason':'fixture reason','idempotency_key':'http_fixture_key_0001'}
        status,body=call(points,payload,'ownerfixture',token);assert status==200,(status,body);assert json.loads(body)['result']['after']==1005
        assert call(points,payload,'ownerfixture',token)[0]==403
        _,body=call(csrf,{'action':'points'},'ownerfixture');status,body=call(points,payload,'ownerfixture',json.loads(body)['csrf_token']);assert status==200 and json.loads(body)['result']['replayed']
        _,body=call(csrf,{'action':'points'},'ownerfixture');changed=dict(payload,amount=6);status,body=call(points,changed,'ownerfixture',json.loads(body)['csrf_token']);assert status==409 and json.loads(body)['error']=='idempotency_key_reused'
        inventory='/api/owner-control/v1/player-inventory-adjust.php'
        _,body=call(csrf,{'action':'inventory'},'ownerfixture');token=json.loads(body)['csrf_token']
        item={'user_id':fixture.USER,'action':'add','amount':2,'item_id':33,'reason':'fixture reason','idempotency_key':'http_fixture_key_0002'}
        status,body=call(inventory,item,'ownerfixture',token);assert status==200,(status,body);assert json.loads(body)['result']['after']==2
        _,body=call(csrf,{'action':'inventory'},'ownerfixture');status,body=call(inventory,item,'ownerfixture',json.loads(body)['csrf_token']);assert status==200 and json.loads(body)['result']['replayed']
        _,body=call(csrf,{'action':'inventory'},'ownerfixture');status,body=call(inventory,dict(item,amount=3),'ownerfixture',json.loads(body)['csrf_token']);assert status==409 and json.loads(body)['error']=='idempotency_key_reused'
        db=sqlite3.connect(path);assert db.execute('SELECT COUNT(*) FROM tbl_owner_player_adjustment_receipts').fetchone()[0]==2;assert db.execute('SELECT quantity FROM tbl_user_inventory').fetchone()[0]==2;db.close()
        print('PASS: real HTTP owner/nonowner/anonymous, Origin, token consumption, points, inventory and idempotent replay; synthetic sessions')
        if os.getenv('OWNER_UI_WAIT')=='1':
            (root/'fixture-signin.php').write_text("<?php setcookie('PHPSESSID','ownerfixture',['path'=>'/','httponly'=>true]);header('Location: /owner-control/v1/player-adjustments.php');",encoding='utf-8')
            print(f'BROWSER_FIXTURE=http://127.0.0.1:{port}/fixture-signin.php',flush=True)
            input('Press Enter to stop disposable fixture: ')
    finally:server.terminate();server.wait(timeout=5)
