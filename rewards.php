<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rewards.php';
$user = requireLogin(); $pdo = db(); reward_schema($pdo);
$st = $pdo->prepare("SELECT MIN(r.id) AS id, r.reward_type, r.discount_percent, r.status,
    CASE WHEN r.status='used' THEN r.product_id ELSE NULL END AS product_id,
    COUNT(*) AS quantity, MAX(r.created_at) AS created_at, p.name AS product_name
    FROM prize_rewards r LEFT JOIN products p ON p.id=CASE WHEN r.status='used' THEN r.product_id ELSE NULL END
    WHERE r.user_id=?
    GROUP BY r.reward_type,r.discount_percent,r.status,CASE WHEN r.status='used' THEN r.product_id ELSE NULL END,p.name
    ORDER BY MAX(r.created_at) DESC, MIN(r.id) DESC");
$st->execute([(int)$user['id']]); $rewards = $st->fetchAll();
$fish = $pdo->query("SELECT id,name,price,image,stock FROM products WHERE status='active' AND stock>0 ORDER BY name")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost('rewards.php');
    if (post_string('action') === 'remove') {
        $id=(int)($_POST['reward_id']??0); $removeQuantity=(int)($_POST['remove_quantity']??0);
        try {
            $pdo->beginTransaction();
            $st=$pdo->prepare("SELECT reward_type,discount_percent,status FROM prize_rewards WHERE id=? AND user_id=? FOR UPDATE");
            $st->execute([$id,(int)$user['id']]); $group=$st->fetch();
            if(!$group||$group['status']!=='available') throw new DomainException('Vật phẩm không còn trong Kho hoặc đã được sử dụng.');
            if($removeQuantity<1) throw new DomainException('Số lượng cần trừ phải từ 1 trở lên.');
            $st=$pdo->prepare("SELECT id FROM prize_rewards WHERE user_id=? AND reward_type=? AND discount_percent=? AND status='available' ORDER BY id FOR UPDATE");
            $st->execute([(int)$user['id'],$group['reward_type'],(int)$group['discount_percent']]);
            $ids=array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));
            if($removeQuantity>count($ids)) throw new DomainException('Số lượng cần trừ lớn hơn số vật phẩm hiện có.');
            $delete=$pdo->prepare('DELETE FROM prize_rewards WHERE id=? AND user_id=? AND status=\'available\'');
            foreach(array_slice($ids,0,$removeQuantity) as $rewardId) $delete->execute([$rewardId,(int)$user['id']]);
            $pdo->commit(); flash('success','Đã trừ '.$removeQuantity.' vật phẩm khỏi Kho.');
        } catch(Throwable $ex) {
            if($pdo->inTransaction())$pdo->rollBack();
            flash('error',$ex instanceof DomainException?$ex->getMessage():'Chưa thể trừ vật phẩm khỏi Kho.');
        }
        redirect('rewards.php');
    }
    $id = (int)($_POST['reward_id'] ?? 0); $productId = (int)($_POST['product_id'] ?? 0);
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT * FROM prize_rewards WHERE id=? AND user_id=? AND status='available' FOR UPDATE");
        $st->execute([$id,(int)$user['id']]); $reward=$st->fetch();
        if (!$reward) throw new DomainException('Phần thưởng này không còn khả dụng.');
        if ($reward['reward_type']==='coupon') {
            if ($productId > 0) {
                $p=$pdo->prepare("SELECT id,stock FROM products WHERE id=? AND status='active' FOR UPDATE"); $p->execute([$productId]); $product=$p->fetch();
                if (!$product || (int)$product['stock']<1) throw new DomainException('Cá đã chọn không còn hàng.');
                $pdo->prepare("INSERT INTO cart(user_id) VALUES(?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)")->execute([(int)$user['id']]);
                $cartId=(int)$pdo->lastInsertId(); if(!$cartId){$c=$pdo->prepare('SELECT id FROM cart WHERE user_id=?');$c->execute([(int)$user['id']]);$cartId=(int)$c->fetchColumn();}
                $pdo->prepare('INSERT INTO cart_items(cart_id,product_id,quantity) VALUES(?,?,1) ON DUPLICATE KEY UPDATE quantity=LEAST(quantity+1,999)')->execute([$cartId,$productId]);
                $_SESSION['prize_coupon_reward'] = $id; $_SESSION['prize_coupon_product'] = $productId;
                $pdo->commit(); flash('success','Đã thêm cá vào giỏ. Khi thanh toán, hệ thống dùng tối đa một phiếu giảm 5% cho mỗi con cùng loại.'); redirect('cart.php');
            }
            $_SESSION['prize_coupon_reward'] = $id;
            $pdo->commit();
            flash('success','Đã chọn phiếu. Chọn loại cá; mỗi con cùng loại trong giỏ sẽ dùng một phiếu giảm 5%.');
            redirect('rewards.php?choose='.$id);
        }
        if ($reward['reward_type'] !== 'fish') throw new DomainException('Phần thưởng này không thể sử dụng.');
        $p=$pdo->prepare("SELECT id,stock FROM products WHERE id=? AND status='active' FOR UPDATE"); $p->execute([$productId]); $product=$p->fetch();
        if (!$product || (int)$product['stock']<1) throw new DomainException('Cá đã chọn không còn hàng.');
        $pdo->prepare('UPDATE prize_rewards SET product_id=? WHERE id=?')->execute([$productId,$id]);
        $pdo->prepare("INSERT INTO cart(user_id) VALUES(?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)")->execute([(int)$user['id']]);
        $cartId=(int)$pdo->lastInsertId(); if(!$cartId){$c=$pdo->prepare('SELECT id FROM cart WHERE user_id=?');$c->execute([(int)$user['id']]);$cartId=(int)$c->fetchColumn();}
        $pdo->prepare('INSERT INTO cart_items(cart_id,product_id,quantity) VALUES(?,?,1) ON DUPLICATE KEY UPDATE quantity=LEAST(quantity+1,999)')->execute([$cartId,$productId]);
        $_SESSION['prize_fish_reward'] = $id; $_SESSION['prize_fish_product'] = $productId;
        $pdo->commit(); flash('success','Cá thưởng đã được thêm vào giỏ. Hoàn tất thanh toán với thông tin giao hàng như bình thường.'); redirect('cart.php');
    } catch(Throwable $ex) { if($pdo->inTransaction())$pdo->rollBack(); flash('error',$ex instanceof DomainException?$ex->getMessage():'Chưa thể sử dụng phần thưởng lúc này.'); redirect('rewards.php'); }
}
$chooseId=(int)($_GET['choose']??0); $pageTitle='Kho giải thưởng'; include __DIR__.'/includes/header.php';
?>
<p class="crumb"><a href="<?=url('index.php')?>">Trang chủ</a> / Kho giải thưởng</p><h1 style="font-size:28px">Kho giải thưởng</h1>
<p><a class="btn ghost sm" href="<?=url('wheel.php')?>">Đến vòng quay</a></p>
<?php if(!$rewards): ?><div class="empty"><h2>Kho của bạn đang trống</h2><p>Hãy quay thử để nhận phần thưởng.</p></div><?php endif; ?>
<div class="product-list">
<?php foreach($rewards as $r): ?><article class="product reward-card <?= $r['reward_type']==='coupon'?'reward-card-coupon':($r['reward_type']==='fish'?'reward-card-fish':'') ?>"><div class="product-body"><span class="chip"><?= $r['status']==='used'?'Đã sử dụng':'Có thể sử dụng' ?></span><h2><?= $r['reward_type']==='coupon'?'Phiếu giảm giá '.(int)$r['discount_percent'].'%':($r['reward_type']==='fish'?'Phần thưởng cá':'Không trúng thưởng') ?> <span class="chip">×<?= (int)$r['quantity'] ?></span></h2>
<?php if($r['reward_type']==='coupon'&&$r['status']==='available'): ?><a class="btn" href="<?=url('rewards.php?choose='.(int)$r['id'])?>">Sử dụng phiếu</a><?php endif; ?>
<?php if($r['reward_type']==='fish'&&$r['status']==='available'): ?><form method="post" action="<?=url('rewards.php')?>"><?=csrf_field()?><input type="hidden" name="reward_id" value="<?=(int)$r['id']?>"><label for="fish-<?=(int)$r['id']?>">Chọn cá để nhận</label><select id="fish-<?=(int)$r['id']?>" name="product_id" required><?php foreach($fish as $p): ?><option value="<?=(int)$p['id']?>"><?=e($p['name'])?></option><?php endforeach; ?></select><button class="btn" type="submit">Chọn cá và giao hàng</button></form><?php endif; ?>
<?php if($r['status']==='available'): ?><form method="post" action="<?=url('rewards.php')?>" data-confirm="Bạn có chắc muốn trừ số lượng đã chọn khỏi Kho?"><?=csrf_field()?><input type="hidden" name="action" value="remove"><input type="hidden" name="reward_id" value="<?=(int)$r['id']?>"><label for="remove-<?=(int)$r['id']?>">Số lượng muốn trừ</label><input id="remove-<?=(int)$r['id']?>" type="number" name="remove_quantity" min="1" max="<?=(int)$r['quantity']?>" value="1" required><button class="btn ghost" type="submit">Trừ vật phẩm</button></form><?php endif; ?>
<?php if($r['status']==='used'&&$r['product_name']): ?><p>Đã chọn: <?=e($r['product_name'])?></p><?php endif; ?></div></article><?php endforeach; ?></div>
<?php if($chooseId): ?>
<section class="card coupon-fish-picker">
  <h2>Chọn cá để dùng phiếu giảm 5%</h2>
  <p class="hint coupon-picker-intro">Cá bạn chọn sẽ được thêm vào giỏ hàng. Phiếu giảm giá được áp dụng khi thanh toán.</p>
  <?php if(!$fish): ?><p>Hiện chưa có cá còn hàng để áp dụng phiếu.</p><?php else: ?>
  <div class="product-list coupon-product-grid">
    <?php foreach($fish as $p): ?>
    <article class="product coupon-product">
      <div class="coupon-product-image"><img src="<?=e(file_url($p['image']))?>" alt="<?=e($p['name'])?>" loading="lazy"></div>
      <div class="coupon-product-body">
        <h3><?=e($p['name'])?></h3>
        <div class="coupon-product-price">
          <span>Giá sau khi dùng 1 phiếu giảm 5%</span>
          <strong><?=money((int)$p['price'] - prize_coupon_line_quote((int)$p['price'], 1, 1)['discount'])?></strong>
          <del class="hint"><?=money($p['price'])?></del>
        </div>
        <form method="post" action="<?=url('rewards.php')?>">
          <?=csrf_field()?>
          <input type="hidden" name="reward_id" value="<?=$chooseId?>">
          <input type="hidden" name="product_id" value="<?=(int)$p['id']?>">
          <button class="btn" type="submit">Chọn cá và dùng phiếu</button>
        </form>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <p class="hint coupon-picker-note">Mỗi con cá cùng loại trong giỏ dùng một phiếu giảm 5%, tối đa bằng số phiếu còn trong Kho.</p>
