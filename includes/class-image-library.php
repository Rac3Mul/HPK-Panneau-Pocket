<?php
/**
 * Built-in PanneauPocket image library (assets/img/base).
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Image_Library
 */
class HPK_PP_Image_Library {

	/**
	 * Default PanneauPocket logo URL (small variant).
	 *
	 * @return string
	 */
	public static function get_default_logo_url() {
		return HPK_PP_URL . 'assets/img/logo-small-panneaupocket.svg';
	}

	/**
	 * Scan assets/img/base recursively, grouped by subfolder.
	 *
	 * @return array<string, array<int, array{url:string,name:string,file:string}>>
	 */
	public static function get_base_library() {
		$base_dir = HPK_PP_PATH . 'assets/img/base';

		if ( ! is_dir( $base_dir ) ) {
			return array();
		}

		$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );
		$library = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base_dir, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			$ext = strtolower( $file->getExtension() );
			if ( ! in_array( $ext, $allowed, true ) ) {
				continue;
			}

			$full_path = wp_normalize_path( $file->getPathname() );
			$base_norm = wp_normalize_path( $base_dir );
			$relative  = ltrim( substr( $full_path, strlen( $base_norm ) ), '/' );

			$parts    = explode( '/', $relative );
			$category = count( $parts ) > 1 ? $parts[0] : __( 'Général', 'hpk-panneaupocket' );

			if ( ! isset( $library[ $category ] ) ) {
				$library[ $category ] = array();
			}

			$library[ $category ][] = array(
				'url'  => HPK_PP_URL . 'assets/img/base/' . $relative,
				'name' => pathinfo( $file->getFilename(), PATHINFO_FILENAME ),
				'file' => $relative,
			);
		}

		ksort( $library );

		foreach ( $library as $category => $images ) {
			usort(
				$library[ $category ],
				static function ( $a, $b ) {
					return strnatcasecmp( $a['name'], $b['name'] );
				}
			);
		}

		return $library;
	}
}
