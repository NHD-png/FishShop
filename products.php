<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/catalog.php';

$filters = catalog_filters($_GET);
$q = $filters['q'];
$cat = $filters['cat'];
$cats = db()->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll();
[$sql, $args] = catalog_query($filters);
$st = db()->prepare($sql);
$st->execute($args);
$products = $st->fetchAll();

$catalogTitle = 'Tất cả sản phẩm';
foreach ($cats as $category) if ((int)$category['id'] === $cat) $catalogTitle = $category['name'];
$productListPage = 'products.php';
$productListBack = safe_back(catalog_path($filters), $productListPage);
$catalogCards = true;
$bodyClass = 'catalog-page';
$pageStyles = ['assets/css/catalog.css'];
$pageTitle = 'Danh sách sản phẩm';
include __DIR__ . '/includes/header.php';
?>
<p class="crumb"><a href="<?=url('index.php')?>">Trang chủ</a> / Danh sách sản phẩm</p>
<section class="page-intro catalog-intro">
  <h1><?=e($catalogTitle)?></h1>
  <p>Tìm cá, hồ thủy sinh và phụ kiện phù hợp với bạn.</p>
  <nav class="catalog-tabs" aria-label="Nhóm sản phẩm">
    <?php foreach (['all'=>'Tất cả', 'stock'=>'Còn hàng', 'new'=>'Mới về', 'best'=>'Bán chạy nhất'] as $value=>$label): ?>
      <a class="pill <?=$filters['view'] === $value ? 'is-active' : ''?>" href="<?=e(url(catalog_path($filters, ['view'=>$value, 'sort'=>'default'])))?>" <?=$filters['view'] === $value ? 'aria-current="page"' : ''?>><?=$label?></a>
    <?php endforeach; ?>
  </nav>
</section>
<?php foreach ($filters['errors'] as $error): ?><p class="alert warn" role="alert"><?=e($error)?></p><?php endforeach; ?>
<div class="catalog-layout">
  <aside class="catalog-filters card" aria-labelledby="catalog-filter-title">
    <div class="catalog-filter-heading"><h2 id="catalog-filter-title">Bộ lọc</h2><a href="<?=url('products.php')?>">Xóa lọc</a></div>
    <form method="get" action="<?=url('products.php')?>" id="catalog-filter-form">
      <?php foreach (['q','view','sort'] as $key): ?><input type="hidden" name="<?=$key?>" value="<?=e($filters[$key])?>"><?php endforeach; ?>
      <fieldset class="catalog-filter-group">
        <legend>Danh mục</legend>
        <div class="catalog-category-options">
          <label class="catalog-radio"><input type="radio" name="cat" value="0" <?=$cat === 0 ? 'checked' : ''?> onchange="this.form.requestSubmit()"><span>Tất cả</span></label>
          <?php foreach ($cats as $category): ?>
            <label class="catalog-radio"><input type="radio" name="cat" value="<?=(int)$category['id']?>" <?=$cat === (int)$category['id'] ? 'checked' : ''?> onchange="this.form.requestSubmit()"><span><?=e($category['name'])?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <fieldset class="catalog-filter-group">
        <legend>Khoảng giá</legend>
        <div class="catalog-price-range">
          <div><label for="catalog-min-price">Từ (đ)</label><input id="catalog-min-price" type="number" name="min_price" min="0" max="999999999999" step="1" placeholder="0" value="<?=e($filters['min_price'])?>"></div>
          <span aria-hidden="true">–</span>
          <div><label for="catalog-max-price">Đến (đ)</label><input id="catalog-max-price" type="number" name="max_price" min="0" max="999999999999" step="1" placeholder="Không giới hạn" value="<?=e($filters['max_price'])?>"></div>
        </div>
      </fieldset>
      <label class="catalog-radio catalog-stock"><input type="checkbox" name="in_stock" value="1" <?=$filters['in_stock'] === '1' ? 'checked' : ''?>><span>Chỉ hiện sản phẩm còn hàng</span></label>
      <button class="btn block" type="submit">Áp dụng bộ lọc</button>
    </form>
  </aside>
  <section class="catalog-results" aria-label="Kết quả sản phẩm">
    <div class="tool catalog-toolbar">
      <p aria-live="polite"><?=count($products)?> sản phẩm<?=$q !== '' ? ' cho “' . e($q) . '”' : ''?></p>
      <form method="get" action="<?=url('products.php')?>">
        <?php foreach (['q','cat','view','min_price','max_price','in_stock'] as $key): ?><input type="hidden" name="<?=$key?>" value="<?=e($filters[$key])?>"><?php endforeach; ?>
        <label for="catalog-sort" class="sr-only">Sắp xếp sản phẩm</label>
        <select id="catalog-sort" name="sort" onchange="this.form.submit()">
          <?php foreach (['default'=>'Mặc định','new'=>'Mới nhất','best'=>'Bán chạy nhất','price_asc'=>'Giá tăng dần','price_desc'=>'Giá giảm dần','name'=>'Tên A–Z'] as $value=>$label): ?>
            <option value="<?=$value?>" <?=$filters['sort'] === $value ? 'selected' : ''?>><?=$label?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button class="btn sm" type="submit">Sắp xếp</button></noscript>
      </form>
    </div>
    <?php if ($filters['view'] === 'new'): ?><p class="catalog-view-hint">Sản phẩm mới được thêm trong 30 ngày gần nhất.</p><?php endif; ?>
    <?php include __DIR__ . '/products/product_list.php'; ?>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
