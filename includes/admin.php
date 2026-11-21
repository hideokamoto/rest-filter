<?php
/**
 * Wp-admin surface: Settings > REST Filter.
 *
 * @package RestFilter
 */

add_action( 'admin_menu', 'rest_filter_register_settings_page' );
add_action( 'admin_init', 'rest_filter_register_setting' );

/**
 * Register the options page under Settings.
 *
 * @return void
 */
function rest_filter_register_settings_page() {
	add_options_page(
		'REST Filter',
		'REST Filter',
		'manage_options',
		'rest-filter',
		'rest_filter_render_settings_page'
	);
}

/**
 * Register the whitelist option with the Settings API.
 *
 * @return void
 */
function rest_filter_register_setting() {
	register_setting(
		'rest_filter',
		REST_FILTER_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'rest_filter_sanitize_option_input',
		)
	);
}

/**
 * Render the whitelist editor (one var per line) with status and warnings.
 *
 * @return void
 */
function rest_filter_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$saved       = get_option( REST_FILTER_OPTION, null );
	$is_override = is_array( $saved );
	$current     = rest_filter_allowed_vars();
	$candidates  = rest_filter_default_allowed_vars();
	$risky       = array_intersect( $current, REST_FILTER_RISKY_VARS );
	?>
	<div class="wrap">
		<h1>REST Filter</h1>
		<p>
			REST API の投稿コレクションに <code>filter[...]</code> パラメータ経由で渡せる
			<code>WP_Query</code> 変数の許可リスト。1行1変数名。
		</p>
		<p>
			現在の状態:
			<?php if ( $is_override ) : ?>
				<strong>option で上書き中</strong>（<code><?php echo esc_html( REST_FILTER_OPTION ); ?></code>）
			<?php else : ?>
				<strong>デフォルト</strong>（公開クエリ変数 + meta 系。保存すると上書きになる）
			<?php endif; ?>
		</p>
		<?php if ( ! empty( $risky ) ) : ?>
			<div class="notice notice-warning"><p>
				警告: 次の変数は非公開データの参照や重いクエリに使えます —
				<code><?php echo esc_html( implode( ', ', $risky ) ); ?></code>
			</p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'rest_filter' ); ?>
			<textarea
				name="<?php echo esc_attr( REST_FILTER_OPTION ); ?>"
				rows="14" cols="40" class="large-text code"
			><?php echo esc_textarea( implode( "\n", $current ) ); ?></textarea>
			<p class="description">
				候補（公開クエリ変数 + meta 系）:
				<code><?php echo esc_html( implode( ', ', $candidates ) ); ?></code><br>
				コード側の <code>rest_query_vars</code> フィルタはこのリストに常に後段適用されます。
				空にすると保存されません（誤って全拒否になるのを防ぐため）。
			</p>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
