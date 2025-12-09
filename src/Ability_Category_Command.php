<?php

namespace WP_CLI\Ability;

use WP_Ability_Category;
use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Lists and inspects ability categories registered via the WordPress Abilities API.
 *
 * The Abilities API, introduced in WordPress 6.9, uses categories to organize
 * related abilities for better discoverability.
 *
 * ## EXAMPLES
 *
 *     # List all registered ability categories.
 *     $ wp ability category list
 *     +------+-------+-----------------------------------------------------+
 *     | slug | label | description                                         |
 *     +------+-------+-----------------------------------------------------+
 *     | site | Site  | Abilities that retrieve or modify site information. |
 *     | user | User  | Abilities that retrieve or modify user information. |
 *     +------+-------+-----------------------------------------------------+
 *
 *     # Get details of a specific category.
 *     $ wp ability category get site
 *     +-------------+-----------------------------------------------------+
 *     | Field       | Value                                               |
 *     +-------------+-----------------------------------------------------+
 *     | slug        | site                                                |
 *     | label       | Site                                                |
 *     | description | Abilities that retrieve or modify site information. |
 *     | meta        | {}                                                  |
 *     +-------------+-----------------------------------------------------+
 *
 *     # Check if a category exists.
 *     $ wp ability category exists site
 *     $ echo $?
 *     0
 *
 * @when    after_wp_load
 * @package wp-cli
 */
class Ability_Category_Command extends WP_CLI_Command {

	/**
	 * Default fields for list output.
	 *
	 * @var string[]
	 */
	protected $default_fields = [
		'slug',
		'label',
		'description',
	];

	/**
	 * All fields for get output.
	 *
	 * @var string[]
	 */
	protected $get_fields = [
		'slug',
		'label',
		'description',
		'meta',
	];

	/**
	 * Lists all registered ability categories.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each category.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific category fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each category:
	 *
	 * * slug
	 * * label
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # List all categories.
	 *     $ wp ability category list
	 *     +------+-------+-----------------------------------------------------+
	 *     | slug | label | description                                         |
	 *     +------+-------+-----------------------------------------------------+
	 *     | site | Site  | Abilities that retrieve or modify site information. |
	 *     | user | User  | Abilities that retrieve or modify user information. |
	 *     +------+-------+-----------------------------------------------------+
	 *
	 *     # List categories as JSON.
	 *     $ wp ability category list --format=json
	 *     [{"slug":"site","label":"Site","description":"..."},{"slug":"user",...}]
	 *
	 *     # List only category slugs.
	 *     $ wp ability category list --field=slug
	 *     site
	 *     user
	 *
	 * @subcommand list
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ): void {
		$categories = wp_get_ability_categories();

		$items = [];

		foreach ( $categories as $category ) {
			$items[] = $this->format_category( $category );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered ability category.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The category slug.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole category, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * slug
	 * * label
	 * * description
	 * * meta
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific category.
	 *     $ wp ability category get site
	 *     +-------------+-----------------------------------------------------+
	 *     | Field       | Value                                               |
	 *     +-------------+-----------------------------------------------------+
	 *     | slug        | site                                                |
	 *     | label       | Site                                                |
	 *     | description | Abilities that retrieve or modify site information. |
	 *     | meta        | {}                                                  |
	 *     +-------------+-----------------------------------------------------+
	 *
	 *     # Get category as JSON.
	 *     $ wp ability category get site --format=json
	 *     {"slug":"site","label":"Site","description":"...","meta":"{}"}
	 *
	 *     # Get only the label.
	 *     $ wp ability category get site --field=label
	 *     Site
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ): void {
		$slug     = $args[0];
		$category = wp_get_ability_category( $slug );

		if ( null === $category ) {
			WP_CLI::error( "Ability category '{$slug}' not found." );
		}

		$category_data = $this->format_category_for_get( $category );

		$formatter = $this->get_formatter_for_get( $assoc_args );
		$formatter->display_item( $category_data );
	}

	/**
	 * Checks whether an ability category is registered.
	 *
	 * Exits with return code 0 if the category exists, 1 if it does not.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The category slug.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check if a category exists.
	 *     $ wp ability category exists site
	 *     $ echo $?
	 *     0
	 *
	 *     # Check for non-existent category.
	 *     $ wp ability category exists nonexistent
	 *     $ echo $?
	 *     1
	 *
	 *     # Use in a script.
	 *     $ if wp ability category exists site; then
	 *     >   echo "Category exists"
	 *     > fi
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function exists( $args, $assoc_args ): void {
		$slug   = $args[0];
		$exists = wp_has_ability_category( $slug );

		WP_CLI::halt( $exists ? 0 : 1 );
	}

	/**
	 * Formats a category for list output.
	 *
	 * @param WP_Ability_Category $category The category object.
	 * @return array<string,mixed>
	 */
	private function format_category( $category ) {
		return [
			'slug'        => $category->get_slug(),
			'label'       => $category->get_label(),
			'description' => $category->get_description(),
		];
	}

	/**
	 * Formats a category for get output.
	 *
	 * @param WP_Ability_Category $category The category object.
	 * @return array<string,mixed>
	 */
	private function format_category_for_get( $category ) {
		$meta = $category->get_meta();

		return [
			'slug'        => $category->get_slug(),
			'label'       => $category->get_label(),
			'description' => $category->get_description(),
			'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : '{}',
		];
	}

	/**
	 * Gets the formatter object for list output.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->default_fields );
	}

	/**
	 * Gets the formatter object for get output.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter_for_get( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->get_fields );
	}
}
