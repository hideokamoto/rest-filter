<?php
/**
 * Wp-admin surface: Settings > REST Filter.
 *
 * @package RestFilter
 */

add_action( 'admin_menu', 'rest_filter_register_settings_page' );
add_action( 'admin_init', 'rest_filter_register_setting' );
add_action( 'admin_post_rest_filter_post_types', 'rest_filter_save_post_types' );

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
	$saved           = get_option( REST_FILTER_OPTION, null );
	$is_override     = is_array( $saved );
	$current         = rest_filter_allowed_vars();
	$candidates      = rest_filter_default_allowed_vars();
	$risky           = array_intersect( $current, REST_FILTER_RISKY_VARS );
	$types_saved     = get_option( REST_FILTER_POST_TYPES_OPTION, null );
	$types_override  = is_array( $types_saved );
	$types_current   = rest_filter_post_types();
	$types_available = rest_filter_default_post_types();
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

		<hr>
		<h2>filter を有効にする投稿タイプ</h2>
		<p>
			<code>filter[...]</code> を受け付ける投稿タイプ。
			未設定（デフォルト）では <code>show_in_rest</code> な全タイプに適用されます。
			現在の状態:
			<?php if ( $types_override ) : ?>
				<strong>option で上書き中</strong>（<code><?php echo esc_html( REST_FILTER_POST_TYPES_OPTION ); ?></code>）
			<?php else : ?>
				<strong>デフォルト</strong>（全タイプ）
			<?php endif; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="rest_filter_post_types">
			<?php wp_nonce_field( 'rest_filter_post_types_save' ); ?>
			<fieldset>
				<?php foreach ( $types_available as $type ) : ?>
					<label style="display:inline-block;margin-right:1em;">
						<input type="checkbox" name="rest_filter_post_types[]" value="<?php echo esc_attr( $type ); ?>"
							<?php checked( in_array( $type, $types_current, true ) ); ?>>
						<?php echo esc_html( $type ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<p>
				<label>
					<input type="checkbox" name="rest_filter_post_types_all" value="1">
					すべての投稿タイプに適用（option を削除してデフォルトに戻す）
				</label>
			</p>
			<p class="description">
				チェックしたタイプだけに <code>filter</code> を有効化します。
				全部外して保存すると全タイプで無効になります（有効化し直すには1つ以上チェック）。
			</p>
			<?php submit_button( '投稿タイプを保存', 'secondary' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Admin-post.php handler for the post-type scope form.
 *
 * "all types" checked -> the option is deleted (default: every REST-enabled
 * type). Otherwise the checked types are saved; an empty selection disables
 * `filter` everywhere.
 *
 * @return void
 */
function rest_filter_save_post_types() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'rest_filter_post_types_save' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	if ( ! empty( $_POST['rest_filter_post_types_all'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		delete_option( REST_FILTER_POST_TYPES_OPTION );
	} else {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in rest_filter_sanitize_var_names; nonce verified above.
		$raw = isset( $_POST['rest_filter_post_types'] ) ? wp_unslash( $_POST['rest_filter_post_types'] ) : array();
		update_option( REST_FILTER_POST_TYPES_OPTION, array_values( array_intersect( rest_filter_sanitize_var_names( $raw ), rest_filter_default_post_types() ) ), false );
	}
	wp_safe_redirect( admin_url( 'options-general.php?page=rest-filter&rest-filter-types-updated=1' ) );
	exit;
}
