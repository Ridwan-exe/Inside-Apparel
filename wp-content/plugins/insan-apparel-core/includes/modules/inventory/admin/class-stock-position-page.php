<?php
/**
 * Halaman Posisi Stok: daftar stok per SKU, form Sesuaikan, dan Export CSV.
 * Angka stok dijelaskan di IA_Stock_Metrics dan docs/INVENTORY_DESIGN.md bagian 3.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Stock_Position_Page {

	const PER_PAGE = 50;

	private static function stock_statuses(): array {
		return array(
			'instock'     => 'Tersedia',
			'outofstock'  => 'Habis',
			'onbackorder' => 'Backorder',
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'ia_view_stock' ) ) {
			IA_Inventory_Module::deny();
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		echo '<div class="wrap">';
		IA_Inventory_Module::render_flash();
		if ( 'adjust' === $action ) {
			self::render_adjust();
		} else {
			self::render_list();
		}
		echo '</div>';
	}

	/** Mengambil produk (sederhana + variasi) sesuai filter. */
	private static function query_products( string $q, string $status, int $paged, int $limit ) {
		$args = array(
			'type'     => array( 'simple', 'variation' ),
			'status'   => array( 'publish', 'private' ),
			'limit'    => $limit,
			'paged'    => $paged,
			'paginate' => true,
			'orderby'  => 'title',
			'order'    => 'ASC',
		);
		if ( '' !== $status ) {
			$args['stock_status'] = $status;
		}
		if ( '' !== $q ) {
			$ids             = WC_Data_Store::load( 'product' )->search_products( $q, '', true, false, 300 );
			$args['include'] = $ids ? array_map( 'intval', $ids ) : array( 0 );
		}
		return wc_get_products( $args );
	}

	private static function product_title( $product ): string {
		$title = $product->get_name();
		if ( $product->is_type( 'variation' ) ) {
			$attrs = wc_get_formatted_variation( $product, true, false );
			if ( '' !== $attrs ) {
				$title .= ' (' . $attrs . ')';
			}
		}
		return wp_strip_all_tags( $title );
	}

	private static function cost_value( $product ): string {
		$cost = $product->get_meta( '_ia_cost_price', true );
		if ( '' === $cost && $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$cost   = $parent ? $parent->get_meta( '_ia_cost_price', true ) : '';
		}
		return '' === $cost ? '' : (string) wc_format_decimal( $cost );
	}

	private static function render_list(): void {
		$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$status = isset( $_GET['stock_status'] ) ? sanitize_key( wp_unslash( $_GET['stock_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! array_key_exists( $status, self::stock_statuses() ) ) {
			$status = '';
		}
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification

		$res      = self::query_products( $q, $status, $paged, self::PER_PAGE );
		$map      = IA_Stock_Metrics::in_process_map();
		$can_edit = current_user_can( 'ia_manage_stock' );
		$labels   = self::stock_statuses();

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'       => 'ia_stock_export',
					'q'            => $q,
					'stock_status' => $status,
				),
				admin_url( 'admin-post.php' )
			),
			'ia_stock_export'
		);

		echo '<h1 class="wp-heading-inline">Posisi Stok</h1>';
		if ( $can_edit ) {
			echo ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=ia-stock-import' ) ) . '">Impor Stok Awal</a>';
		}
		echo ' <a class="page-title-action" href="' . esc_url( $export_url ) . '">Export CSV</a>';
		echo '<hr class="wp-header-end">';

		echo '<form method="get" style="margin:12px 0;">';
		echo '<input type="hidden" name="page" value="ia-stock">';
		echo '<input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="Cari nama atau SKU" style="min-width:260px;"> ';
		echo '<select name="stock_status"><option value="">Semua status</option>';
		foreach ( $labels as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $status, $key, false ), esc_html( $label ) );
		}
		echo '</select> <button class="button">Terapkan</button></form>';

		echo '<table class="wp-list-table widefat striped"><thead><tr>';
		echo '<th>Produk</th><th>SKU</th><th>Harga pokok</th>';
		echo '<th title="Stok setelah dikurangi order yang sudah dibayar">Stok WC</th>';
		echo '<th title="Ditahan untuk order belum dibayar (maks. 30 menit)">Ditahan</th>';
		echo '<th title="Stok WC dikurangi Ditahan. Angka inilah yang boleh dijual">Tersedia</th>';
		echo '<th title="Order sudah dibayar, belum dikirim">Dalam proses</th>';
		echo '<th title="Stok WC ditambah Dalam proses. Barang yang masih di rak">Fisik</th>';
		echo '<th>Status</th><th>Aksi</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $res->products ) ) {
			echo '<tr><td colspan="10">Tidak ada produk yang cocok.</td></tr>';
		}

		foreach ( $res->products as $product ) {
			$id      = $product->get_id();
			$sku     = (string) $product->get_sku();
			$managed = true === $product->managing_stock();
			$cost    = self::cost_value( $product );
			$st      = $product->get_stock_status();
			$n       = $managed ? IA_Stock_Metrics::for_product( $product, $map ) : null;

			echo '<tr>';
			echo '<td><strong>' . esc_html( self::product_title( $product ) ) . '</strong></td>';
			echo '<td>' . ( '' !== $sku ? esc_html( $sku ) : '<em>kosong</em>' ) . '</td>';
			echo '<td>' . ( '' !== $cost ? wp_kses_post( wc_price( (float) $cost ) ) : '&mdash;' ) . '</td>';
			if ( $managed ) {
				echo '<td>' . esc_html( (string) $n['wc'] ) . '</td>';
				echo '<td>' . ( null === $n['held'] ? '&mdash;' : esc_html( (string) $n['held'] ) ) . '</td>';
				echo '<td><strong>' . esc_html( (string) $n['available'] ) . '</strong></td>';
				echo '<td>' . esc_html( (string) $n['in_process'] ) . '</td>';
				echo '<td>' . esc_html( (string) $n['physical'] ) . '</td>';
			} else {
				echo '<td colspan="5"><em>stok tidak dikelola</em></td>';
			}
			echo '<td>' . esc_html( $labels[ $st ] ?? $st ) . '</td>';
			echo '<td>';
			if ( $can_edit && $managed ) {
				$adjust_url = add_query_arg(
					array(
						'page'       => 'ia-stock',
						'action'     => 'adjust',
						'product_id' => $id,
					),
					admin_url( 'admin.php' )
				);
				echo '<a href="' . esc_url( $adjust_url ) . '">Sesuaikan</a> | ';
			}
			if ( '' !== $sku ) {
				echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'ia-stock-log', 'sku' => $sku ), admin_url( 'admin.php' ) ) ) . '">Log</a>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';

		if ( $res->max_num_pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => (int) $res->max_num_pages,
					)
				)
			);
			echo '</div></div>';
		}
		printf( '<p class="description">Total %d SKU. <strong>Tersedia</strong> = Stok WC &minus; Ditahan. <strong>Fisik</strong> = Stok WC + Dalam proses. Waktu pada log memakai zona waktu situs (atur ke Asia/Jakarta di Pengaturan &rarr; Umum).</p>', (int) $res->total );
	}

	private static function render_adjust(): void {
		if ( ! current_user_can( 'ia_manage_stock' ) ) {
			IA_Inventory_Module::deny();
		}

		$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$product    = $product_id ? wc_get_product( $product_id ) : false;
		$check      = IA_Inventory_Service::check_product( $product );
		$back       = admin_url( 'admin.php?page=ia-stock' );

		if ( is_wp_error( $check ) ) {
			echo '<h1>Sesuaikan Stok</h1><div class="notice notice-error"><p>' . esc_html( $check->get_error_message() ) . '</p></div>';
			echo '<p><a href="' . esc_url( $back ) . '">&larr; Kembali</a></p>';
			return;
		}

		$n = IA_Stock_Metrics::for_product( $product, IA_Stock_Metrics::in_process_map() );

		echo '<h1>Sesuaikan Stok</h1>';
		echo '<p><a href="' . esc_url( $back ) . '">&larr; Kembali ke Posisi Stok</a></p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th>Produk</th><td><strong>' . esc_html( self::product_title( $product ) ) . '</strong></td></tr>';
		echo '<tr><th>SKU</th><td>' . esc_html( (string) $product->get_sku() ) . '</td></tr>';
		echo '<tr><th>Stok WC</th><td><strong>' . esc_html( (string) $n['wc'] ) . '</strong></td></tr>';
		echo '<tr><th>Dalam proses</th><td>' . esc_html( (string) $n['in_process'] ) . '</td></tr>';
		echo '<tr><th>Fisik di rak (saat ini)</th><td><strong>' . esc_html( (string) $n['physical'] ) . '</strong></td></tr>';
		echo '</tbody></table>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="ia_stock_adjust">';
		echo '<input type="hidden" name="product_id" value="' . esc_attr( (string) $product_id ) . '">';
		wp_nonce_field( 'ia_stock_adjust_' . $product_id );

		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row">Jenis perubahan</th><td>';
		echo '<label><input type="radio" name="mode" value="add" checked> Tambah</label><br>';
		echo '<label><input type="radio" name="mode" value="subtract"> Kurangi</label><br>';
		echo '<label><input type="radio" name="mode" value="set"> Set jumlah <strong>fisik di rak</strong> (hasil hitung stok opname)</label>';
		echo '</td></tr>';
		echo '<tr><th scope="row"><label for="ia_qty">Jumlah</label></th><td><input type="number" id="ia_qty" name="qty" min="0" step="1" required class="small-text"></td></tr>';
		echo '<tr><th scope="row"><label for="ia_reason">Alasan</label></th><td><select id="ia_reason" name="reason" required>';
		foreach ( IA_Stock_Log::manual_reasons() as $code => $info ) {
			printf( '<option value="%s">%s</option>', esc_attr( $code ), esc_html( $info['label'] ) );
		}
		echo '</select><p class="description">&ldquo;Barang rusak / hilang&rdquo; hanya untuk mengurangi. &ldquo;Retur dikembalikan ke stok&rdquo; hanya untuk menambah.</p></td></tr>';
		echo '<tr><th scope="row"><label for="ia_note">Catatan</label></th><td><textarea id="ia_note" name="note" rows="3" class="large-text" maxlength="500"></textarea></td></tr>';
		echo '</tbody></table>';
		submit_button( 'Simpan perubahan' );
		echo '</form>';
	}

	public static function handle_adjust(): void {
		if ( ! current_user_can( 'ia_manage_stock' ) ) {
			IA_Inventory_Module::deny();
		}
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'ia_stock_adjust_' . $product_id );

		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		$qty_in = isset( $_POST['qty'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['qty'] ) ) ) : '';
		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		$back = add_query_arg(
			array(
				'page'       => 'ia-stock',
				'action'     => 'adjust',
				'product_id' => $product_id,
			),
			admin_url( 'admin.php' )
		);

		$manual = IA_Stock_Log::manual_reasons();
		if ( ! in_array( $mode, array( 'add', 'subtract', 'set' ), true ) || ! isset( $manual[ $reason ] ) ) {
			IA_Inventory_Module::flash( 'error', 'Jenis perubahan atau alasan tidak valid.' );
			wp_safe_redirect( $back );
			exit;
		}
		if ( ! in_array( $mode, $manual[ $reason ]['modes'], true ) ) {
			IA_Inventory_Module::flash( 'error', 'Alasan "' . $manual[ $reason ]['label'] . '" tidak cocok dengan jenis perubahan yang dipilih.' );
			wp_safe_redirect( $back );
			exit;
		}
		if ( ! preg_match( '/^\d{1,9}$/', $qty_in ) ) {
			IA_Inventory_Module::flash( 'error', 'Jumlah harus bilangan bulat 0 atau lebih.' );
			wp_safe_redirect( $back );
			exit;
		}
		$qty = (int) $qty_in;

		$args = array(
			'note'     => $note,
			'user_id'  => get_current_user_id(),
			'ref_type' => 'adjustment',
		);

		if ( 'set' === $mode ) {
			// Jumlah yang dimasukkan adalah FISIK di rak. Stok WC = fisik - barang dalam proses.
			$in_process = (int) ( IA_Stock_Metrics::in_process_map()[ $product_id ] ?? 0 );
			$target     = IA_Stock_Metrics::wc_target_from_physical( $qty, $in_process );
			if ( null === $target ) {
				IA_Inventory_Module::flash(
					'error',
					sprintf( 'Jumlah fisik (%d) lebih kecil dari barang dalam proses (%d). Periksa kembali hitungan Anda.', $qty, $in_process )
				);
				wp_safe_redirect( $back );
				exit;
			}
			$result = IA_Inventory_Service::set_quantity( $product_id, $target, $reason, 'admin', $args );
		} elseif ( 0 === $qty ) {
			$result = new WP_Error( 'ia_zero_delta', 'Jumlah harus lebih dari 0.' );
		} else {
			$result = IA_Inventory_Service::adjust( $product_id, 'add' === $mode ? $qty : -$qty, $reason, 'admin', $args );
		}

		if ( is_wp_error( $result ) ) {
			IA_Inventory_Module::flash( 'ia_no_change' === $result->get_error_code() ? 'warning' : 'error', $result->get_error_message() );
			wp_safe_redirect( $back );
			exit;
		}

		IA_Inventory_Module::flash(
			'success',
			sprintf( 'Stok WC %s berubah dari %d menjadi %d.', $result['sku'], $result['qty_before'], $result['qty_after'] )
		);
		wp_safe_redirect( admin_url( 'admin.php?page=ia-stock' ) );
		exit;
	}

	public static function handle_export(): void {
		if ( ! current_user_can( 'ia_view_stock' ) ) {
			IA_Inventory_Module::deny();
		}
		check_admin_referer( 'ia_stock_export' );

		$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$status = isset( $_GET['stock_status'] ) ? sanitize_key( wp_unslash( $_GET['stock_status'] ) ) : '';
		if ( ! array_key_exists( $status, self::stock_statuses() ) ) {
			$status = '';
		}
		$map = IA_Stock_Metrics::in_process_map();

		$rows = ( static function () use ( $q, $status, $map ) {
			$page = 1;
			do {
				$res = self::query_products( $q, $status, $page, 200 );
				foreach ( $res->products as $product ) {
					$managed = true === $product->managing_stock();
					$n       = $managed ? IA_Stock_Metrics::for_product( $product, $map ) : null;
					yield array(
						IA_Core_Csv::safe( (string) $product->get_sku() ),
						IA_Core_Csv::safe( self::product_title( $product ) ),
						self::cost_value( $product ),
						$managed ? $n['wc'] : '',
						$managed && null !== $n['held'] ? $n['held'] : '',
						$managed ? $n['available'] : '',
						$managed ? $n['in_process'] : '',
						$managed ? $n['physical'] : '',
						$product->get_stock_status(),
					);
				}
				++$page;
			} while ( $page <= (int) $res->max_num_pages );
		} )();

		IA_Core_Csv::stream(
			'posisi-stok-' . gmdate( 'Ymd-His' ) . '.csv',
			array( 'sku', 'produk', 'harga_pokok', 'stok_wc', 'ditahan', 'tersedia', 'dalam_proses', 'fisik', 'status' ),
			$rows
		);
	}
}
