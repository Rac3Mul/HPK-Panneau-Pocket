<?php
/**
 * GitHub automatic updates via Plugin Update Checker.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Updater
 */
class HPK_PP_Updater {

	/**
	 * Bootstrap update checker.
	 */
	public static function init() {
		$puc_file = HPK_PP_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';
		if ( ! is_readable( $puc_file ) ) {
			return;
		}

		require_once $puc_file;

		$repo = apply_filters(
			'hpk_pp_github_repo',
			defined( 'HPK_PP_GITHUB_REPO' ) ? HPK_PP_GITHUB_REPO : 'https://github.com/HPK-PanneauPocket/hpk-panneaupocket/'
		);

		$repo = untrailingslashit( trim( (string) $repo ) );
		if ( '' === $repo || false === filter_var( $repo, FILTER_VALIDATE_URL ) ) {
			return;
		}

		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			$repo . '/',
			HPK_PP_LOADED_FROM,
			'hpk-panneaupocket'
		);

		$branch = apply_filters(
			'hpk_pp_github_branch',
			defined( 'HPK_PP_GITHUB_BRANCH' ) ? HPK_PP_GITHUB_BRANCH : 'main'
		);
		$checker->setBranch( $branch );

		if ( defined( 'HPK_PP_GITHUB_TOKEN' ) && HPK_PP_GITHUB_TOKEN ) {
			$checker->setAuthentication( HPK_PP_GITHUB_TOKEN );
		}

		$vcs = $checker->getVcsApi();
		if ( $vcs && method_exists( $vcs, 'enableReleaseAssets' ) ) {
			$vcs->enableReleaseAssets( '/\.zip$/i' );
		}
	}
}
