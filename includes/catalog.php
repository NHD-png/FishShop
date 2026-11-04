<?php
function catalog_filters(array $input): array {
    $string = static fn($key) => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    $sorts = ['default', 'new', 'best', 'price_asc', 'price_desc', 'name'];
    $views = ['all', 'new', 'best', 'stock'];
    $view = in_array($string('view'), $views, true) ? $string('view') : 'all';
    $sort = in_array($string('sort'), $sorts, true) ? $string('sort') : 'default';
    $errors = [];
    $prices = [];
    foreach (['min_price', 'max_price'] as $key) {
        $raw = $string($key);
        $prices[$key] = '';
        if ($raw === '') continue;
        if (!ctype_digit($raw) || strlen($raw) > 12) {
            $errors[] = 'Khoảng giá cần là số tiền hợp lệ, từ 0 đến 999.999.999.999 đ.';
        } else $prices[$key] = (string)(int)$raw;
    }
    if ($prices['min_price'] !== '' && $prices['max_price'] !== '' && (int)$prices['min_price'] > (int)$prices['max_price']) {
        $errors[] = 'Giá thấp nhất phải nhỏ hơn hoặc bằng giá cao nhất.';
        $prices = ['min_price'=>'', 'max_price'=>''];
    }
    return ['q'=>$string('q'), 'cat'=>ctype_digit($string('cat')) ? (int)$string('cat') : 0,
        'sort'=>$sort, 'view'=>$view, 'in_stock'=>$string('in_stock') === '1' ? '1' : '',
        'min_price'=>$prices['min_price'], 'max_price'=>$prices['max_price'], 'errors'=>array_unique($errors)];
}

function catalog_path(array $filters, array $changes = []): string {
    $values = array_intersect_key(array_replace($filters, $changes),
        array_flip(['q', 'cat', 'sort', 'view', 'min_price', 'max_price', 'in_stock']));
    $query = http_build_query(array_filter($values, static fn($v) => $v !== '' && $v !== null && $v !== 0 && $v !== 'all' && $v !== 'default'));
    return 'products.php' . ($query !== '' ? '?' . $query : '');
}

function catalog_query(array $filters): array {
    $sql = "SELECT p.id, p.name, p.price, p.image, p.stock, p.created_at, c.name AS category_name,
                   COALESCE(sales.sold_count,0) AS sold_count,
                   (p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS is_new
            FROM products p JOIN categories c ON c.id=p.category_id
            LEFT JOIN (
                SELECT oi.product_id, SUM(oi.quantity) AS sold_count
                FROM order_items oi JOIN orders o ON o.id=oi.order_id
                WHERE o.status='completed' GROUP BY oi.product_id
            ) sales ON sales.product_id=p.id
            WHERE p.status='active' AND c.status='active'";
    $args = [];
    if ($filters['q'] !== '') { $sql .= ' AND p.name LIKE ?'; $args[] = '%' . addcslashes($filters['q'], '%_\\') . '%'; }
    if ($filters['cat'] > 0) { $sql .= ' AND p.category_id=?'; $args[] = $filters['cat']; }
    if ($filters['min_price'] !== '') { $sql .= ' AND p.price>=?'; $args[] = (int)$filters['min_price']; }
    if ($filters['max_price'] !== '') { $sql .= ' AND p.price<=?'; $args[] = (int)$filters['max_price']; }
    if ($filters['in_stock'] === '1' || $filters['view'] === 'stock') $sql .= ' AND p.stock>0';
    if ($filters['view'] === 'new') $sql .= ' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    if ($filters['view'] === 'best') $sql .= ' AND COALESCE(sales.sold_count,0)>0';
    $sort = $filters['sort'];
    if ($sort === 'default') $sort = $filters['view'] === 'best' ? 'best' : 'new';
    $orders = ['new'=>'p.created_at DESC, p.id DESC', 'best'=>'sold_count DESC, p.id DESC',
        'price_asc'=>'p.price ASC, p.id DESC', 'price_desc'=>'p.price DESC, p.id DESC', 'name'=>'p.name ASC, p.id DESC'];
    return [$sql . ' ORDER BY ' . $orders[$sort], $args];
}
