<?php
/**
 * Plugin Name: POCKETA Product Reviews
 * Description: Form ulasan produk POCKETA dengan fitur tambah, lihat, dan edit ulasan milik sendiri.
 * Version: 1.0.0
 * Author: POCKETA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Proses tambah dan edit ulasan.
 * Ulasan baru/perubahan masuk ke moderasi admin terlebih dahulu.
 */
add_action( 'init', 'pocketa_handle_product_review' );

function pocketa_handle_product_review() {
    if ( empty( $_POST['pocketa_review_submit'] ) ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    if (
        empty( $_POST['pocketa_review_nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['pocketa_review_nonce'] ) ),
            'pocketa_review_action'
        )
    ) {
        wp_die( 'Permintaan tidak valid. Silakan muat ulang halaman.' );
    }

    $product_id = isset( $_POST['pocketa_product_id'] )
        ? absint( $_POST['pocketa_product_id'] )
        : 0;

    if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
        wp_die( 'Produk tidak ditemukan.' );
    }

    $user = wp_get_current_user();

    $review_title = isset( $_POST['pocketa_review_title'] )
        ? sanitize_text_field( wp_unslash( $_POST['pocketa_review_title'] ) )
        : '';

    $review_content = isset( $_POST['pocketa_review_content'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['pocketa_review_content'] ) )
        : '';

    $rating = isset( $_POST['pocketa_rating'] )
        ? absint( $_POST['pocketa_rating'] )
        : 0;

    if ( '' === $review_title || '' === $review_content || $rating < 1 || $rating > 5 ) {
        pocketa_review_redirect( $product_id, 'invalid' );
    }

    $comment_id = isset( $_POST['pocketa_comment_id'] )
        ? absint( $_POST['pocketa_comment_id'] )
        : 0;

    if ( $comment_id ) {
        $existing = get_comment( $comment_id );

        // Pastikan ulasan benar-benar milik pengguna yang sedang login
        // dan terkait dengan produk yang sedang dibuka.
        if (
            ! $existing ||
            (int) $existing->user_id !== (int) $user->ID ||
            (int) $existing->comment_post_ID !== $product_id ||
            'review' !== $existing->comment_type
        ) {
            wp_die( 'Anda tidak memiliki izin untuk mengedit ulasan ini.' );
        }

        $updated = wp_update_comment(
            array(
                'comment_ID'      => $comment_id,
                'comment_content' => $review_content,
                'comment_approved' => 0, // Perubahan diperiksa admin kembali.
            )
        );

        if ( $updated ) {
            update_comment_meta( $comment_id, 'pocketa_review_title', $review_title );
            update_comment_meta( $comment_id, 'rating', $rating );
            pocketa_review_redirect( $product_id, 'updated' );
        }

        pocketa_review_redirect( $product_id, 'error' );
    }

    $new_comment_id = wp_insert_comment(
        array(
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_content'      => $review_content,
            'comment_type'         => 'review',
            'user_id'              => $user->ID,
            'comment_approved'     => 0, // Menunggu persetujuan admin.
        )
    );

    if ( $new_comment_id ) {
        update_comment_meta( $new_comment_id, 'pocketa_review_title', $review_title );
        update_comment_meta( $new_comment_id, 'rating', $rating );
        pocketa_review_redirect( $product_id, 'submitted' );
    }

    pocketa_review_redirect( $product_id, 'error' );
}

/**
 * Redirect agar browser tidak mengirim ulang form saat halaman di-refresh.
 */
function pocketa_review_redirect( $product_id, $status ) {
    $url = get_permalink( $product_id );
    $url = add_query_arg( 'pocketa_review_status', $status, $url );

    wp_safe_redirect( $url . '#pocketa-reviews' );
    exit;
}

/**
 * Shortcode: [pocketa_product_reviews]
 * Pasang shortcode ini pada halaman detail produk WooCommerce.
 */
add_shortcode( 'pocketa_product_reviews', 'pocketa_product_reviews_shortcode' );

function pocketa_product_reviews_shortcode() {
    if ( ! function_exists( 'wc_get_product' ) ) {
        return '<p>Fitur ulasan membutuhkan WooCommerce aktif.</p>';
    }

    global $product;

    $product_id = ( $product instanceof WC_Product )
        ? $product->get_id()
        : get_the_ID();

    if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
        return '<p>Ulasan hanya tersedia pada halaman detail produk.</p>';
    }

    $current_user_id = get_current_user_id();
    $edit_id = isset( $_GET['edit_review'] ) ? absint( $_GET['edit_review'] ) : 0;
    $editing_comment = null;

    if ( $edit_id && $current_user_id ) {
        $candidate = get_comment( $edit_id );

        if (
            $candidate &&
            (int) $candidate->user_id === $current_user_id &&
            (int) $candidate->comment_post_ID === (int) $product_id &&
            'review' === $candidate->comment_type
        ) {
            $editing_comment = $candidate;
        }
    }

    $status = isset( $_GET['pocketa_review_status'] )
        ? sanitize_key( wp_unslash( $_GET['pocketa_review_status'] ) )
        : '';

    ob_start();
    ?>
    <section id="pocketa-reviews" class="pocketa-reviews">
        <style>
            .pocketa-reviews{margin:32px 0;padding:24px;border:1px solid #e8e8e8;border-radius:16px;background:#fff;color:#252525}
            .pocketa-reviews h3{margin:0 0 8px;font-size:23px;color:#173f35}
            .pocketa-reviews .pocketa-subtitle{margin:0 0 20px;color:#777}
            .pocketa-review-notice{padding:12px 14px;margin:14px 0;border-radius:9px;background:#edf8f1;color:#17633e}
            .pocketa-review-form{display:grid;gap:13px;margin:18px 0 28px}
            .pocketa-review-form label{display:block;font-weight:600;margin-bottom:6px}
            .pocketa-review-form input,.pocketa-review-form textarea,.pocketa-review-form select{width:100%;padding:12px;border:1px solid #ddd;border-radius:9px;box-sizing:border-box;font:inherit}
            .pocketa-review-form textarea{min-height:110px;resize:vertical}
            .pocketa-review-submit{border:0;border-radius:9px;padding:12px 20px;background:#176b50;color:#fff;font-weight:700;cursor:pointer}
            .pocketa-review-submit:hover{background:#10523d}
            .pocketa-review-item{padding:16px 0;border-top:1px solid #eee}
            .pocketa-review-item h4{margin:0 0 6px;color:#173f35}
            .pocketa-stars{color:#e6a700;letter-spacing:2px}
            .pocketa-review-meta{font-size:13px;color:#888;margin:5px 0 10px}
            .pocketa-edit-link{display:inline-block;margin-top:8px;color:#176b50;font-weight:700;text-decoration:none}
            .pocketa-login-note{padding:14px;background:#f7f7f7;border-radius:9px}
            @media(max-width:600px){.pocketa-reviews{padding:17px}.pocketa-reviews h3{font-size:20px}}
        </style>

        <h3>Ulasan Produk</h3>
        <p class="pocketa-subtitle">Bagikan pengalamanmu menggunakan produk POCKETA.</p>

        <?php if ( 'submitted' === $status ) : ?>
            <div class="pocketa-review-notice">Terima kasih! Ulasan berhasil dikirim dan menunggu persetujuan admin.</div>
        <?php elseif ( 'updated' === $status ) : ?>
            <div class="pocketa-review-notice">Ulasan berhasil diperbarui dan dikirim kembali untuk ditinjau admin.</div>
        <?php elseif ( 'invalid' === $status ) : ?>
            <div class="pocketa-review-notice">Mohon lengkapi judul, rating, dan isi ulasan dengan benar.</div>
        <?php elseif ( 'error' === $status ) : ?>
            <div class="pocketa-review-notice">Ulasan belum berhasil disimpan. Silakan coba kembali.</div>
        <?php endif; ?>

        <?php if ( is_user_logged_in() ) : ?>
            <form class="pocketa-review-form" method="post">
                <input type="hidden" name="pocketa_product_id" value="<?php echo esc_attr( $product_id ); ?>">
                <input type="hidden" name="pocketa_comment_id" value="<?php echo $editing_comment ? esc_attr( $editing_comment->comment_ID ) : '0'; ?>">
                <?php wp_nonce_field( 'pocketa_review_action', 'pocketa_review_nonce' ); ?>

                <div>
                    <label for="pocketa_review_title"><?php echo $editing_comment ? 'Edit Judul Ulasan' : 'Judul Ulasan'; ?></label>
                    <input id="pocketa_review_title" type="text" name="pocketa_review_title"
                        maxlength="100" required
                        value="<?php echo $editing_comment ? esc_attr( get_comment_meta( $editing_comment->comment_ID, 'pocketa_review_title', true ) ) : ''; ?>"
                        placeholder="Contoh: Dompetnya lucu dan praktis!">
                </div>

                <div>
                    <label for="pocketa_rating">Rating Produk</label>
                    <select id="pocketa_rating" name="pocketa_rating" required>
                        <option value="">Pilih rating</option>
                        <?php
                        $selected_rating = $editing_comment
                            ? (int) get_comment_meta( $editing_comment->comment_ID, 'rating', true )
                            : 0;

                        for ( $i = 5; $i >= 1; $i-- ) :
                            ?>
                            <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $selected_rating, $i ); ?>>
                                <?php echo esc_html( $i . ' dari 5 bintang' ); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label for="pocketa_review_content">Ceritakan Pengalamanmu</label>
                    <textarea id="pocketa_review_content" name="pocketa_review_content" required
                        placeholder="Bagaimana kualitas bahan, desain, kenyamanan, dan pengalamanmu?"><?php
                        echo $editing_comment ? esc_textarea( $editing_comment->comment_content ) : '';
                        ?></textarea>
                </div>

                <button class="pocketa-review-submit" type="submit" name="pocketa_review_submit" value="1">
                    <?php echo $editing_comment ? 'Simpan Perubahan' : 'Kirim Ulasan'; ?>
                </button>

                <?php if ( $editing_comment ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $product_id ) . '#pocketa-reviews' ); ?>">Batal edit</a>
                <?php endif; ?>
            </form>
        <?php else : ?>
            <p class="pocketa-login-note">
                Silakan <a href="<?php echo esc_url( wp_login_url( get_permalink( $product_id ) ) ); ?>">login ke akun POCKETA</a>
                untuk memberikan ulasan dan mengedit ulasan milikmu.
            </p>
        <?php endif; ?>

        <div class="pocketa-review-list">
            <?php
            $reviews = get_comments(
                array(
                    'post_id' => $product_id,
                    'type'    => 'review',
                    'status'  => 'all',
                    'orderby' => 'comment_date_gmt',
                    'order'   => 'DESC',
                )
            );

            $visible_count = 0;

            foreach ( $reviews as $review ) :
                $is_approved = '1' === (string) $review->comment_approved;
                $is_own_pending = $current_user_id && (int) $review->user_id === $current_user_id
                    && '0' === (string) $review->comment_approved;

                // Pengunjung hanya melihat ulasan yang disetujui.
                // Pemilik tetap dapat melihat ulasannya sendiri yang menunggu moderasi.
                if ( ! $is_approved && ! $is_own_pending ) {
                    continue;
                }

                $visible_count++;
                $review_rating = (int) get_comment_meta( $review->comment_ID, 'rating', true );
                $review_title = get_comment_meta( $review->comment_ID, 'pocketa_review_title', true );
                ?>
                <article class="pocketa-review-item">
                    <h4><?php echo esc_html( $review_title ? $review_title : 'Ulasan pelanggan' ); ?></h4>
                    <div class="pocketa-stars" aria-label="<?php echo esc_attr( $review_rating ); ?> dari 5 bintang">
                        <?php echo esc_html( str_repeat( '★', max( 0, min( 5, $review_rating ) ) ) . str_repeat( '☆', 5 - max( 0, min( 5, $review_rating ) ) ) ); ?>
                    </div>
                    <div class="pocketa-review-meta">
                        <?php echo esc_html( $review->comment_author ); ?> ·
                        <?php echo esc_html( get_comment_date( 'd M Y', $review ) ); ?>
                        <?php if ( $is_own_pending ) : ?>
                            · <strong>Menunggu persetujuan admin</strong>
                        <?php endif; ?>
                    </div>
                    <div><?php echo nl2br( esc_html( $review->comment_content ) ); ?></div>

                    <?php if ( $current_user_id && (int) $review->user_id === $current_user_id ) : ?>
                        <a class="pocketa-edit-link"
                           href="<?php echo esc_url( add_query_arg( 'edit_review', $review->comment_ID, get_permalink( $product_id ) ) . '#pocketa-reviews' ); ?>">
                            Edit ulasan
                        </a>
                    <?php endif; ?>
                </article>
                <?php
            endforeach;

            if ( 0 === $visible_count ) :
                ?>
                <p>Belum ada ulasan untuk produk ini. Jadilah pelanggan pertama yang memberikan ulasan!</p>
            <?php endif; ?>
        </div>
    </section>
    <?php

    return ob_get_clean();
}
