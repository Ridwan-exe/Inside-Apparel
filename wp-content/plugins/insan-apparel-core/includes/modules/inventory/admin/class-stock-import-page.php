<?php
/**
 * Impor Stok Awal dari CSV (kolom: sku, qty) dengan pratinjau sebelum diterapkan.
 * Alasan log otomatis "stok_awal". Nilai qty adalah jumlah stok akhir (bukan selisih).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Stock_Import_Page {

	const MAX_BYTES   = 1048576; // 1 MB
	const TRANSIENT   = 'ia_import_';
	const TTL_SECONDS = 1800;

	private static function page_url( string $token = '' ): string {
		$args = array( 'page' => 'ia-stock-import' );
		if ( '' !== $token ) {
			$args['token'] = $token;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	public static function render(): void {
		if ( ! current_user_can( 'ia_manage_stock' ) ) {
			IA_Inventory_Module::deny();
		}

		echo '<div class="wrap"><h1>Impor Stok Awal</h1>';
		IA_Inventory_Module::render_flash();

		$token = isset( $_GET['token'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$data  = '' !== $token ? get_transient( self::TRANSIENT . $token ) : false;

		if ( is_array( $data ) && (int) $data['user_id'] === get_current_user_id() ) {
			self::render_preview( $token, $data );
		} else {
			if ( '' !== $token ) {
				echo '<div class="notice notice-warning"><p>Pratinjau sudah kedaluwarsa atau sudah diterapkan. Unggah ulang file CSV.</p></div>';
			}
			self::render_upload();
		}
		echo '</div>';
	}

	private static function render_upload(): void {
		echo '<p>Unggah file CSV dengan dua kolom: <code>sku</code> dan <code>qty</code>. Nilai <code>qty</code> adalah <strong>jumlah stok akhir</strong>. Pemisah koma atau titik koma, maksimal 2.000 baris dan 1 MB.</p>';
		echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:8px;display:inline-block;">sku,qty' . "\n" . 'KMJ-001-M,10' . "\n" . 'KMJ-001-L,8</pre>';
		echo '<p>Produk harus sudah ada dan mengaktifkan &ldquo;Kelola stok&rdquo;. Tidak ada yang diubah sebelum Anda menekan tombol terapkan pada halaman pratinjau.</p>';

		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="ia_stock_import_preview">';
		wp_nonce_field( 'ia_stock_import_preview' );
		echo '<input type="file" name="csv_file" accept=".csv,text/csv" required> ';
		submit_button( 'Lihat pratinjau', 'primary', 'submit', false );
		echo '</form>';
	}

	private static function render_preview( string $token, array $data ): void {
		$counts = array(
			'change' => 0,
			'same'   => 0,
			'error'  => 0,
		);
		foreach ( $data['items'] as $item ) {
			++$counts[ $item['status'] ];
		}

		printf(
			'<p><strong>Pratinjau:</strong> %d akan diubah, %d sudah sama, %d bermasalah (tidak akan diproses).</p>',
			(int) $counts['change'],
			(int) $counts['same'],
			(int) $counts['error']
		);

		echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
		echo '<th style="width:60px;">Baris</th><th style="width:150px;">SKU</th><th>Produk</th><th style="width:100px;">Stok sekarang</th><th style="width:100px;">Stok baru</th><th>Status</th>';
		echo '</tr></thead><tbody>';
		$labels = array(
			'change' => 'Akan diubah',
			'same'   => 'Sudah sama',
			'error'  => 'Bermasalah',
		);
		foreach ( $data['items'] as $item ) {
			$status_text = $labels[ $item['status'] ] . ( '' !== $item['message'] ? ': ' . $item['message'] : '' );
			echo '<tr>';
			echo '<td>' . esc_html( (string) $item['line'] ) . '</td>';
			echo '<td>' . esc_html( $item['sku'] ) . '</td>';
			echo '<td>' . esc_html( $item['name'] ) . '</td>';
			echo '<td>' . ( null === $item['old'] ? '&mdash;' : esc_html( (string) $item['old'] ) ) . '</td>';
			echo '<td>' . ( null === $item['new'] ? '&mdash;' : esc_html( (string) $item['new'] ) ) . '</td>';
			echo '<td' . ( 'error' === $item['status'] ? ' style="color:#b32d2e;"' : '' ) . '>' . esc_html( $status_text ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:16px;">';
		echo '<input type="hidden" name="action" value="ia_stock_import_apply">';
		echo '<input type="hidden" name="token" value="' . esc_attr( $token ) . '">';
		wp_nonce_field( 'ia_stock_import_apply_' . $token );
		submit_button( sprintf( 'Terapkan %d perubahan', (int) $counts['change'] ), 'primary', 'submit', false, $counts['change'] > 0 ? array() : array( 'disabled' => 'disabled' ) );
		echo ' <a class="button" href="' . esc_url( self::page_url() ) . '">Batal</a></form>';
	}

	/** Menganalisis baris CSV terhadap produk di database. Tidak mengubah apa pun. */
	private static function analyze( array $rows ): array {
		$seen  = array();
		$items = array();

		foreach ( $rows as $r ) {
			$sku  = sanitize_text_field( $r['sku'] );
			$item = array(
				'line'       => (int) $r['line'],
				'sku'        => $sku,
				'product_id' => 0,
				'name'       => '',
				'old'        => null,
				'new'        => null,
				'status'     => 'error',
				'message'    => '',
			);

			if ( '' === $sku ) {
				$item['message'] = 'SKU kosong.';
			} elseif ( ! preg_match( '/^\d{1,9}$/', $r['qty'] ) ) {
				$item['message'] = 'qty harus bilangan bulat 0 atau lebih.';
			} elseif ( isset( $seen[ strtolower( $sku ) ] ) ) {
				$item['message'] = 'SKU duplikat (sudah ada di baris ' . $seen[ strtolower( $sku ) ] . ').';
			} else {
				$seen[ strtolower( $sku ) ] = (int) $r['line'];
				$id                         = (int) wc_get_product_id_by_sku( $sku );
				$product                    = $id ? wc_get_product( $id ) : false;
				$check                      = $id ? IA_Inventory_Service::check_product( $product ) : new WP_Error( 'ia_sku_missing', 'SKU tidak ditemukan.' );

				if ( is_wp_error( $check ) ) {
					$item['message'] = $check->get_error_message();
				} else {
					$item['product_id'] = $id;
					$item['name']       = wp_strip_all_tags( $product->get_name() );
					$item['old']        = (int) $product->get_stock_quantity();
					$item['new']        = (int) $r['qty'];
					$item['status']     = $item['old'] === $item['new'] ? 'same' : 'change';
				}
			}
			$items[] = $item;
		}
		return $items;
	}

	public static function handle_preview(): void {
		if ( ! current_user_can( 'ia_manage_stock' ) ) {
			IA_Inventory_Module::deny();
		}
		check_admin_referer( 'ia_stock_import_preview' );

		$back = self::page_url();
		$file = isset( $_FILES['csv_file'] ) && is_array( $_FILES['csv_file'] ) ? $_FILES['csv_file'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( empty( $file['tmp_name'] ) || ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			IA_Inventory_Module::flash( 'error', 'Unggahan file gagal. Coba lagi.' );
			wp_safe_redirect( $back );
			exit;
		}
		if ( (int) $file['size'] > self::MAX_BYTES ) {
			IA_Inventory_Module::flash( 'error', 'Ukuran file melebihi 1 MB.' );
			wp_safe_redirect( $back );
			exit;
		}
		$ext = strtolower( pathinfo( sanitize_file_name( (string) $file['name'] ), PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			IA_Inventory_Module::flash( 'error', 'File harus berformat .csv.' );
			wp_safe_redirect( $back );
			exit;
		}

		$parsed = IA_Core_Csv::parse_stock_file( $file['tmp_name'] );
		if ( null !== $parsed['error'] ) {
			IA_Inventory_Module::flash( 'error', $parsed['error'] );
			wp_safe_redirect( $back );
			exit;
		}

		$token = wp_generate_password( 20, false );
		set_transient(
			self::TRANSIENT . $token,
			array(
				'user_id' => get_current_user_id(),
				'items'   => self::analyze( $parsed['rows'] ),
			),
			self::TTL_SECONDS
		);

		wp_safe_redirect( self::page_url( $token ) );
		exit;
	}

	public static function handle_apply(): void {
		if ( ! current_user_can( 'ia_manage_stock' ) ) {
			IA_Inventory_Module::deny();
		}
		$token = isset( $_POST['token'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'ia_stock_import_apply_' . $token );

		$key  = self::TRANSIENT . $token;
		$data = get_transient( $key );
		// Hapus lebih dulu agar klik ganda atau kirim ulang tidak memproses dua kali.
		delete_transient( $key );

		if ( ! is_array( $data ) || (int) $data['user_id'] !== get_current_user_id() ) {
			IA_Inventory_Module::flash( 'error', 'Pratinjau kedaluwarsa atau sudah diterapkan. Unggah ulang file CSV.' );
			wp_safe_redirect( self::page_url() );
			exit;
		}

		$changed = 0;
		$skipped = 0;
		$errors  = array();

		foreach ( $data['items'] as $item ) {
			if ( 'change' !== $item['status'] ) {
				continue;
			}
			$result = IA_Inventory_Service::set_quantity(
				(int) $item['product_id'],
				(int) $item['new'],
				'stok_awal',
				'admin',
				array(
					'user_id'  => get_current_user_id(),
					'ref_type' => 'import',
					'ref_id'   => $token,
				)
			);
			if ( is_wp_error( $result ) ) {
				if ( 'ia_no_change' === $result->get_error_code() ) {
					++$skipped;
				} else {
					$errors[] = $item['sku'] . ': ' . $result->get_error_message();
				}
				continue;
			}
			++$changed;
		}

		$message = sprintf( 'Impor selesai: %d SKU diubah, %d sudah sama, %d gagal.', $changed, $skipped, count( $errors ) );
		if ( $errors ) {
			$message .= ' ' . implode( ' | ', array_slice( $errors, 0, 5 ) ) . ( count( $errors ) > 5 ? ' ...' : '' );
		}
		IA_Inventory_Module::flash( $errors ? 'warning' : 'success', $message );
		wp_safe_redirect( admin_url( 'admin.php?page=ia-stock' ) );
		exit;
	}
}
