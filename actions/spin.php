<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rewards.php';
$user = requireLogin(); requirePost('wheel.php');
$pdo=db();
try {
    reward_schema($pdo); $pdo->beginTransaction();
    $wallet=prize_wallet($pdo,(int)$user['id']);
    $lock=$pdo->prepare('SELECT balance FROM prize_wallets WHERE user_id=? FOR UPDATE');
    $lock->execute([(int)$user['id']]); $wallet=$lock->fetch();
    $count=(int)($_POST['count']??1);
    if (!in_array($count,[1,5,10],true)) throw new DomainException('Vui lòng chọn x1, x5 hoặc x10.');
    $cost=$count*5000;
    if ((int)$wallet['balance']<$cost) throw new DomainException('Số dư ví không đủ. Mỗi lượt quay cần 5.000 đ.');
    $pdo->prepare('UPDATE prize_wallets SET balance=balance-? WHERE user_id=?')->execute([$cost,(int)$user['id']]);
    $settings=prize_spin_odds();
    $outcomes=[];
    for($i=0;$i<$count;$i++) {
        $roll=random_int(1,10000);
        $miss=(int)$settings['miss_bp']; $coupon=(int)$settings['coupon_bp'];
        $type=$roll<=$miss?'miss':($roll<=$miss+$coupon?'coupon':'fish');
        $discount=$type==='coupon'?5:0; $rewardId=null;
        if($type!=='miss') {
            $st=$pdo->prepare('INSERT INTO prize_rewards(user_id,reward_type,discount_percent) VALUES(?,?,?)');
            $st->execute([(int)$user['id'],$type,$discount]); $rewardId=(int)$pdo->lastInsertId();
        }
        $pdo->prepare('INSERT INTO prize_spins(user_id,reward_id) VALUES(?,?)')->execute([(int)$user['id'],$rewardId]);
        $outcomes[]=['type'=>$type,'reward_id'=>$rewardId];
    }
    $pdo->commit();
    $_SESSION['prize_spin_result']=['outcomes'=>$outcomes,'count'=>$count];
} catch(Throwable $ex) {
    if($pdo->inTransaction())$pdo->rollBack();
    flash($ex instanceof DomainException?'error':'error',$ex instanceof DomainException?$ex->getMessage():'Chưa thể quay thưởng lúc này.');
}
redirect('wheel.php');
