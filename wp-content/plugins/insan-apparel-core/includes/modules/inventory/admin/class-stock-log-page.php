<?php
/**
 * Halaman Log Stok (hanya baca) dan Export CSV.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Stock_Log_Page {

	const PER_PAGE = 50;

	private static function format_time( string $utc ): string {
		$ts = strtotime( $utc . ' UTC' );
		return $ts ? wp_date( 'd M Y H:i', $ts ) : $utc;
	}

	private static function user_name( $user_id ): string {
		if ( ! $user_id ) {
			return 'Sistem';
		}
		$user = get_userdata( (int) $user_id );
		return $user ? $user->display_name : '#' . (int) $user_id;
	}

	private static function reference( $row ): string {
		if ( empty( $row->ref_type ) ) {
			return '';
		}
		return $row->ref_type . ( ! empty( $row->ref_id ) ? ' #' . $row->ref_id : '' );
	}

	public static function render(): void {
		if ( ! current_user_can( 'ia_view_stock' ) ) {
			IA_Inventory_Module::deny();
		}

		$filters = IA_Stock_Log::sanitize_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$result  = IA_Stock_Log::query( $filters, self::PER_PAGE, $paged );
		$reasons = IA_Stock_Log::reasons();
		$sources = IA_Stock_Log::sources();
		$users   = IA_Stock_Log::logged_users();

		$export_url = wp_nonce_url(
			add_query_arg( array_merge( array( 'action' => 'ia_stock_log_export' ), $filters ), admin_url( 'admin-post.php' ) ),
			'ia_stock_log_export'
		);

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">Log Stok</h1> <a class="page-title-action" href="' . esc_url( $export_url ) . '">Export CSV</a><hr class="wp-header-end">';

		echo '<form method="get" style="margin:12px 0;">';
		echo '<input type="hidden" name="page" value="ia-stock-log">';
		echo '<input type="search" name="sku" value="' . esc_attr( $filters['sku'] ) . '" placeholder="SKU"> ';

		echo '<select name="reason"><option value="">Semua alasan</option>';
		foreach ( $reasons as $code => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $code ), selected( $filters['reason'], $code, false ), esc_html( $label ) );
		}
		echo '</select> ';

		echo '<select name="source"><option value="">Semua sumber</option>';
		foreach ( $sources as $code => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $code ), selected( $filters['source'], $code, false ), esc_html( $label ) );
		}
		echo '</select> ';

		echo '<select name="user_id"><option value="0">Semua pengguna</option>';
		foreach ( $users as $uid => $name ) {
			printf( '<option value="%d"%s>%s</option>', (int) $uid, selected( $filters['user_id'], $uid, false ), esc_html( $name ) );
		}
		echo '</select> ';

		echo 'Dari <input type="date" name="date_from" value="' . esc_attr( $filters['date_from'] ) . '"> ';
		echo 'sampai <input type="date" name="date_to" value="' . esc_attr( $filters['date_to'] ) . '"> ';
		echo '<button class="button">Terapkan</button> <a class="button-link" href="' . esc_url( admin_url( 'admin.php?page=ia-stock-log' ) ) . '">Reset</a></form>';

		echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
		echo '<th style="width:130px;">Waktu</th><th style="width:130px;">SKU</th><th style="width:70px;">Selisih</th><th style="width:60px;">Sebelum</th><th style="width:60px;">Sesudah</th>';
		echo '<th>Alasan</th><th style="width:90px;">Sumber</th><th>Referensi</th><th style="width:110px;">Pengguna</th><th>Catatan</th>';
		echo '</tr></thead><tbody>';

		if ( ! $result['rows'] ) {
			echo '<tr><td colspan="10">Belum ada data log.</td></tr>';
		}
		foreach ( $result['rows'] as $row ) {
			$delta = (int) $row->delta;
			echo '<tr>';
			echo '<td>' . esc_html( self::format_time( $row->created_at ) ) . '</td>';
			echo '<td>' . esc_html( $row->sku ) . '</td>';
			echo '<td><strong style="color:' . ( $delta > 0 ? '#1a7f37' : '#b32d2e' ) . ';">' . esc_html( ( $delta > 0 ? '+' : '' ) . $delta ) . '</strong></td>';
			echo '<td>' . esc_html( (string) (int) $row->qty_before ) . '</td>';
			echo '<td>' . esc_html( (string) (int) $row->qty_after ) . '</td>';
			echo '<td>' . esc_html( $reasons[ $row->reason ] ?? $row->reason ) . '</td>';
			echo '<td>' . esc_html( $sources[ $row->source ] ?? $row->source ) . '</td>';
			echo '<td>' . esc_html( self::reference( $row ) ) . '</td>';
			echo '<td>' . esc_html( self::user_name( $row->user_id ) ) . '</td>';
			echo '<td>' . esc_html( (string) $row->note ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		$pages = (int) ceil( $result['total'] / self::PER_PAGE );
		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				)
			);
			echo '</div></div>';
		}
		printf( '<p class="description">Total %d catatan. Log bersifat hanya-baca.</p></div>', (int) $result['total'] );
	}

	public static function handle_export(): void {
		if ( ! current_user_can( 'ia_view_stock' ) ) {
			IA_Inventory_Module::deny();
		}
		check_admin_referer( 'ia_stock_log_export' );

		$filters = IA_Stock_Log::sanitize_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
		$reasons = IA_Stock_Log::reasons();
		$sources = IA_Stock_Log::sources();

		$rows = ( static function () use ( $filters, $reasons, $sources ) {
			$per_page = 500;
			$max_rows = 50000;
			$page     = 1;
			$sent     = 0;
			do {
				$res = IA_Stock_Log::query( $filters, $per_page, $page );
				foreach ( $res['rows'] as $row ) {
					yield array(
						$row->id,
						$row->created_at . ' UTC',
						IA_Core_Csv::safe( $row->sku ),
						(int) $row->delta,
						(int) $row->qty_before,
						(int) $row->qty_after,
						IA_Core_Csv::safe( $reasons[ $row->reason ] ?? $row->reason ),
						IA_Core_Csv::safe( $sources[ $row->source ] ?? $row->source ),
						IA_Core_Csv::safe( self::reference( $row ) ),
						IA_Core_Csv::safe( self::user_name( $row->user_id ) ),
						IA_Core_Csv::safe( (string) $row->note ),
					);
					++$sent;
				}
				++$page;
			} while ( count( $res['rows'] ) === $per_page && $sent < $max_rows );
		} )();

		IA_Core_Csv::stream(
			'log-stok-' . gmdate( 'Ymd-His' ) . '.csv',
			array( 'id', 'waktu_utc', 'sku', 'selisih', 'sebelum', 'sesudah', 'alasan', 'sumber', 'referensi', 'pengguna', 'catatan' ),
			$rows
		);
	}
}
