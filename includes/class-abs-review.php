<?php
/** Native WooCommerce product reviews and moderation. @package AttarBrasilStorefront */

defined( 'ABSPATH' ) || exit;

final class ABS_Reviews {
	private static $instance = null;
	public static function instance() { if ( null === self::$instance ) self::$instance = new self(); return self::$instance; }

	private function __construct() {
		add_action( 'template_redirect', array( $this, 'submit' ) );
		add_action( 'admin_init', array( $this, 'moderate' ) );
		add_action( 'delete_comment', array( $this, 'delete_photos' ) );
	}

	public function submit() {
		if ( empty( $_POST['abs_review_submit'] ) ) return;
		$product_id = isset( $_POST['abs_review_product'] ) ? absint( $_POST['abs_review_product'] ) : 0;
		if ( ! is_user_logged_in() || ! $product_id || 'product' !== get_post_type( $product_id ) ) return;
		if ( empty( $_POST['abs_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abs_review_nonce'] ) ), 'abs_submit_review_' . $product_id ) ) return;
		$rating = isset( $_POST['abs_review_rating'] ) ? min( 5, max( 1, absint( $_POST['abs_review_rating'] ) ) ) : 0;
		$content = isset( $_POST['abs_review_content'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['abs_review_content'] ) ) ) : '';
		if ( ! $rating || strlen( $content ) < 10 ) {
			wp_safe_redirect( add_query_arg( 'abs_review', 'invalid', get_permalink( $product_id ) ) . '#avaliacoes' ); exit;
		}
		$user = wp_get_current_user();
		$existing = get_comments( array( 'post_id' => $product_id, 'user_id' => $user->ID, 'type' => 'review', 'count' => true, 'status' => 'all' ) );
		if ( $existing ) { wp_safe_redirect( add_query_arg( 'abs_review', 'duplicate', get_permalink( $product_id ) ) . '#avaliacoes' ); exit; }
		$comment_id = wp_insert_comment( array( 'comment_post_ID' => $product_id, 'comment_author' => $user->display_name, 'comment_author_email' => $user->user_email, 'comment_content' => $content, 'comment_type' => 'review', 'comment_parent' => 0, 'user_id' => $user->ID, 'comment_approved' => 0 ) );
		if ( $comment_id ) {
			add_comment_meta( $comment_id, 'rating', $rating, true );
			$this->upload_photos( $comment_id, $product_id );
		}
		wp_safe_redirect( add_query_arg( 'abs_review', $comment_id ? 'submitted' : 'error', get_permalink( $product_id ) ) . '#avaliacoes' ); exit;
	}

	/** Store up to five customer photos as Media Library attachments. */
	private function upload_photos( $comment_id, $product_id ) {
		if ( empty( $_FILES['abs_review_photos']['name'] ) || ! is_array( $_FILES['abs_review_photos']['name'] ) ) return; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$files = $_FILES['abs_review_photos']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
		$attachment_ids = array();
		$count = min( 5, count( $files['name'] ) );

		for ( $index = 0; $index < $count; $index++ ) {
			if ( empty( $files['name'][ $index ] ) || UPLOAD_ERR_OK !== (int) $files['error'][ $index ] || (int) $files['size'][ $index ] > 5 * MB_IN_BYTES ) continue;
			$file = array(
				'name'     => sanitize_file_name( wp_unslash( $files['name'][ $index ] ) ),
				'type'     => sanitize_mime_type( $files['type'][ $index ] ),
				'tmp_name' => $files['tmp_name'][ $index ], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'error'    => (int) $files['error'][ $index ],
				'size'     => (int) $files['size'][ $index ],
			);
			$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
			if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $allowed, true ) ) continue;
			$attachment_id = media_handle_sideload( $file, $product_id, get_the_title( $product_id ) );
			if ( is_wp_error( $attachment_id ) ) continue;
			$number = self::unique_alt_number();
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', get_the_title( $product_id ) . ' ' . $number );
			update_post_meta( $attachment_id, '_abs_review_alt_number', $number );
			update_post_meta( $attachment_id, '_abs_review_photo', $comment_id );
			$attachment_ids[] = absint( $attachment_id );
		}
		if ( $attachment_ids ) add_comment_meta( $comment_id, 'abs_review_photos', $attachment_ids, true );
	}

	/** Generate an eight-digit number not already used by a review image ALT. */
	private static function unique_alt_number() {
		for ( $attempt = 0; $attempt < 30; $attempt++ ) {
			$number = (string) wp_rand( 10000000, 99999999 );
			$used = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_abs_review_alt_number', 'meta_value' => $number ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			if ( ! $used ) return $number;
		}
		return (string) time() . wp_rand( 100, 999 );
	}

	/** Remove review-owned attachments when the comment is permanently deleted. */
	public function delete_photos( $comment_id ) {
		foreach ( (array) get_comment_meta( $comment_id, 'abs_review_photos', true ) as $attachment_id ) {
			if ( (int) get_post_meta( $attachment_id, '_abs_review_photo', true ) === (int) $comment_id ) wp_delete_attachment( absint( $attachment_id ), true );
		}
	}

	private static function photos( $comment_id, $admin = false ) {
		$ids = array_filter( array_map( 'absint', (array) get_comment_meta( $comment_id, 'abs_review_photos', true ) ) );
		if ( ! $ids ) return '';
		$class = $admin ? 'abs-review-photos' : 'attar-review__photos';
		$html = '<div class="' . esc_attr( $class ) . '">';
		foreach ( $ids as $id ) {
			$image = wp_get_attachment_image( $id, $admin ? 'thumbnail' : 'medium', false, array( 'loading' => 'lazy' ) );
			$full = wp_get_attachment_image_url( $id, 'full' );
			if ( $image && $full ) $html .= '<a href="' . esc_url( $full ) . '" target="_blank" rel="noopener">' . $image . '</a>';
		}
		return $html . '</div>';
	}

	public function moderate() {
		if ( empty( $_GET['page'] ) || 'attar-storefront' !== $_GET['page'] || empty( $_GET['abs_review_action'] ) || empty( $_GET['comment_id'] ) || ! current_user_can( 'moderate_comments' ) ) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = absint( $_GET['comment_id'] );
		check_admin_referer( 'abs_review_' . $id );
		$comment = get_comment( $id );
		if ( ! $comment || 'product' !== get_post_type( $comment->comment_post_ID ) || 'review' !== $comment->comment_type ) return;
		$product_id = absint( $comment->comment_post_ID );
		$action = sanitize_key( $_GET['abs_review_action'] );
		if ( 'approve' === $action ) wp_set_comment_status( $id, 'approve' );
		if ( 'hold' === $action ) wp_set_comment_status( $id, 'hold' );
		if ( 'trash' === $action ) wp_trash_comment( $id );
		if ( class_exists( 'WC_Comments' ) ) WC_Comments::clear_transients( $product_id );
		wp_safe_redirect( admin_url( 'admin.php?page=attar-storefront&tab=reviews&updated=1' ) ); exit;
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'produto_id' => 0, 'titulo' => 'Avaliações do produto' ), (array) $atts, 'attar_avaliacoes_produto' );
		$product_id = absint( $atts['produto_id'] );
		if ( ! $product_id && is_product() ) $product_id = get_queried_object_id();
		$product = wc_get_product( $product_id );
		if ( ! $product ) return '';
		$reviews = get_comments( array( 'post_id' => $product_id, 'status' => 'approve', 'type' => 'review', 'orderby' => 'comment_date_gmt', 'order' => 'DESC' ) );
		$message = isset( $_GET['abs_review'] ) ? sanitize_key( $_GET['abs_review'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		ob_start(); ?>
		<section id="avaliacoes" class="attar-reviews" data-product-id="<?php echo esc_attr( $product_id ); ?>"><div class="attar-reviews__header"><h2><?php echo esc_html( $atts['titulo'] ); ?></h2><span><?php echo esc_html( sprintf( _n( '%s avaliação', '%s avaliações', count( $reviews ), 'attar-brasil-storefront' ), number_format_i18n( count( $reviews ) ) ) ); ?></span></div>
		<?php if ( 'submitted' === $message ) : ?><p class="attar-reviews__notice is-success">Sua avaliação foi enviada e será publicada após aprovação.</p><?php elseif ( 'invalid' === $message ) : ?><p class="attar-reviews__notice is-error">Escolha uma nota e escreva pelo menos 10 caracteres.</p><?php elseif ( 'duplicate' === $message ) : ?><p class="attar-reviews__notice is-error">Você já avaliou este produto.</p><?php endif; ?>
		<div class="attar-reviews__list"><?php if ( ! $reviews ) : ?><p>Ainda não há avaliações aprovadas para este produto.</p><?php endif; foreach ( $reviews as $review ) : $rating = absint( get_comment_meta( $review->comment_ID, 'rating', true ) ); ?><article class="attar-review"><div class="attar-review__stars" aria-label="<?php echo esc_attr( $rating . ' de 5 estrelas' ); ?>"><?php echo esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ); ?></div><p><?php echo esc_html( $review->comment_content ); ?></p><?php echo self::photos( $review->comment_ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><footer><strong><?php echo esc_html( $review->comment_author ); ?></strong> · <time datetime="<?php echo esc_attr( get_comment_date( DATE_W3C, $review ) ); ?>"><?php echo esc_html( get_comment_date( 'd/m/Y', $review ) ); ?></time></footer></article><?php endforeach; ?></div>
		<div class="attar-review-form"><h3>Avalie este produto</h3><?php if ( is_user_logged_in() ) : ?><form method="post" enctype="multipart/form-data" action="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php wp_nonce_field( 'abs_submit_review_' . $product_id, 'abs_review_nonce' ); ?><input type="hidden" name="abs_review_product" value="<?php echo esc_attr( $product_id ); ?>"><fieldset><legend>Sua nota</legend><div class="attar-rating-input"><?php for ( $i = 5; $i >= 1; $i-- ) : ?><input required type="radio" id="abs-rating-<?php echo esc_attr( $product_id . '-' . $i ); ?>" name="abs_review_rating" value="<?php echo esc_attr( $i ); ?>"><label for="abs-rating-<?php echo esc_attr( $product_id . '-' . $i ); ?>" title="<?php echo esc_attr( $i ); ?> estrelas">★</label><?php endfor; ?></div></fieldset><label>Seu comentário<textarea name="abs_review_content" rows="5" minlength="10" required></textarea></label><label class="attar-review-form__photos">Fotos do produto <small>Até 5 imagens em JPG, PNG ou WebP, com no máximo 5 MB cada.</small><input type="file" name="abs_review_photos[]" accept="image/jpeg,image/png,image/webp" multiple></label><button type="submit" name="abs_review_submit" value="1">Enviar avaliação</button></form><?php else : ?><p><a href="<?php echo esc_url( wp_login_url( get_permalink( $product_id ) . '#avaliacoes' ) ); ?>">Entre na sua conta</a> para avaliar este produto.</p><?php endif; ?></div></section>
		<?php return (string) ob_get_clean();
	}

	public static function admin_table() {
		$status = isset( $_GET['review_status'] ) ? sanitize_key( $_GET['review_status'] ) : 'hold'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$comments = get_comments( array( 'post_type' => 'product', 'type' => 'review', 'status' => in_array( $status, array( 'hold', 'approve' ), true ) ? $status : 'hold', 'number' => 100 ) ); ?>
		<div class="abs-box" style="margin-top:20px"><h2>Moderação de avaliações</h2><p><a href="<?php echo esc_url( admin_url( 'admin.php?page=attar-storefront&tab=reviews&review_status=hold' ) ); ?>">Pendentes</a> · <a href="<?php echo esc_url( admin_url( 'admin.php?page=attar-storefront&tab=reviews&review_status=approve' ) ); ?>">Aprovadas</a></p><table class="abs-table"><thead><tr><th>Produto</th><th>Cliente</th><th>Nota</th><th>Avaliação e fotos</th><th>Ações</th></tr></thead><tbody><?php if ( ! $comments ) : ?><tr><td colspan="5">Nenhuma avaliação nesta seção.</td></tr><?php endif; foreach ( $comments as $comment ) : $rating = absint( get_comment_meta( $comment->comment_ID, 'rating', true ) ); ?><tr><td><a href="<?php echo esc_url( get_permalink( $comment->comment_post_ID ) ); ?>"><?php echo esc_html( get_the_title( $comment->comment_post_ID ) ); ?></a></td><td><?php echo esc_html( $comment->comment_author ); ?></td><td><?php echo esc_html( $rating . '/5' ); ?></td><td><?php echo esc_html( $comment->comment_content ); ?><?php echo self::photos( $comment->comment_ID, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td><td><?php if ( '1' !== $comment->comment_approved ) : ?><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=attar-storefront&tab=reviews&abs_review_action=approve&comment_id=' . $comment->comment_ID ), 'abs_review_' . $comment->comment_ID ) ); ?>">Aprovar</a> · <?php else : ?><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=attar-storefront&tab=reviews&abs_review_action=hold&comment_id=' . $comment->comment_ID ), 'abs_review_' . $comment->comment_ID ) ); ?>">Retirar</a> · <?php endif; ?><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=attar-storefront&tab=reviews&abs_review_action=trash&comment_id=' . $comment->comment_ID ), 'abs_review_' . $comment->comment_ID ) ); ?>">Lixeira</a></td></tr><?php endforeach; ?></tbody></table></div>
	<?php }
}
