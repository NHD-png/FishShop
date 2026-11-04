<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/rewards.php';
require_once __DIR__.'/includes/payos.php';
$user=requireLogin(); $pdo=db(); reward_schema($pdo); $uid=(int)$user['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost('wallet.php');
    if (post_string('regenerate') === '1') {
        $depositId = (int)($_POST['deposit_id'] ?? 0);
        try {
            payos_cancel_wallet_deposit_link($depositId,$uid);
            payos_create_wallet_deposit_link($depositId,$uid);
            flash('success','Đã tạo mã thanh toán mới.');
        } catch (Throwable $error) {
            flash('error',$error instanceof DomainException ? $error->getMessage() : 'Chưa thể tạo mã thanh toán mới. Vui lòng thử lại.');
            error_log('FishShop PayOS wallet QR regeneration failed (' . get_class($error) . ').');
        }
        redirect('wallet.php?deposit=' . $depositId);
    }
    $raw = trim(post_string('amount'));
    if (!ctype_digit($raw) || (int)$raw < 5000 || (int)$raw > 50000000) {
        flash('error', 'Số tiền nạp từ 5.000 đ đến 50.000.000 đ.');
        redirect('wallet.php');
    }
    $requestType = post_string('request_type') ?: 'payos';
    if (!in_array($requestType, ['payos','direct'], true)) {
        flash('error', 'Hình thức nạp tiền không hợp lệ.');
        redirect('wallet.php');
    }
    try {
        $st = $pdo->prepare("INSERT INTO wallet_deposits(user_id,amount,reference_mode,request_type,status) VALUES(?,?,'no_code',?,'pending')");
        $st->execute([$uid, (int)$raw, $requestType]);
        $depositId = (int)$pdo->lastInsertId();
        if ($requestType === 'direct') {
            flash('success', 'Đã gửi yêu cầu cộng tiền trực tiếp. Ví sẽ được cộng sau khi quản trị viên duyệt.');
            redirect('wallet.php?deposit=' . $depositId);
        }
        try {
            payos_create_wallet_deposit_link($depositId,$uid);
            flash('success','Đã tạo yêu cầu nạp ví. Quét mã bên dưới để thanh toán.');
        } catch (Throwable $error) {
            error_log('FishShop PayOS wallet QR creation failed (' . get_class($error) . ').');
            flash('error','Đã tạo yêu cầu nạp ví nhưng chưa tạo được mã thanh toán. Hãy mở lại yêu cầu để thử tiếp.');
        }
        redirect('wallet.php?deposit=' . $depositId);
    } catch (Throwable $ex) {
        flash('error', 'Chưa thể tạo yêu cầu nạp tiền. Vui lòng thử lại.');
        redirect('wallet.php');
    }
}$wallet=prize_wallet($pdo,$uid);
$st=$pdo->prepare('SELECT id,amount,reference_mode,request_type,status,payos_payment_link_id,payos_checkout_url,paid_at,payos_bank_bin,payos_account_number,payos_account_name,payos_transfer_description,payos_qr_code,created_at FROM wallet_deposits WHERE user_id=? ORDER BY id DESC LIMIT 20');$st->execute([$uid]);$requests=$st->fetchAll();
$selectedId=(int)($_GET['deposit']??0);$selectedDeposit=null;$payosError='';
if($selectedId>0){$st=$pdo->prepare('SELECT id,amount,reference_mode,request_type,status,payos_payment_link_id,payos_checkout_url,paid_at,payos_bank_bin,payos_account_number,payos_account_name,payos_transfer_description,payos_qr_code,created_at FROM wallet_deposits WHERE id=? AND user_id=?');$st->execute([$selectedId,$uid]);$selectedDeposit=$st->fetch()?:null;}
if($selectedDeposit && $selectedDeposit['request_type']==='payos' && $selectedDeposit['status']==='pending' && empty($selectedDeposit['payos_checkout_url'])){
    try{$payosUrl=payos_create_wallet_deposit_link($selectedId,$uid);$st=$pdo->prepare('SELECT id,amount,reference_mode,request_type,status,payos_payment_link_id,payos_checkout_url,paid_at,payos_bank_bin,payos_account_number,payos_account_name,payos_transfer_description,payos_qr_code,created_at FROM wallet_deposits WHERE id=? AND user_id=?');$st->execute([$selectedId,$uid]);$selectedDeposit=$st->fetch()?:$selectedDeposit;$selectedDeposit['payos_checkout_url']=$selectedDeposit['payos_checkout_url']?:$payosUrl;}
    catch(Throwable $error){$payosError='Chưa tạo được mã thanh toán. Hãy thử lại bằng nút bên dưới.';error_log('FishShop PayOS wallet QR retry failed (' . get_class($error) . ').');}
}
$walletQrUrl='';
if($selectedDeposit && !empty($selectedDeposit['payos_qr_code'])){
    $walletQrUrl='https://api.qrserver.com/v1/create-qr-code/?'.http_build_query([
        'size'=>'320x320','margin'=>8,'data'=>(string)$selectedDeposit['payos_qr_code'],
    ],'','&',PHP_QUERY_RFC3986);
}
$payosWasCancelled=strtolower((string)($_GET['cancel']??''))==='true'||strtoupper((string)($_GET['status']??''))==='CANCELLED';
$pageTitle='Nạp tiền ví vòng quay';include __DIR__.'/includes/header.php';
?>
<p class="crumb"><a href="<?=url('wheel.php')?>">Vòng quay</a> / Nạp tiền ví</p>
<section class="card form-card">
  <h1>Nạp tiền vào ví vòng quay</h1><p>Số dư hiện tại: <strong><?=money($wallet['balance'])?></strong></p>
  <form method="post" action="<?=url('wallet.php')?>"><?=csrf_field()?>
    <div class="field"><label for="amount">Số tiền muốn nạp (VNĐ)</label><input id="amount" name="amount" type="number" min="5000" max="50000000" step="1000" required placeholder="Ví dụ: 50000"></div>
    <div class="wallet-topup-actions"><button class="btn" type="submit" name="request_type" value="payos">Tạo mã thanh toán</button><button class="btn ghost" type="submit" name="request_type" value="direct">Chuyển trực tiếp</button></div>
  </form>
  <p class="hint">Quét mã để thanh toán. Ví sẽ được cộng tự động sau khi giao dịch được xác nhận.</p>
  <p class="hint">Yêu cầu cộng tiền trực tiếp sẽ được quản trị viên xem xét và duyệt trước khi cộng vào ví.</p>
