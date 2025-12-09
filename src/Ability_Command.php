<?php

namespace WP_CLI\Ability;

use Spyc;
use WP_Ability;
use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI_Command;

/**
 * Lists, inspects, and executes abilities registered via the WordPress Abilities API.
 *
 * The Abilities API, introduced in WordPress 6.9, provides a standardized way
 * to register and discover distinct units of functionality within a WordPress site.
 *
 * ## EXAMPLES
 *
 *     # List all registered abilities.
 *     $ wp ability list
 *     +---------------------------+----------------------+----------+------------------------------------------+
 *     | name                      | label                | category | description                              |
 *     +---------------------------+----------------------+----------+------------------------------------------+
 *     | core/get-site-info        | Get Site Information | site     | Returns site information configured i... |
 *     | core/get-user-info        | Get User Information | user     | Returns basic profile details for the... |
 *     | core/get-environment-info | Get Environment Info | site     | Returns core details about the site's... |
 *     +---------------------------+----------------------+----------+------------------------------------------+
 *
 *     # Get details of a specific ability.
 *     $ wp ability get core/get-site-info --fields=name,label,category,readonly,show_in_rest
 *     +---------------+----------------------+
 *     | Field         | Value                |
 *     +---------------+----------------------+
 *     | name          | core/get-site-info   |
 *     | label         | Get Site Information |
 *     | category      | site                 |
 *     | readonly      | 1                    |
 *     | show_in_rest  | 1                    |
 *     +---------------+----------------------+
 *
 *     # Execute an ability with JSON input (required for array values).
 *     $ wp ability run core/get-site-info --input='{"fields":["name","version"]}' --user=admin
 *     {
 *         "name": "Test Blog",
 *         "version": "6.9"
 *     }
 *
 *     # Check if an ability exists.
 *     $ wp ability exists core/get-site-info
 *     $ echo $?
 *     0
 *
 *     # Check if user can run an ability.
 *     $ wp ability can-run core/get-site-info
 *     $ echo $?
 *     0
 *
 *     # Validate input before execution.
 *     $ wp ability validate core/get-site-info --input='{"fields":["name","version"]}'
 *     Success: Input is valid.
 *
 * @when    after_wp_load
 * @package wp-cli
 */
class Ability_Command extends WP_CLI_Command {

	/**
	 * Default fields for list output.
	 *
	 * @var string[]
	 */
	protected $default_fields = [
		'name',
		'label',
		'category',
		'description',
	];

	/**
	 * Default fields for get output.
	 *
	 * @var string[]
	 */
	protected $get_fields = [
		'name',
		'label',
		'category',
		'description',
		'input_schema',
		'output_schema',
		'readonly',
		'destructive',
		'idempotent',
		'show_in_rest',
	];

