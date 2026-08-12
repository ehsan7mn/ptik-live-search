<?php
/**
 * Plugin Name: جستجوی زنده
 * Plugin URI: https://persiantik.net
 * Description: جستجوی زنده (AJAX) بین نوشته‌ها، برگه‌ها، محصولات و تاکسونومی‌های سایت با نمایش نتایج گروه‌بندی‌شده بر اساس نوع محتوا. با شورتکد [ptik_search] در هرجای سایت قابل استفاده است.
 * Version: 1.4.0
 * Author: امیراحسان مهدیزاده
 * Author URI: https://persiantik.net
 * Text Domain: ptik-live-search
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // دسترسی مستقیم به فایل مجاز نیست
}

define( 'PTIK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PTIK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PTIK_OPTION_KEY', 'ptik_search_settings' );

/* -------------------------------------------------------------------------
 * بروزرسانی خودکار از گیت‌هاب (Plugin Update Checker)
 * راهنمای کامل نصب: پایین همین فایل / یا در گفتگو با Claude
 * ---------------------------------------------------------------------- */

if ( file_exists( PTIK_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once PTIK_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

	// TODO: آدرس ریپازیتوری گیت‌هاب واقعی خودت رو اینجا جایگزین کن
	$ptik_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/ehsan7mn/ptik-live-search/',
		__FILE__,
		'ptik-live-search'
	);

	// چون ریپازیتوری خصوصی (private) است، به یک GitHub Personal Access Token نیاز دارد.
	// توکن را مستقیم اینجا ننویس؛ در wp-config.php این خط رو اضافه کن:
	// define( 'PTIK_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
	if ( defined( 'PTIK_GITHUB_TOKEN' ) && PTIK_GITHUB_TOKEN ) {
		$ptik_update_checker->setAuthentication( PTIK_GITHUB_TOKEN );
	}

	// بروزرسانی بر اساس Release های گیت‌هاب (نه صرفاً آخرین کامیت main/master)
	$ptik_update_checker->getVcsApi()->enableReleaseAssets();
}

/* -------------------------------------------------------------------------
 * صفحه تنظیمات پلاگین (زیرمنوی ووکامرس)
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', 'ptik_add_settings_page', 20 );
function ptik_add_settings_page() {
	// اگر یکی از افزونه‌های مدیریتی «پرشین‌تیک» نصب/فعال باشد، منو زیرمجموعه‌ی همون بشود
	$parent_slug = null;
	if ( ptik_admin_menu_slug_exists( 'persiantik_shop_themes' ) ) {
		$parent_slug = 'persiantik_shop_themes';
	} elseif ( ptik_admin_menu_slug_exists( 'persiantik_themes' ) ) {
		$parent_slug = 'persiantik_themes';
	}

	if ( $parent_slug ) {
		add_submenu_page(
			$parent_slug,
			'جستجوی زنده',
			'جستجوی زنده',
			'manage_options',
			'ptik-live-search',
			'ptik_render_settings_page'
		);
		return;
	}

	// در غیر این صورت، به‌صورت یک منوی مستقل درست قبل از «تنظیمات» نمایش داده شود
	add_menu_page(
		'جستجوی زنده',
		'جستجوی زنده',
		'manage_options',
		'ptik-live-search',
		'ptik_render_settings_page',
		'dashicons-search',
		79
	);
}

/**
 * بررسی می‌کند آیا منوی ادمین با این slug از قبل ثبت شده یا نه
 * (برای تشخیص فعال بودن افزونه‌های persiantik_themes / persiantik_shop_themes از روی منوشان،
 * چون این افزونه‌ها هنوز نوشته نشده‌اند و نمی‌دانیم مسیر فایل اصلی‌شان دقیقاً چیست).
 * این تابع با priority=20 روی admin_menu اجرا می‌شود تا مطمئن شود آن افزونه‌ها
 * (که معمولاً priority پیش‌فرض 10 دارند) قبل از این، منوی خودشان را ثبت کرده باشند.
 */
