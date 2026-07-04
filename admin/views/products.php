<?php
defined( 'ABSPATH' ) || exit;

// WooCommerce product query.
$search   = sanitize_text_field( $_GET['s']    ?? '' );
$category = sanitize_text_field( $_GET['cat']  ?? '' );
$stock    = sanitize_text_field( $_GET['stock'] ?? '' );
$per_page = 24;
$paged    = max( 1, (int) ( $_GET['paged'] ?? 1 ) );

$args = [
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
];
if ( $search ) {
	$args['s'] = $search;
}
if ( $category ) {
	$args['tax_query'] = [ [ 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $category ] ];
}
if ( $stock === 'instock' ) {
	$args['meta_query'] = [ [ 'key' => '_stock_status', 'value' => 'instock' ] ];
} elseif ( $stock === 'outofstock' ) {
	$args['meta_query'] = [ [ 'key' => '_stock_status', 'value' => 'outofstock' ] ];
}

$query    = new WP_Query( $args );
$products = $query->posts;
$total    = $query->found_posts;
$pages    = ceil( $total / $per_page );

// Categories for filter.
$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true ] );
?>
<div class="wrap aiagent-page">
  <h1>Products Catalogue</h1>
  <p style="color:#64748b;margin-bottom:20px;">Browse your WooCommerce products. Click a product to open it for editing, or go to its Manuals page.</p>

  <!-- ── Filters ──────────────────────────────────────────────────────────── -->
  <form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:24px;">
    <input type="hidden" name="page" value="aiagent-products">

    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="🔍 Search products…"
      class="aa-input" style="max-width:220px;">

    <select name="cat" class="aa-select" style="max-width:180px;">
      <option value="">All Categories</option>
      <?php foreach ( $cats as $c ) : ?>
        <option value="<?php echo esc_attr($c->slug); ?>" <?php selected($category,$c->slug); ?>>
          <?php echo esc_html($c->name); ?> (<?php echo (int)$c->count; ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <select name="stock" class="aa-select" style="max-width:160px;">
      <option value="">All Stock</option>
      <option value="instock"    <?php selected($stock,'instock'); ?>>In Stock</option>
      <option value="outofstock" <?php selected($stock,'outofstock'); ?>>Out of Stock</option>
    </select>

    <button type="submit" class="aa-btn aa-btn--primary">Filter</button>
    <?php if ($search || $category || $stock) : ?>
      <a href="?page=aiagent-products" class="aa-btn">Clear</a>
    <?php endif; ?>

    <span style="margin-left:auto;color:#64748b;font-size:13px;">
      <?php echo number_format($total); ?> product<?php echo $total!==1?'s':''; ?>
    </span>
  </form>

  <!-- ── Product grid ─────────────────────────────────────────────────────── -->
  <?php if ( empty($products) ) : ?>
    <div class="aa-empty" style="margin-top:60px;">
      <div class="aa-empty__icon">📦</div>
      <div class="aa-empty__title">No products found</div>
      <div class="aa-empty__desc">Try a different search or category filter.</div>
    </div>

  <?php else : ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
    <?php foreach ( $products as $product_post ) :
      $product  = wc_get_product( $product_post->ID );
      if ( ! $product ) continue;
      $img_id   = $product->get_image_id();
      $img_url  = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
      $price    = $product->get_price_html();
      $in_stock = $product->is_in_stock();
      $sku      = $product->get_sku();
      $edit_url = get_edit_post_link( $product_post->ID );
      $cats_str = strip_tags( wc_get_product_category_list( $product_post->ID ) );
    ?>
    <div class="aa-product-card" onclick="window.location='<?php echo esc_url($edit_url); ?>'">
      <!-- Image -->
      <div class="aa-product-card__img">
        <?php if ( $img_url ) : ?>
          <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
        <?php else : ?>
          <div class="aa-product-card__noimg">📦</div>
        <?php endif; ?>

        <!-- Stock badge -->
        <div class="aa-product-card__stock <?php echo $in_stock?'aa-product-card__stock--in':'aa-product-card__stock--out'; ?>">
          <?php echo $in_stock ? 'In Stock' : 'Out'; ?>
        </div>
      </div>

      <!-- Info -->
      <div class="aa-product-card__body">
        <div class="aa-product-card__name"><?php echo esc_html($product->get_name()); ?></div>

        <?php if ( $sku ) : ?>
          <div class="aa-product-card__sku">SKU: <?php echo esc_html($sku); ?></div>
        <?php endif; ?>

        <?php if ( $cats_str ) : ?>
          <div class="aa-product-card__cat"><?php echo esc_html(wp_trim_words($cats_str,4,'')); ?></div>
        <?php endif; ?>

        <div class="aa-product-card__price"><?php echo wp_kses_post($price); ?></div>

        <!-- Quick links -->
        <div class="aa-product-card__actions" onclick="event.stopPropagation()">
          <a href="<?php echo esc_url($edit_url); ?>" class="aa-btn" style="font-size:11px;padding:4px 10px;">Edit</a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=aiagent-manuals&model='.urlencode($sku?:$product->get_name()))); ?>"
             class="aa-btn aa-btn--primary" style="font-size:11px;padding:4px 10px;">Manual</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Pagination ──────────────────────────────────────────────────────── -->
  <?php if ( $pages > 1 ) : ?>
  <div style="display:flex;gap:6px;margin-top:24px;align-items:center;">
    <?php for ( $p = 1; $p <= $pages; $p++ ) :
      $href = add_query_arg( array_merge( $_GET, [ 'paged' => $p ] ) );
    ?>
      <a href="<?php echo esc_url($href); ?>"
         style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;
         border:1px solid <?php echo $p===$paged?'#1a6b3c':'#e2e8f0'; ?>;
         background:<?php echo $p===$paged?'#1a6b3c':'#fff'; ?>;
         color:<?php echo $p===$paged?'#fff':'#374151'; ?>;font-size:13px;text-decoration:none;">
        <?php echo $p; ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<style>
.aa-product-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;cursor:pointer;transition:box-shadow .15s,transform .15s;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.aa-product-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.12);transform:translateY(-2px);}
.aa-product-card__img{position:relative;height:180px;background:#f0f2f5;overflow:hidden;}
.aa-product-card__img img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.aa-product-card:hover .aa-product-card__img img{transform:scale(1.04);}
.aa-product-card__noimg{height:100%;display:flex;align-items:center;justify-content:center;font-size:50px;color:#cbd5e1;}
.aa-product-card__stock{position:absolute;top:8px;right:8px;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.5px;}
.aa-product-card__stock--in{background:#dcfce7;color:#166534;}
.aa-product-card__stock--out{background:#fee2e2;color:#991b1b;}
.aa-product-card__body{padding:12px 14px;}
.aa-product-card__name{font-weight:700;font-size:13px;line-height:1.4;margin-bottom:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.aa-product-card__sku{font-size:11px;color:#94a3b8;margin-bottom:3px;}
.aa-product-card__cat{font-size:11px;color:#64748b;margin-bottom:6px;}
.aa-product-card__price{font-size:14px;font-weight:700;color:#1a6b3c;margin-bottom:10px;}
.aa-product-card__actions{display:flex;gap:6px;}
</style>
