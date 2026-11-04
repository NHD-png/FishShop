<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rewards.php';
$user=requireLogin(); $pdo=db(); reward_schema($pdo); $wallet=prize_wallet($pdo,(int)$user['id']);
$rates=prize_spin_odds();
$rateText=fn($bp)=>number_format((int)$bp/100,2,'.','');
$segments=[['type'=>'miss','label'=>'Trượt','bp'=>(int)$rates['miss_bp'],'color'=>'#e88759'],['type'=>'coupon','label'=>'5%','bp'=>(int)$rates['coupon_bp'],'color'=>'#2cb6a3'],['type'=>'fish','label'=>'Cá','bp'=>(int)$rates['fish_bp'],'color'=>'#f6c85f']];
$sectorMarkup='';$angle=0.0;
foreach($segments as $segment){
    $sweep=$segment['bp']/10000*360;
    if($sweep<=0)continue;
$fill=$segment['color'];
    if($sweep>=359.999){$sectorMarkup.='<circle cx="50" cy="50" r="48" fill="'.$fill.'" stroke="#f0f7f3" stroke-width=".7"/>';$mid=$angle+180;}
    else{$a1=deg2rad(-90+$angle);$a2=deg2rad(-90+$angle+$sweep);$x1=50+48*cos($a1);$y1=50+48*sin($a1);$x2=50+48*cos($a2);$y2=50+48*sin($a2);$large=$sweep>180?1:0;$sectorMarkup.='<path d="M50 50 L'.number_format($x1,3,'.','').' '.number_format($y1,3,'.','').' A48 48 0 '.$large.' 1 '.number_format($x2,3,'.','').' '.number_format($y2,3,'.','').' Z" fill="'.$fill.'" stroke="#f0f7f3" stroke-width=".7"/>';$mid=$angle+$sweep/2;}
    $sectorMarkup.='<text x="50" y="23" text-anchor="middle" dominant-baseline="middle" transform="rotate('.number_format($mid,3,'.','').' 50 50)" fill="#10202a" stroke="#fff" stroke-width=".8" paint-order="stroke" font-weight="800" font-size="'.max(3.2,min(9,$sweep/8)).'">'.$segment['label'].'</text>';
    $angle+=$sweep;
}
$spinResult=is_array($_SESSION['prize_spin_result']??null)?$_SESSION['prize_spin_result']:null;
unset($_SESSION['prize_spin_result']);
$selectedCount = (int)($spinResult['count'] ?? 1);
if (!in_array($selectedCount, [1, 5, 10], true)) $selectedCount = 1;
$pageTitle='Vòng quay trúng thưởng'; include __DIR__.'/includes/header.php';
?>
<p class="crumb"><a href="<?=url('index.php')?>">Trang chủ</a> / Vòng quay trúng thưởng</p>
<section class="card prize-wheel-page">
  <p class="map-eyebrow">MAY MẮN HÔM NAY</p><h1>Vòng quay trúng thưởng</h1>
  <div class="wallet-balance"><span>Số dư ví</span><b><?=money($wallet['balance'])?></b></div>
  <p><a class="btn ghost sm" href="<?=url('wallet.php')?>">＋ Nạp tiền vào ví</a></p>
  <div class="spin-layout">
    <form class="spin-purchase" id="spin-purchase" method="post" action="<?=url('actions/spin.php')?>">
      <?=csrf_field()?>
      <fieldset id="spin-fields">
        <legend>Chọn mức tiền</legend>
        <?php foreach([1,5,10] as $n): ?>
          <label class="spin-price-option">
            <input type="radio" name="count" value="<?=$n?>" <?=$selectedCount===$n?'checked':''?> required>
            <span><strong><?=money($n*5000)?></strong><small><?=$n?> lượt quay</small></span>
          </label>
        <?php endforeach; ?>
        <button class="btn block" id="spin-submit" type="submit">Quay ngay</button>
      </fieldset>
      <p class="hint">Mỗi lượt quay dùng 5.000 đ từ ví.</p>
    </form>
    <div class="spin-main">
  <div class="wheel-stage"><div class="wheel-pin" aria-hidden="true"></div><div class="prize-wheel" id="prize-wheel" data-miss="<?=$rateText($rates['miss_bp'])?>" data-coupon="<?=$rateText($rates['coupon_bp'])?>" data-fish="<?=$rateText($rates['fish_bp'])?>" aria-label="Vòng quay trúng thưởng"><svg viewBox="0 0 100 100" role="img" aria-label="Các phần thưởng theo tỷ lệ công bố"><?=$sectorMarkup?><circle cx="50" cy="50" r="12" fill="#f4f7f5" stroke="#143e43" stroke-width="1.5"/><text x="50" y="51" text-anchor="middle" dominant-baseline="middle" font-size="9">★</text></svg></div></div>
  <?php if($spinResult): ?>
    <div id="spin-result" class="spin-result" hidden>
      <h2>Kết quả <?=count($spinResult['outcomes'])>1?'các lượt quay':'lượt quay'?></h2>
      <div class="spin-results-list"><?php foreach($spinResult['outcomes'] as $i=>$outcome): ?><div><?=($i+1)?>. <?= $outcome['type']==='miss'?'😿 Không trúng gì':($outcome['type']==='coupon'?'🎟️ Phiếu giảm giá 5%':'🐠 Phần thưởng cá') ?></div><?php endforeach; ?></div>
    </div>
    <div class="spin-animation-controls" id="spin-controls"><p>Vòng quay đang chạy…</p><button class="btn ghost" type="button" id="skip-spin">Bỏ qua</button></div>