	/**
	 * Lists all registered abilities.
	 *
	 * ## OPTIONS
	 *
	 * [--category=<slug>]
	 * : Filter abilities by category slug.
	 *
	 * [--namespace=<prefix>]
	 * : Filter abilities by namespace prefix (e.g., 'core' for 'core/*' abilities).
	 *
	 * [--show-in-rest=<bool>]
	 * : Filter abilities by REST API exposure.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each ability.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific ability fields.
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
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each ability:
	 *
	 * * name
	 * * label
	 * * category
	 * * description
	 *
	 * These fields are optionally available:
	 *
	 * * readonly
	 * * destructive
	 * * idempotent
	 * * show_in_rest
	 *
	 * ## EXAMPLES
	 *
	 *     # List all abilities.
	 *     $ wp ability list
	 *     +---------------------------+----------------------+----------+------------------------------------------+
	 *     | name                      | label                | category | description                              |
	 *     +---------------------------+----------------------+----------+------------------------------------------+
	 *     | core/get-site-info        | Get Site Information | site     | Returns site information configured i... |
	 *     | core/get-user-info        | Get User Information | user     | Returns basic profile details for the... |
	 *     +---------------------------+----------------------+----------+------------------------------------------+
	 *
	 *     # List abilities in a specific category.
	 *     $ wp ability list --category=site
	 *
	 *     # List abilities by namespace.
	 *     $ wp ability list --namespace=core
	 *
	 *     # List abilities exposed to REST API.
	 *     $ wp ability list --show-in-rest=true
	 *
	 *     # List abilities as JSON.
	 *     $ wp ability list --format=json
	 *
	 *     # List only ability names.
	 *     $ wp ability list --field=name
	 *     core/get-site-info
	 *     core/get-user-info
	 *     core/get-environment-info
	 *
	 * @subcommand list
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ): void {
		$abilities     = wp_get_abilities();
		$category_slug = Utils\get_flag_value( $assoc_args, 'category' );
		$namespace     = Utils\get_flag_value( $assoc_args, 'namespace' );
		$show_in_rest  = Utils\get_flag_value( $assoc_args, 'show-in-rest' );

		$items = [];

		foreach ( $abilities as $ability ) {
			$ability_data = $this->format_ability_for_list( $ability );

			// Filter by category if specified.
			if ( null !== $category_slug && $ability_data['category'] !== $category_slug ) {
				continue;
			}

			// Filter by namespace if specified.
			if ( null !== $namespace ) {
				$ability_name = isset( $ability_data['name'] ) && is_string( $ability_data['name'] ) ? $ability_data['name'] : '';
				$name_parts   = explode( '/', $ability_name, 2 );
				if ( $name_parts[0] !== $namespace ) {
					continue;
				}
			}

			// Filter by show_in_rest if specified.
			if ( null !== $show_in_rest ) {
				$show_in_rest_bool = filter_var( $show_in_rest, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				$ability_rest      = '1' === $ability_data['show_in_rest'];
				if ( null !== $show_in_rest_bool && $show_in_rest_bool !== $ability_rest ) {
					continue;
				}
			}

			$items[] = $ability_data;
		}

		$formatter = $this->get_formatter( $assoc_args, $this->default_fields );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered ability.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The ability name (namespace/ability-name format).
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole ability, returns the value of a single field.
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
	 * * name
	 * * label
	 * * category
	 * * description
	 * * input_schema
	 * * output_schema
	 * * readonly
	 * * destructive
	 * * idempotent
	 * * show_in_rest
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific ability.
	 *     $ wp ability get core/get-site-info
	 *     +---------------+----------------------+
	 *     | Field         | Value                |
	 *     +---------------+----------------------+
	 *     | name          | core/get-site-info   |
	 *     | label         | Get Site Information |
	 *     | category      | site                 |
	 *     | description   | Returns site info... |
	 *     | input_schema  | {"type":"object"}    |
	 *     | output_schema | {"type":"object"}    |
	 *     | readonly      | 1                    |
	 *     | destructive   | 0                    |
	 *     | idempotent    | 1                    |
	 *     | show_in_rest  | 1                    |
	 *     +---------------+----------------------+
	 *
	 *     # Get ability as JSON.
	 *     $ wp ability get core/get-site-info --format=json
	 *
	 *     # Get only the description.
	 *     $ wp ability get core/get-site-info --field=description
	 *     Returns site information configured in WordPress.
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ): void {
		$name    = $args[0];
		$ability = wp_get_ability( $name );

		if ( null === $ability ) {
			WP_CLI::error( "Ability '{$name}' not found." );
		}

		$ability_data = $this->format_ability_for_get( $ability );

		$formatter = $this->get_formatter( $assoc_args, $this->get_fields );
		$formatter->display_item( $ability_data );
	}

	/**
	 * Executes a registered ability.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The ability name (namespace/ability-name format).
	 *
	 * [--input=<json>]
	 * : JSON string containing input data for the ability. Use '-' to read from stdin.
	 *
	 * [--<field>=<value>]
	 * : Individual input fields. Alternative to --input for simple inputs.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 *   - var_export
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Execute an ability.
	 *     $ wp ability run core/get-site-info --user=admin
	 *     {
	 *         "name": "Test Blog",
	 *         "description": "Just another WordPress site",
	 *         "url": "http://example.com",
	 *         ...
	 *     }
	 *
	 *     # Execute an ability with JSON input (required for array values).
	 *     $ wp ability run core/get-site-info --input='{"fields":["name","version"]}' --user=admin
	 *     {
	 *         "name": "Test Blog",
	 *         "version": "6.9"
	 *     }
	 *
	 *     # Execute an ability with simple string arguments.
	 *     $ wp ability run my-plugin/greet --name=World
	 *
	 *     # Execute and output as YAML.
	 *     $ wp ability run core/get-site-info --format=yaml --user=admin
	 *     name: Test Blog
	 *     description: Just another WordPress site
	 *     ...
	 *
	 *     # Execute with input from stdin.
	 *     $ echo '{"fields":["name"]}' | wp ability run core/get-site-info --input=- --user=admin
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function run( $args, $assoc_args ): void {
		$name    = $args[0];
		$ability = wp_get_ability( $name );

		if ( null === $ability ) {
			WP_CLI::error( "Ability '{$name}' not found." );
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'json' );

		// Build input data (with stdin support).
		$input = $this->build_input_with_stdin( $assoc_args );

		// Execute the ability.
		$result = $ability->execute( $input );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		// Output the result.
		switch ( $format ) {
			case 'yaml':
				WP_CLI::line( Spyc::YAMLDump( $result, 2, 0 ) );
				break;
			case 'var_export':
				var_export( $result );
				break;
			case 'json':
			default:
				$json = wp_json_encode( $result, JSON_PRETTY_PRINT );
				if ( false !== $json ) {
					WP_CLI::line( $json );
				}
				break;
		}
	}

	/**
	 * Checks whether an ability is registered.
	 *
	 * Exits with return code 0 if the ability exists, 1 if it does not.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The ability name (namespace/ability-name format).
	 *
	 * ## EXAMPLES
	 *
	 *     # Check if an ability exists.
	 *     $ wp ability exists core/get-site-info
	 *     $ echo $?
	 *     0
	 *
	 *     # Check for non-existent ability.
	 *     $ wp ability exists nonexistent/ability
	 *     $ echo $?
	 *     1
	 *
	 *     # Use in a script.
	 *     $ if wp ability exists core/get-site-info; then
	 *     >   wp ability run core/get-site-info
	 *     > fi
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function exists( $args, $assoc_args ): void {
		$name   = $args[0];
		$exists = wp_has_ability( $name );

		WP_CLI::halt( $exists ? 0 : 1 );
	}

	/**
	 * Checks if the current user can execute an ability.
	 *
	 * Validates permissions without actually executing the ability.
	 * Exits with return code 0 if permitted, 1 if not.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The ability name (namespace/ability-name format).
	 *
	 * [--input=<json>]
	 * : JSON string containing input data for permission checking.
	 *
	 * [--<field>=<value>]
	 * : Individual input fields for permission checking.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check if current user can run an ability (as admin).
	 *     $ wp ability can-run core/get-site-info --user=admin
	 *     $ echo $?
	 *     0
	 *
	 *     # Check permission when not permitted.
	 *     $ wp ability can-run core/get-site-info
	 *     $ echo $?
	 *     1
	 *
	 *     # Use in a script.
	 *     $ if wp ability can-run core/get-site-info --user=admin; then
	 *     >   wp ability run core/get-site-info --user=admin
	 *     > fi
	 *
	 * @subcommand can-run
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function can_run( $args, $assoc_args ): void {
		$name    = $args[0];
		$ability = wp_get_ability( $name );

		if ( null === $ability ) {
			WP_CLI::error( "Ability '{$name}' not found." );
		}

		// Build input data for permission check.
		$input = $this->build_input( $assoc_args );

		// Check permissions.
		$can_run = $ability->check_permissions( $input );

		if ( is_wp_error( $can_run ) ) {
			WP_CLI::debug( $can_run->get_error_message() );
			WP_CLI::halt( 1 );
		}

		WP_CLI::halt( $can_run ? 0 : 1 );
	}

	/**
	 * Validates input against an ability's schema.
	 *
	 * Validates the input data without executing the ability.
	 * Useful for testing input before execution.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The ability name (namespace/ability-name format).
	 *
	 * [--input=<json>]
	 * : JSON string containing input data to validate.
	 *
	 * [--<field>=<value>]
	 * : Individual input fields to validate.
	 *
	 * ## EXAMPLES
	 *
	 *     # Validate input for an ability (use JSON for array values).
	 *     $ wp ability validate core/get-site-info --input='{"fields":["name","version"]}'
	 *     Success: Input is valid.
	 *
	 *     # Validate simple string arguments.
	 *     $ wp ability validate my-plugin/greet --name=World
	 *     Success: Input is valid.
	 *
	 *     # Validation failure shows error message.
	 *     $ wp ability validate core/get-site-info --input='{"fields":"invalid"}'
	 *     Error: Ability "core/get-site-info" has invalid input. Reason: ...
	 *
	 * @param string[] $args       Positional arguments.
	 * @param array    $assoc_args Associative arguments.
	 */
	public function validate( $args, $assoc_args ): void {
		$name    = $args[0];
		$ability = wp_get_ability( $name );

		if ( null === $ability ) {
			WP_CLI::error( "Ability '{$name}' not found." );
		}

		// Build input data.
		$input = $this->build_input( $assoc_args );

		// Normalize input (applies defaults from schema).
		$input = $ability->normalize_input( $input );

		// Validate input.
		$is_valid = $ability->validate_input( $input );

		if ( is_wp_error( $is_valid ) ) {
			WP_CLI::error( $is_valid->get_error_message() );
		}

		WP_CLI::success( 'Input is valid.' );
	}

