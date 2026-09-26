<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mouse-lp-store-eligibility.php';
require_once __DIR__ . '/mouse-lp-store-delivery.php';

function mouse_lp_store_session_user(): string { return trim((string)($_SESSION['discord_id'] ?? '')); }
function mouse_lp_store_input(): array { $data = json_decode(file_get_contents('php://input'), true); return is_array($data) ? $data : []; }
function mouse_lp_store_receipt(array $row): array { return ['purchase_id'=>(int)$row['purchase_id'],'offer_id'=>(int)$row['offer_id'],'source_type'=>$row['source_type'],'source_item_id'=>(int)$row['source_item_id'],'item_title'=>$row['item_title_snapshot'],'payment_method'=>$row['payment_method'],'amount_paid'=>(int)$row['amount_paid'],'mouse'=>$row['payment_method']==='lp'?['token_id'=>$row['selected_token_id'],'collection'=>$row['selected_collection'],'custom_name'=>$row['mouse_name_snapshot']]:null,'delivery_reference_id'=>(int)$row['delivery_reference_id'],'completed_at'=>$row['completed_at']]; }
function mouse_lp_store_fingerprint(string $user, int $offer, string $payment, ?string $token): string { return hash('sha256', json_encode([$user,$offer,$payment,$payment === 'lp' ? $token : null], JSON_UNESCAPED_SLASHES)); }
function mouse_lp_store_available_dspoinc(PDO $pdo, string $userId): int {
    $stmt=$pdo->prepare("SELECT COALESCE((SELECT SUM(score) FROM tbl_user_scores WHERE user_id=?),0)-COALESCE((SELECT SUM(amount) FROM tbl_dspoinc_stakes WHERE user_id=? AND status IN ('active','frozen')),0)"); $stmt->execute([$userId,$userId]); return (int)$stmt->fetchColumn();
}
function mouse_lp_store_purchase(PDO $pdo, string $userId, array $input): array {
    $offerId=(int)($input['offer_id']??0); $payment=trim((string)($input['payment_method']??'')); $key=trim((string)($input['idempotency_key']??'')); $token=trim((string)($input['token_id']??'')); $collection=strtolower(trim((string)($input['collection']??'')));
    if ($offerId<1 || !in_array($payment,['lp','dspoinc'],true) || $key==='' || strlen($key)>200 || ($payment==='lp' && ($token==='' || $collection!=='genesis'))) throw new InvalidArgumentException('Invalid purchase request.');
    if ($payment==='dspoinc') { $token=''; $collection=''; }
    $fp=mouse_lp_store_fingerprint($userId,$offerId,$payment,$token ?: null); $pdo->exec('BEGIN IMMEDIATE');
    try {
        $offer=$pdo->prepare('SELECT * FROM tbl_mouse_lp_store_offers WHERE offer_id=? AND is_active=1'); $offer->execute([$offerId]); $offer=$offer->fetch(PDO::FETCH_ASSOC); if (!$offer) throw new RuntimeException('Offer is unavailable.');
        $old=$pdo->prepare('SELECT * FROM tbl_mouse_lp_store_purchases WHERE purchaser_user_id=? AND idempotency_key=?'); $old->execute([$userId,$key]); $row=$old->fetch(PDO::FETCH_ASSOC);
        if ($row) { if (!hash_equals((string)$row['request_fingerprint'],$fp)) throw new DomainException('idempotency_conflict'); $pdo->exec('COMMIT'); return ['replayed'=>true,'receipt'=>mouse_lp_store_receipt($row)]; }
        $source=mouse_lp_store_load_source($pdo,$offer['source_type'],(int)$offer['source_item_id']); if (!$source) throw new RuntimeException('Offer source is unavailable.');
        if ($offer['source_type'] === 'genetic_item' && !mouse_lp_store_can_deliver_genetic($pdo, $userId, (int)$offer['source_item_id'])) throw new RuntimeException('You already own the maximum 2 copies of this genetic trait.');
        $price=$payment==='lp' ? $offer['lp_price'] : $offer['dspoinc_price']; if ($price===null || (int)$price<1) throw new RuntimeException('Payment method is unavailable.'); $price=(int)$price;
        $mouse=null; if ($payment==='lp') { $mouse=mouse_lp_store_named_mouse($pdo,$userId,$token); if (!$mouse) throw new RuntimeException('Selected mouse is no longer eligible.'); $season=mouse_lp_store_active_season($pdo); if (mouse_lp_store_totals($pdo,$token,(int)$season['season_id'])['spendable_lp']<$price) throw new RuntimeException('Insufficient spendable League Points.'); }
        else if (mouse_lp_store_available_dspoinc($pdo,$userId)<$price) throw new RuntimeException('Insufficient available DSPOINC.');
        $provisional=$pdo->prepare("INSERT INTO tbl_mouse_lp_store_purchases (purchaser_user_id,idempotency_key,request_fingerprint,offer_id,source_type,source_item_id,item_title_snapshot,payment_method,amount_paid,selected_token_id,selected_collection,mouse_name_snapshot,delivery_reference_id,completed_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NULL,CURRENT_TIMESTAMP)");
        $provisional->execute([$userId,$key,$fp,$offerId,$offer['source_type'],$offer['source_item_id'],$source['title'],$payment,$price,$payment==='lp'?$token:null,$payment==='lp'?'genesis':null,$payment==='lp'?trim((string)$mouse['custom_name']):null]); $purchaseId=(int)$pdo->lastInsertId();
        $stock=$pdo->prepare('UPDATE tbl_mouse_lp_store_offers SET stock=stock-1, updated_at=CURRENT_TIMESTAMP WHERE offer_id=? AND is_active=1 AND stock>0'); $stock->execute([$offerId]); if ($stock->rowCount()!==1) throw new RuntimeException('Offer is out of stock.');
        if ($payment==='lp') { $spend=$pdo->prepare("INSERT INTO tbl_mousefight_league_point_spends (purchase_id,purchaser_user_id,token_id,collection,points_spent,spent_at) VALUES (?,?,?,'genesis',?,CURRENT_TIMESTAMP)"); $spend->execute([$purchaseId,$userId,$token,$price]); }
        else { $score=$pdo->prepare("INSERT INTO tbl_user_scores (user_id,score,game,source,timestamp) VALUES (?,?,'store_purchase','store',CURRENT_TIMESTAMP)"); $score->execute([$userId,-$price]); $adjust=$pdo->prepare("INSERT INTO tbl_score_adjustments (user_id,admin_id,amount,action,reason,timestamp) VALUES (?,?,?,'remove',?,CURRENT_TIMESTAMP)"); $adjust->execute([$userId,$userId,-$price,'Mouse LP Store purchase: '.$source['title'].' [purchase #'.$purchaseId.']']); }
        $delivery=$offer['source_type']==='normal_store'?mouse_lp_store_deliver_normal($pdo,$userId,(int)$offer['source_item_id']):mouse_lp_store_deliver_genetic($pdo,$userId,(int)$offer['source_item_id']);
        $finish=$pdo->prepare('UPDATE tbl_mouse_lp_store_purchases SET delivery_reference_id=? WHERE purchase_id=?'); $finish->execute([$delivery,$purchaseId]); $row=$pdo->prepare('SELECT * FROM tbl_mouse_lp_store_purchases WHERE purchase_id=?'); $row->execute([$purchaseId]); $row=$row->fetch(PDO::FETCH_ASSOC); $pdo->exec('COMMIT'); return ['replayed'=>false,'receipt'=>mouse_lp_store_receipt($row)];
    } catch(Throwable $e) { try{$pdo->exec('ROLLBACK');}catch(Throwable $ignore){} throw $e; }
}