function ptik_admin_menu_slug_exists( $slug ) {
	global $menu;
	if ( empty( $menu ) || ! is_array( $menu ) ) {
		return false;
	}
	foreach ( $menu as $item ) {
		if ( isset( $item[2] ) && $item[2] === $slug ) {
			return true;
		}
	}
	return false;
}

add_action( 'admin_init', 'ptik_register_settings' );
function ptik_register_settings() {
	register_setting( 'ptik_search_settings_group', PTIK_OPTION_KEY, 'ptik_sanitize_settings' );
}

function ptik_sanitize_settings( $input ) {
	$output                       = array();
	$output['show_more_button']   = ! empty( $input['show_more_button'] ) ? 1 : 0;
	return $output;
}

function ptik_get_settings() {
	$defaults = array(
		'show_more_button' => 1,
	);
	$saved    = get_option( PTIK_OPTION_KEY, array() );
	return wp_parse_args( $saved, $defaults );
}

function ptik_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = ptik_get_settings();
	?>
	<div class="wrap">
		<h1>تنظیمات جستجوی زنده</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'ptik_search_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">دکمه «نتایج بیشتر»</th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( PTIK_OPTION_KEY ); ?>[show_more_button]"
								value="1" <?php checked( $settings['show_more_button'], 1 ); ?>>
							نمایش دکمه‌ای در انتهای نتایج که به صفحه جستجوی عادی وردپرس (همان کلمه) لینک می‌دهد
						</label>
					</td>
				</tr>
			</table>
			<p class="description">تعداد نتایج هر گروه از طریق مشخصه <code>count</code> در خود شورتکد تنظیم می‌شود، مثلاً <code>[ptik_search source="product" count="8"]</code>.</p>
			<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * لود استایل و اسکریپت (باید حتماً روی هوک wp_enqueue_scripts باشد، نه داخل
 * تابع شورتکد؛ چون شورتکد دیر اجرا می‌شود و wp_head دیگر منتظرش نمی‌ماند)
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'ptik_register_assets' );
function ptik_register_assets() {
	wp_enqueue_style( 'ptik-search-css', PTIK_PLUGIN_URL . 'assets/ptik-search.css', array(), '1.4.0' );
	wp_enqueue_script( 'ptik-search-js', PTIK_PLUGIN_URL . 'assets/ptik-search.js', array(), '1.4.0', true );
	wp_localize_script(
		'ptik-search-js',
		'ptikSearchAjax',
		array(
			'url' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/* -------------------------------------------------------------------------
 * شورتکد
 * مثال: [ptik_search source="product,post,page" taxonomy="product_cat,category" image="true" price="true"]
 * ---------------------------------------------------------------------- */

add_shortcode( 'ptik_search', 'ptik_search_func' );
function ptik_search_func( $atts ) {
	static $ptik_instance = 0;
	$ptik_instance++;

	$atts = shortcode_atts(
		array(
			'source'      => '',
			'taxonomy'    => '',
			'image'       => 'true',
			'price'       => 'false',
			'placeholder' => 'کد یا نام محصول را وارد کنید',
			'count'       => '5',
			'depth'       => '0',
			'absolute'    => 'false',
			'style'       => 'default',
		),
		$atts,
		'ptik_search'
	);

	// اگر نه source و نه taxonomy مشخص نشده بود، طبق رفتار قبلی فقط محصولات جست‌وجو شود
	if ( '' === trim( $atts['source'] ) && '' === trim( $atts['taxonomy'] ) ) {
		$atts['source'] = 'product';
	}

	$valid_styles = array( 'default', 'glass1', 'solid-primary', 'outline-primary', 'gradient-spin', 'shine-border', 'minoru', 'madoka' );
	$style        = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'default';

	$is_absolute = 'true' === $atts['absolute'];
	$form_id     = 'ptik-search-' . $ptik_instance;

	$wrapper_class = 'search_bar ptik-style-' . $style . ( $is_absolute ? ' position-relative' : '' );
	$result_class  = 'search_result' . ( $is_absolute ? ' position-absolute w-100' : '' );

	// آیکون ذره‌بین ساده، در بیشتر استایل‌ها استفاده می‌شود
	$magnifier = '<svg class="ptik-icon-magnifier" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.2" y2="16.2"/></svg>';

	$form_attrs  = ' data-source="' . esc_attr( $atts['source'] ) . '"';
	$form_attrs .= ' data-taxonomy="' . esc_attr( $atts['taxonomy'] ) . '"';
	$form_attrs .= ' data-image="' . esc_attr( $atts['image'] ) . '"';
	$form_attrs .= ' data-price="' . esc_attr( $atts['price'] ) . '"';
	$form_attrs .= ' data-count="' . esc_attr( max( 1, intval( $atts['count'] ) ) ) . '"';
	$form_attrs .= ' data-depth="' . esc_attr( max( 0, intval( $atts['depth'] ) ) ) . '"';

	$input_attr_placeholder = esc_attr( $atts['placeholder'] );

	$html  = '<div class="' . esc_attr( $wrapper_class ) . '">';
	$html .= '<form class="ptik-form ptik-form--' . esc_attr( $style ) . '" id="' . esc_attr( $form_id ) . '" action="/" method="get" autocomplete="off"' . $form_attrs . '>';

	switch ( $style ) {

		case 'glass1':
			$html .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search" autocomplete="off">';
			$html .= '<svg class="ptik-eyes" width="42" height="22" viewBox="0 0 44 30" aria-hidden="true">';
			$html .= '<ellipse cx="12" cy="15" rx="8" ry="11" fill="none" stroke="currentColor" stroke-width="1.5"/>';
			$html .= '<ellipse cx="32" cy="15" rx="8" ry="11" fill="none" stroke="currentColor" stroke-width="1.5"/>';
			$html .= '<circle class="ptik-pupil" cx="12" cy="15" r="4.3" fill="currentColor"/>';
			$html .= '<circle class="ptik-pupil" cx="32" cy="15" r="4.3" fill="currentColor"/>';
			$html .= '</svg>';
			break;

		case 'solid-primary':
		case 'outline-primary':
			$html .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search" autocomplete="off">';
			$html .= '<button type="button" class="ptik-circle-btn">' . $magnifier . '</button>';
			break;

		case 'gradient-spin':
			$mask_id = 'ptik-ring-mask-' . $ptik_instance;
			$html   .= '<svg class="ptik-gradient-ring" width="100%" height="100%" viewBox="0 0 380 58" preserveAspectRatio="none">';
			$html   .= '<defs><linearGradient id="ptik-ring-grad-' . $ptik_instance . '" gradientUnits="userSpaceOnUse" x1="0" y1="29" x2="380" y2="29">';
			$html   .= '<stop offset="0%" stop-color="#2f8fe0"/><stop offset="25%" stop-color="#b13fd6"/><stop offset="50%" stop-color="#2f8fe0"/><stop offset="75%" stop-color="#b13fd6"/><stop offset="100%" stop-color="#2f8fe0"/>';
			$html   .= '<animateTransform attributeName="gradientTransform" type="rotate" from="0 190 29" to="360 190 29" dur="3s" repeatCount="indefinite"/>';
			$html   .= '</linearGradient>';
			$html   .= '<mask id="' . esc_attr( $mask_id ) . '"><rect x="0" y="0" width="380" height="58" rx="29" ry="29" fill="#fff"/><rect x="3" y="3" width="374" height="52" rx="26" ry="26" fill="#000"/></mask>';
			$html   .= '</defs>';
			$html   .= '<rect x="0" y="0" width="380" height="58" rx="29" ry="29" fill="url(#ptik-ring-grad-' . $ptik_instance . ')" mask="url(#' . esc_attr( $mask_id ) . ')"/>';
			$html   .= '</svg>';
			$html   .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search" autocomplete="off">';
			break;

		case 'shine-border':
			$html .= '<div class="ptik-shine-frame"><div class="ptik-shine-light"></div><div class="ptik-shine-light"></div></div>';
			$html .= '<div class="ptik-shine-glow-orb"></div><div class="ptik-shine-glow-orb"></div>';
			$html .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search" autocomplete="off">';
			break;

		case 'minoru':
			$html .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search ptik-input--minoru" id="' . esc_attr( $form_id ) . '-field">';
			$html .= '<label class="ptik-label--minoru" for="' . esc_attr( $form_id ) . '-field"><span class="ptik-minoru-icon">' . $magnifier . '</span></label>';
			break;

		case 'madoka':
			$html .= '<span class="ptik-input-wrap--madoka">';
			$html .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search ptik-input--madoka" id="' . esc_attr( $form_id ) . '-field">';
			$html .= '<label class="ptik-label--madoka" for="' . esc_attr( $form_id ) . '-field">';
			$html .= '<svg class="ptik-graphic--madoka" width="100%" height="100%" viewBox="0 0 404 77" preserveAspectRatio="none"><path vector-effect="non-scaling-stroke" d="m0,0l404,0l0,77l-404,0l0,-77z"></path></svg>';
			$html .= '<span class="ptik-madoka-icon">' . $magnifier . '</span>';
			$html .= '</label>';
			$html .= '</span>';
			break;

		default:
			$html .= '<input type="text" name="s" placeholder="' . $input_attr_placeholder . '" class="input_search" autocomplete="off">';
			$html .= '<button type="button" class="mybtn">' . $magnifier . '</button>';
			break;
	}

	$html .= '</form>';
	$html .= '<div class="' . esc_attr( $result_class ) . '" style="display:none;"><ul><li><div class="loading_search"></div></li></ul></div>';
	$html .= '</div>';

	return $html;
}

/* -------------------------------------------------------------------------
 * پردازش AJAX
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_ptik_search', 'ptik_handle_ajax' );
add_action( 'wp_ajax_nopriv_ptik_search', 'ptik_handle_ajax' );
function ptik_handle_ajax() {

	$keyword = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
	if ( '' === $keyword ) {
		wp_die();
	}

	$settings  = ptik_get_settings();
	$per_group = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 5;
	$per_group = min( 50, max( 1, $per_group ) );
	$max_depth = isset( $_POST['depth'] ) ? max( 0, intval( $_POST['depth'] ) ) : 0;

	$requested_sources    = isset( $_POST['source'] ) ? array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['source'] ) ) ) ) ) : array();
	$requested_taxonomies = isset( $_POST['taxonomy'] ) ? array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) ) ) ) : array();
	$show_image           = isset( $_POST['image'] ) && 'true' === $_POST['image'];
	$show_price           = isset( $_POST['price'] ) && 'true' === $_POST['price'];

	// فقط پست‌تایپ‌ها و تاکسونومی‌های عمومیِ واقعاً موجود در سایت پذیرفته می‌شوند (جلوگیری از ورودی مخرب)
	$valid_post_types = get_post_types( array( 'public' => true ), 'names' );
	$valid_taxonomies = get_taxonomies( array( 'public' => true ), 'names' );

	$sources    = array_intersect( $requested_sources, $valid_post_types );
	$taxonomies = array_intersect( $requested_taxonomies, $valid_taxonomies );

	$output    = '';
	$found_any = false;

	// --- گروه‌های پست‌تایپ (محصول، نوشته، برگه و ...) ---
	foreach ( $sources as $post_type ) {
		$pt_object = get_post_type_object( $post_type );
		if ( ! $pt_object ) {
			continue;
		}

		$q = new WP_Query(
			array(
				's'                   => $keyword,
				'post_type'           => $post_type,
				'posts_per_page'      => $per_group,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
			)
		);

		if ( $q->have_posts() ) {
			$found_any = true;
			$output   .= '<div class="ptik-group ptik-group-' . esc_attr( $post_type ) . '">';
			$output   .= '<h4 class="ptik-group-title">' . esc_html( $pt_object->labels->name ) . '</h4><ul>';

			while ( $q->have_posts() ) {
				$q->the_post();
				$output .= '<li><a href="' . esc_url( get_permalink() ) . '">';
				$output .= '<span class="ptik-info">';
				if ( $show_image ) {
					$thumb = get_the_post_thumbnail( get_the_ID(), 'woocommerce_gallery_thumbnail' );
					if ( '' === $thumb && function_exists( 'wc_placeholder_img' ) ) {
						$thumb = wc_placeholder_img( array( 60, 60 ) );
					}
					$output .= $thumb;
				}
				$output .= '<span class="ptik-title">' . esc_html( get_the_title() ) . '</span>';
				$output .= '</span>';

				if ( $show_price && 'product' === $post_type && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( get_the_ID() );
					if ( $product ) {
						$output .= '<span class="ptik-price">' . $product->get_price_html() . '</span>';
					}
				}
				$output .= '</a></li>';
			}
			$output .= '</ul></div>';
			wp_reset_postdata();
		}
	}

	// --- گروه‌های تاکسونومی (دسته‌بندی محصول، دسته‌بندی نوشته، برند و ...) ---
	foreach ( $taxonomies as $taxonomy ) {
		$tax_object = get_taxonomy( $taxonomy );
		if ( ! $tax_object ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'name__like' => $keyword,
				'number'     => $max_depth > 0 ? 0 : $per_group,
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) && $max_depth > 0 && ! empty( $terms ) ) {
			$terms = array_values(
				array_filter(
					$terms,
					function ( $term ) use ( $taxonomy, $max_depth ) {
						$level = count( get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) ) + 1;
						return $level <= $max_depth;
					}
				)
			);
			$terms = array_slice( $terms, 0, $per_group );
		}

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$found_any = true;

			// تعیین برچسب شمارش بر اساس نوع محتوایی که این تاکسونومی به آن تعلق دارد
			$object_types = (array) $tax_object->object_type;
			if ( in_array( 'product', $object_types, true ) ) {
				$count_label = 'محصول';
			} elseif ( in_array( 'page', $object_types, true ) && 1 === count( $object_types ) ) {
				$count_label = ''; // برگه: چیزی نمایش داده نمی‌شود
			} else {
				$count_label = 'نوشته';
			}

			$output .= '<div class="ptik-group ptik-group-' . esc_attr( $taxonomy ) . '">';
			$output .= '<h4 class="ptik-group-title">' . esc_html( $tax_object->labels->name ) . '</h4><ul>';

			foreach ( $terms as $term ) {
				$output .= '<li><a href="' . esc_url( get_term_link( $term ) ) . '">';
				$output .= '<span class="ptik-info">';
				if ( $show_image ) {
					// اگر ووکامرس نصب باشد، دسته‌بندی محصول تصویر شاخص دارد
					$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
					if ( $thumb_id ) {
						$output .= wp_get_attachment_image( $thumb_id, array( 60, 60 ) );
					} elseif ( function_exists( 'wc_placeholder_img' ) ) {
						$output .= wc_placeholder_img( array( 60, 60 ) );
					}
				}
				$output .= '<span class="ptik-title">' . esc_html( $term->name ) . '</span>';
				$output .= '</span>';

				if ( '' !== $count_label ) {
					$output .= '<span class="ptik-count">' . intval( $term->count ) . ' ' . esc_html( $count_label ) . '</span>';
				}
				$output .= '</a></li>';
			}
			$output .= '</ul></div>';
		}
	}

	if ( ! $found_any ) {
		$output = '<ul><li>محتوایی یافت نشد ...</li></ul>';
	} elseif ( ! empty( $settings['show_more_button'] ) ) {
		$search_url = get_search_link( $keyword );
		$output    .= '<div class="ptik-more-wrap"><a href="' . esc_url( $search_url ) . '" class="btn btn-primary">نتایج بیشتر</a></div>';
	}

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput -- تک‌تک بخش‌ها بالاتر escape شده‌اند
	wp_die();
}