	/**
	 * Builds input data from associative arguments.
	 *
	 * Returns null if no input was provided, which is important for abilities
	 * without an input schema (validate_input returns error for non-null input
	 * when there's no schema).
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return array<string,mixed>|null The input data, or null if none provided.
	 */
	private function build_input( $assoc_args ) {
		$input = [];

		// Check for JSON input first.
		$json_input = Utils\get_flag_value( $assoc_args, 'input' );
		if ( null !== $json_input ) {
			$decoded = json_decode( (string) $json_input, true );
			if ( ! is_array( $decoded ) && '' !== $json_input ) {
				WP_CLI::error( 'Invalid JSON provided for --input.' );
			}
			if ( is_array( $decoded ) ) {
				$input = $decoded;
			}
		}

		// Collect individual field arguments.
		$reserved_args = [ 'input', 'format' ];
		foreach ( $assoc_args as $key => $value ) {
			if ( ! is_string( $key ) || in_array( $key, $reserved_args, true ) ) {
				continue;
			}
			$input[ $key ] = $value;
		}

		// Return null if no input provided (for abilities without input schema).
		return empty( $input ) ? null : $input;
	}

	/**
	 * Builds input data from associative arguments with stdin support.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return array<string,mixed> The input data.
	 */
	private function build_input_with_stdin( $assoc_args ) {
		$input = [];

		// Check for JSON input first.
		$json_input = Utils\get_flag_value( $assoc_args, 'input' );
		if ( '-' === $json_input ) {
			// Read from stdin.
			$stdin_content = file_get_contents( 'php://stdin' );
			if ( false === $stdin_content ) {
				WP_CLI::error( 'Failed to read from stdin.' );
			}
			$json_input = trim( $stdin_content );
		}

		if ( null !== $json_input && '' !== $json_input ) {
			$decoded = json_decode( (string) $json_input, true );
			if ( ! is_array( $decoded ) ) {
				WP_CLI::error( 'Invalid JSON provided for --input.' );
			}
			$input = $decoded;
		}

		// Collect individual field arguments.
		$reserved_args = [ 'input', 'format' ];
		foreach ( $assoc_args as $key => $value ) {
			if ( ! is_string( $key ) || in_array( $key, $reserved_args, true ) ) {
				continue;
			}
			$input[ $key ] = $value;
		}

		return $input;
	}