<script type="application/json" id="spin-outcomes"><?=json_encode(array_column($spinResult['outcomes'],'type'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
    <script>
    (async()=>{const wheel=document.getElementById('prize-wheel'),result=document.getElementById('spin-result'),controls=document.getElementById('spin-controls'),skip=document.getElementById('skip-spin'),fields=document.getElementById('spin-fields'),outcomes=JSON.parse(document.getElementById('spin-outcomes').textContent);fields.disabled=true;let stopped=false,angle=0,timer;const finish=()=>{stopped=true;clearTimeout(timer);wheel.style.transition='none';result.hidden=false;controls.hidden=true;fields.disabled=false;};skip.addEventListener('click',finish);const miss=Number(wheel.dataset.miss),coupon=Number(wheel.dataset.coupon),center={miss:miss*3.6/2,coupon:miss*3.6+coupon*3.6/2,fish:(miss+coupon)*3.6+Number(wheel.dataset.fish)*3.6/2};for(const type of outcomes){if(stopped)break;const remainder=((angle%360)+360)%360,delta=((360-center[type]-remainder)%360+360)%360;angle+=360*4+delta;wheel.style.transition='none';wheel.style.transform=`rotate(${((angle-360*4-delta)%360+360)%360}deg)`;void wheel.offsetWidth;wheel.style.transition='transform 1.85s cubic-bezier(.12,.74,.19,1)';wheel.style.transform=`rotate(${angle}deg)`;await new Promise(resolve=>{timer=setTimeout(resolve,1950);});}if(!stopped)finish();})();
    </script>
  <?php endif; ?>
    </div>
  </div>
    <div class="prize-odds"><div><b><?=$rateText($rates['miss_bp'])?>%</b><span>Không trúng gì</span></div><div><b><?=$rateText($rates['coupon_bp'])?>%</b><span>Phiếu giảm giá 5%</span></div><div><b><?=$rateText($rates['fish_bp'])?>%</b><span>Một phần thưởng cá</span></div></div>
    <p class="hint">Các tỷ lệ trên cũng là tỷ lệ đang được dùng để quay.</p>

  <a class="btn ghost" href="<?=url('rewards.php')?>">Mở Kho giải thưởng</a>
</section>
<style>
.prize-wheel-page{text-align:center;max-width:900px;margin:26px auto}
.wallet-balance{display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;padding:12px;margin:18px auto;max-width:500px;border:1px solid var(--line);border-radius:12px;background:#0b1722}
.wallet-balance b{font-size:22px;color:var(--teal)}
.spin-layout{display:grid;grid-template-columns:260px minmax(0,1fr);align-items:center;gap:32px;margin:28px 0}
.spin-purchase{padding:20px;text-align:left;border:1px solid var(--line);border-radius:14px;background:#0b1722}
.spin-purchase fieldset{min-width:0;margin:0;padding:0;border:0;display:grid;gap:12px}
.spin-purchase legend{padding:0;margin-bottom:14px;font-size:18px;font-weight:700}
.spin-price-option{display:flex;align-items:center;gap:12px;margin:0;padding:13px 14px;border:1px solid var(--line);border-radius:10px;cursor:pointer}
.spin-price-option:has(input:checked){border-color:var(--teal);background:rgba(44,182,163,.12)}
.spin-price-option input[type=radio]{appearance:auto;width:18px;height:18px;margin:0;flex:0 0 18px;accent-color:var(--teal)}
.spin-price-option strong,.spin-price-option small{display:block}
.spin-price-option strong{font-size:18px;color:var(--teal)}
.spin-price-option small{margin-top:3px;font-weight:400}
.spin-purchase .btn{margin-top:4px}
.spin-purchase .hint{margin:12px 0 0}
.spin-main{min-width:0}
.wheel-stage{position:relative;width:min(300px,calc(100% - 20px));margin:24px auto}
.prize-wheel{width:100%;aspect-ratio:1;border-radius:50%;background:#153d44;border:8px solid #e8f4ef;box-shadow:0 0 0 5px #187e76;overflow:hidden;transform:rotate(0deg);will-change:transform}
.prize-wheel svg{display:block;width:100%;height:100%}
.wheel-pin{position:absolute;top:-14px;left:calc(50% - 15px);z-index:3;width:0;height:0;border-left:15px solid transparent;border-right:15px solid transparent;border-top:32px solid #fff;filter:drop-shadow(0 2px 2px #000)}
.prize-odds{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:22px 0}
.prize-odds div{padding:16px;background:#0b1722;border:1px solid var(--line);border-radius:12px}
.prize-odds b,.prize-odds span{display:block}
.prize-odds b{font-size:24px;color:var(--teal)}
.prize-odds span{font-size:13px}
.spin-results-list{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
.spin-results-list div{padding:12px;border-radius:10px;background:#0b1722;border:1px solid var(--line)}
.spin-animation-controls{margin:16px}
@media(max-width:680px){.spin-layout{grid-template-columns:1fr;gap:16px}.spin-main{grid-row:1}.spin-purchase{width:100%}}
@media(max-width:520px){.prize-odds{grid-template-columns:1fr}}
</style>
<script>
document.getElementById('spin-purchase').addEventListener('submit',()=>{
  const button=document.getElementById('spin-submit');
  button.disabled=true;
  button.textContent='Đang quay…';
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>