</section>
<?php endif; ?>
<style>
.reward-card{min-height:230px;background-size:cover;background-position:center;position:relative}.reward-card-coupon{background-image:linear-gradient(135deg,rgba(9,29,39,.98),rgba(44,182,163,.28))}.reward-card-fish{background-image:linear-gradient(135deg,rgba(9,29,39,.98),rgba(246,200,95,.22))}.reward-card .product-body{padding:20px;text-shadow:0 1px 3px #000}
.coupon-fish-picker{margin-top:24px}
.coupon-fish-picker h2{margin:0;font-size:22px;line-height:1.4}
.coupon-picker-intro{margin:8px 0 20px;line-height:1.6}
.coupon-product-grid{grid-template-columns:repeat(auto-fill,minmax(min(100%,240px),1fr));gap:20px}
.coupon-product{min-width:0}
.coupon-product-image{aspect-ratio:4/3;overflow:hidden;background:var(--bg)}
.coupon-product-image img{display:block;width:100%;height:100%;object-fit:cover}
.coupon-product-body{display:flex;flex:1;flex-direction:column;gap:16px;padding:18px}
.coupon-product .coupon-product-body h3{margin:0;font-size:17px;line-height:1.5;overflow-wrap:anywhere}
.coupon-product-price{display:grid;gap:4px}
.coupon-product-price span{font-size:13px;color:var(--mute)}
.coupon-product-price strong{font-size:21px;line-height:1.4;color:var(--teal)}
.coupon-product-body form{margin: auto 0 0;padding-top:4px}
.coupon-product .coupon-product-body .btn{width:100%;min-height:46px;padding:11px 12px;font-size:14px;line-height:1.5;white-space:normal}
.coupon-picker-note{margin:20px 0 0;line-height:1.6}
@media(max-width:560px){.coupon-product-grid{grid-template-columns:1fr}.coupon-product-image{max-height:240px}.coupon-product-body{padding:16px}}
</style>
<?php include __DIR__.'/includes/footer.php'; ?>