	/**
	 * Formats an ability for list output.
	 *
	 * @param WP_Ability $ability The ability object.
	 * @return array<string,mixed>
	 */
	private function format_ability_for_list( $ability ) {
		$annotations = $this->get_annotations( $ability );

		return [
			'name'         => $ability->get_name(),
			'label'        => $ability->get_label(),
			'category'     => $ability->get_category(),
			'description'  => $ability->get_description(),
			'readonly'     => $this->format_annotation( $annotations['readonly'] ),
			'destructive'  => $this->format_annotation( $annotations['destructive'] ),
			'idempotent'   => $this->format_annotation( $annotations['idempotent'] ),
			'show_in_rest' => $ability->get_meta_item( 'show_in_rest', false ) ? '1' : '0',
		];
	}

	/**
	 * Formats an ability for get output.
	 *
	 * @param WP_Ability $ability The ability object.
	 * @return array<string,mixed>
	 */
	private function format_ability_for_get( $ability ) {
		$annotations = $this->get_annotations( $ability );

		return [
			'name'          => $ability->get_name(),
			'label'         => $ability->get_label(),
			'category'      => $ability->get_category(),
			'description'   => $ability->get_description(),
			'input_schema'  => wp_json_encode( $ability->get_input_schema() ),
			'output_schema' => wp_json_encode( $ability->get_output_schema() ),
			'readonly'      => $this->format_annotation( $annotations['readonly'] ),
			'destructive'   => $this->format_annotation( $annotations['destructive'] ),
			'idempotent'    => $this->format_annotation( $annotations['idempotent'] ),
			'show_in_rest'  => $ability->get_meta_item( 'show_in_rest', false ) ? '1' : '0',
		];
	}

	/**
	 * Gets annotations from an ability with proper typing.
	 *
	 * @param WP_Ability $ability The ability object.
	 * @return array{readonly: bool|null, destructive: bool|null, idempotent: bool|null}
	 */
	private function get_annotations( $ability ) {
		$meta_annotations = $ability->get_meta_item( 'annotations', [] );
		$annotations      = is_array( $meta_annotations ) ? $meta_annotations : [];

		return [
			'readonly'    => isset( $annotations['readonly'] ) && is_bool( $annotations['readonly'] ) ? $annotations['readonly'] : null,
			'destructive' => isset( $annotations['destructive'] ) && is_bool( $annotations['destructive'] ) ? $annotations['destructive'] : null,
			'idempotent'  => isset( $annotations['idempotent'] ) && is_bool( $annotations['idempotent'] ) ? $annotations['idempotent'] : null,
		];
	}

	/**
	 * Formats an annotation value for output.
	 *
	 * @param bool|null $value The annotation value.
	 * @return string '1' for true, '0' for false, '' for null.
	 */
	private function format_annotation( $value ) {
		if ( null === $value ) {
			return '';
		}
		return $value ? '1' : '0';
	}

	/**
	 * Gets the formatter object.
	 *
	 * @param array    $assoc_args     Associative arguments.
	 * @param string[] $default_fields Default fields to display.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args, $default_fields ) {
		return new Formatter( $assoc_args, $default_fields );
	}
}
