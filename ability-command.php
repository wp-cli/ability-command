<?php

use WP_CLI\Utils;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_ability_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpcli_ability_autoloader ) ) {
	require_once $wpcli_ability_autoloader;
}

$wpcli_ability_before_invoke = static function () {
	// The Abilities API was introduced in WordPress 6.9.
	if ( Utils\wp_version_compare( '6.9', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 6.9 or greater.' );
	}
};

WP_CLI::add_command( 'ability', '\WP_CLI\Ability\Ability_Command', [ 'before_invoke' => $wpcli_ability_before_invoke ] );
WP_CLI::add_command( 'ability category', '\WP_CLI\Ability\Ability_Category_Command', [ 'before_invoke' => $wpcli_ability_before_invoke ] );
