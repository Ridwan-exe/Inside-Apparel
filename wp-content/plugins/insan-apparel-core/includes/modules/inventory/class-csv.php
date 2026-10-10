<?php
/**
 * Helper CSV: pembacaan file impor stok dan penulisan file export.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IA_Core_Csv {

	/**
	 * Mencegah formula injection saat CSV dibuka di Excel/Sheets.
	 * Pakai hanya untuk kolom teks, bukan angka.
	 */
	public static function safe( $value ): string {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/** Memilih pemisah kolom dari baris header (Excel Indonesia biasanya memakai titik koma). */
	public static function detect_delimiter( string $line ): string {
		$counts = array(
			',' => substr_count( $line, ',' ),
			';' => substr_count( $line, ';' ),
			"\t" => substr_count( $line, "\t" ),
		);
		arsort( $counts );
		$best = (string) array_key_first( $counts );
		return $counts[ $best ] > 0 ? $best : ',';
	}

	/**
	 * Membaca CSV impor stok. Kolom wajib: sku, qty.
	 *
	 * @return array{rows: array, error: ?string} Tiap baris: line, sku, qty (teks apa adanya).
	 */
	public static function parse_stock_file( string $path, int $max_rows = 2000 ): array {
		$h = @fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $h ) {
			return array(
				'rows'  => array(),
				'error' => 'File tidak dapat dibaca.',
			);
		}

		$bom = fread( $h, 3 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $h );
		}
		$first = fgets( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $first || '' === trim( $first ) ) {
			fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return array(
				'rows'  => array(),
				'error' => 'File kosong.',
			);
		}
		$delimiter = self::detect_delimiter( $first );

		rewind( $h );
		if ( "\xEF\xBB\xBF" === $bom ) {
			fread( $h, 3 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		$header = fgetcsv( $h, 0, $delimiter, '"', '\\' );
		$cols   = array_map(
			static function ( $c ) {
				return strtolower( trim( (string) $c ) );
			},
			is_array( $header ) ? $header : array()
		);
		$i_sku = array_search( 'sku', $cols, true );
		$i_qty = array_search( 'qty', $cols, true );
		if ( false === $i_sku || false === $i_qty ) {
			fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return array(
				'rows'  => array(),
				'error' => 'Baris pertama harus berisi kolom sku dan qty.',
			);
		}

		$rows = array();
		$line = 1;
		while ( false !== ( $r = fgetcsv( $h, 0, $delimiter, '"', '\\' ) ) ) {
			++$line;
			if ( array( null ) === $r ) {
				continue; // baris kosong
			}
			if ( count( $rows ) >= $max_rows ) {
				fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				return array(
					'rows'  => array(),
					'error' => sprintf( 'File melebihi batas %d baris.', $max_rows ),
				);
			}
			$rows[] = array(
				'line' => $line,
				'sku'  => trim( (string) ( $r[ $i_sku ] ?? '' ) ),
				'qty'  => trim( (string) ( $r[ $i_qty ] ?? '' ) ),
			);
		}
		fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! $rows ) {
			return array(
				'rows'  => array(),
				'error' => 'File tidak berisi data.',
			);
		}

		return array(
			'rows'  => $rows,
			'error' => null,
		);
	}

	/**
	 * Mengirim CSV ke browser lalu mengakhiri request.
	 *
	 * @param iterable $rows Baris data; kolom teks harus sudah melewati self::safe().
	 */
	public static function stream( string $filename, array $header, iterable $rows ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM agar Excel membaca UTF-8 // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, $header, ',', '"', '\\' );
		foreach ( $rows as $row ) {
			fputcsv( $out, $row, ',', '"', '\\' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