</section>
<style>.wallet-topup-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}.wallet-topup-actions button{flex:1;min-width:190px}.wallet-payment-success{padding:24px;text-align:center;border:1px solid var(--teal);border-radius:14px;background:#102b2b}.wallet-payment-success-mark{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 12px;border-radius:50%;background:var(--teal);color:#092c28;font-size:30px;font-weight:800}.wallet-countdown{color:var(--mute);margin:12px 0}</style>
<?php if($selectedDeposit): ?>
<section class="card wallet-qr-card" style="margin-top:20px">
  <h2><?=$selectedDeposit['status']==='approved'?'Nạp ví thành công':($selectedDeposit['request_type']==='direct'?'Yêu cầu cộng tiền trực tiếp':'Thanh toán nạp ví')?></h2>
  <div class="transfer-details wallet-transfer-summary">
    <div><span>Số tiền nạp</span><strong><?=money($selectedDeposit['amount'])?></strong></div>
    <div><span>Mã yêu cầu</span><strong>#<?= (int)$selectedDeposit['id'] ?></strong></div>
    <?php if($selectedDeposit['request_type']==='payos'&&!empty($selectedDeposit['payos_account_number'])): ?>
      <div><span>Ngân hàng nhận</span><strong><?=e(payos_bank_display_name((string)$selectedDeposit['payos_bank_bin']))?></strong></div>
      <div><span>Chủ tài khoản</span><strong><?=e($selectedDeposit['payos_account_name'])?></strong></div>
      <div><span>Số tài khoản</span><strong><?=e($selectedDeposit['payos_account_number'])?></strong></div>
      <div><span>Nội dung chuyển khoản</span><strong><?=e($selectedDeposit['payos_transfer_description']?:'WV'.$selectedDeposit['id'])?></strong></div>
    <?php endif; ?>
  </div>
  <?php if($selectedDeposit['status']==='approved'): ?>
    <div class="wallet-payment-success" role="status"><div class="wallet-payment-success-mark" aria-hidden="true">✓</div><h3>Đã nạp tiền vào ví</h3><p><?=$selectedDeposit['request_type']==='direct'?'Quản trị viên đã duyệt yêu cầu. Số dư ví đã được cộng.':'Giao dịch đã được xác nhận. Số dư ví được cập nhật tự động.'?></p><a class="btn" href="<?=url('wallet.php')?>">Xem số dư ngay</a></div>
  <?php elseif($selectedDeposit['status']==='rejected'): ?>
    <div class="alert err">Yêu cầu cộng tiền này đã bị từ chối. Vui lòng liên hệ cửa hàng nếu cần hỗ trợ.</div>
  <?php elseif($selectedDeposit['request_type']==='direct'): ?>
    <div class="alert warn">Yêu cầu đã gửi và đang chờ quản trị viên duyệt. Số dư ví chỉ tăng sau khi yêu cầu được duyệt.</div>
  <?php elseif($payosWasCancelled): ?>
    <div class="alert warn">Bạn đã đóng hoặc hủy bước thanh toán. Yêu cầu vẫn đang chờ xác nhận.</div>
    <form method="post" action="<?=url('wallet.php')?>" style="margin-top:14px"><?=csrf_field()?><input type="hidden" name="deposit_id" value="<?=(int)$selectedDeposit['id']?>"><input type="hidden" name="regenerate" value="1"><button class="btn" type="submit">Tạo mã QR mới</button></form>
  <?php elseif(!empty($selectedDeposit['payos_checkout_url'])&&$walletQrUrl!==''): ?>
    <div id="wallet-qr-only" class="wallet-qr-only"><img id="wallet-qr-image" src="<?=e($walletQrUrl)?>" alt="Mã QR chuyển khoản cho yêu cầu nạp ví #<?= (int)$selectedDeposit['id'] ?>" width="300" height="300" referrerpolicy="no-referrer"></div>
    <p id="wallet-payos-note" class="payos-embed-note">Quét mã QR bằng ứng dụng ngân hàng để chuyển đúng số tiền và nội dung.</p>
    <div id="wallet-payment-success" class="wallet-payment-success hidden" role="status" aria-live="polite"><div class="wallet-payment-success-mark" aria-hidden="true">✓</div><h3>Thanh toán thành công</h3><p>Giao dịch đã được xác nhận. Tiền đã được cộng vào ví.</p><p class="wallet-countdown">Đang cập nhật số dư sau <strong id="wallet-countdown">3</strong> giây</p><a class="btn" href="<?=url('wallet.php')?>">Xem số dư ngay</a></div>
    <p id="wallet-payos-external" class="payos-embed-note hidden"><a class="link-btn" href="<?=e($selectedDeposit['payos_checkout_url'])?>" target="_blank" rel="noopener noreferrer">Mở trang thanh toán nếu mã QR không tải</a></p>
    <form id="wallet-payos-regenerate" method="post" action="<?=url('wallet.php')?>" style="margin-top:14px"><?=csrf_field()?><input type="hidden" name="deposit_id" value="<?=(int)$selectedDeposit['id']?>"><input type="hidden" name="regenerate" value="1"><button class="btn ghost" type="submit">Tạo mã QR mới</button></form>
    <script>
    (()=>{const note=document.getElementById('wallet-payos-note'),regenerate=document.getElementById('wallet-payos-regenerate'),statusUrl=<?=json_encode(url('actions/wallet_deposit_status.php?id='.(int)$selectedDeposit['id']),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>,walletUrl=<?=json_encode(url('wallet.php'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;let checking=false,redirecting=false,attempts=0,stopped=false;
    const showPaid=()=>{if(redirecting)return;redirecting=true;document.getElementById('wallet-qr-only').classList.add('hidden');note.classList.add('hidden');regenerate.classList.add('hidden');document.getElementById('wallet-payos-external').classList.add('hidden');document.getElementById('wallet-payment-success').classList.remove('hidden');let seconds=3;const counter=document.getElementById('wallet-countdown');counter.textContent=seconds;const timer=setInterval(()=>{counter.textContent=--seconds;if(seconds<=0){clearInterval(timer);window.location.replace(walletUrl+'?deposit=<?=(int)$selectedDeposit['id']?>');}},1000)};
    const check=async()=>{if(checking||redirecting||stopped)return;checking=true;attempts++;try{const response=await fetch(statusUrl,{headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store'});const result=await response.json();if(response.ok&&result.ok&&result.status==='approved'){showPaid();return}}catch(error){}finally{checking=false;if(!redirecting&&!stopped){if(attempts<36)setTimeout(check,5000);else{stopped=true;note.textContent='Chưa nhận được xác nhận. Hãy tải lại trang để kiểm tra trạng thái nạp ví.'}}}};
    const qrImage=document.getElementById('wallet-qr-image'),external=document.getElementById('wallet-payos-external');qrImage.addEventListener('error',()=>{note.textContent='Không tải được ảnh QR. Bạn có thể mở trang thanh toán riêng.';external.classList.remove('hidden')});check()})();
    </script>
  <?php elseif(!empty($selectedDeposit['payos_checkout_url'])): ?>
    <div class="alert warn">Chưa có đủ dữ liệu để hiển thị QR riêng cho yêu cầu này. Hãy tạo mã mới để cập nhật thông tin chuyển khoản.</div>
    <form method="post" action="<?=url('wallet.php')?>" style="margin-top:14px"><?=csrf_field()?><input type="hidden" name="deposit_id" value="<?=(int)$selectedDeposit['id']?>"><input type="hidden" name="regenerate" value="1"><button class="btn" type="submit">Tạo mã QR mới</button></form>
  <?php else: ?>
    <div class="alert err"><?=e($payosError ?: 'Chưa tạo được mã thanh toán cho yêu cầu này.')?></div>
    <form method="post" action="<?=url('wallet.php')?>" style="margin-top:14px"><?=csrf_field()?><input type="hidden" name="deposit_id" value="<?=(int)$selectedDeposit['id']?>"><input type="hidden" name="regenerate" value="1"><button class="btn" type="submit">Thử tạo mã thanh toán</button></form>
  <?php endif; ?>
</section>
<?php endif; ?>
<section class="card" style="margin-top:20px"><h2>Lịch sử yêu cầu nạp tiền</h2>
  <?php if(!$requests): ?><p>Chưa có yêu cầu nạp tiền.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Mã</th><th>Số tiền</th><th>Đối chiếu</th><th>Ngày tạo</th><th>Trạng thái</th></tr></thead><tbody>
  <?php foreach($requests as $r): ?><tr><td><a href="<?=url('wallet.php?deposit='.(int)$r['id'])?>">#<?=(int)$r['id']?></a></td><td><?=money($r['amount'])?></td><td><?=$r['request_type']==='direct'?'Duyệt trực tiếp':'Tự động'?></td><td><?=e($r['created_at'])?></td><td><?=($r['status']==='pending'&&$r['request_type']==='direct')?'Chờ duyệt':(['pending'=>'Chờ thanh toán','approved'=>'Đã nạp ví','rejected'=>'Từ chối'][$r['status']])?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>
