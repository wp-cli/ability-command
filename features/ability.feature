Feature: Manage abilities registered via the WordPress Abilities API.

  Background:
    Given a WP install

  @less-than-wp-6.9
  Scenario: Require WordPress 6.9 or greater.
    When I try `wp ability list`
    Then STDERR should contain:
      """
      Error: Requires WordPress 6.9 or greater.
      """

  @require-wp-6.9
  Scenario: Register and list abilities.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category for abilities.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/get-site-title', array(
              'label'               => 'Get Site Title',
              'description'         => 'Returns the site title.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'title' => get_bloginfo( 'name' ) );
              },
              'permission_callback' => '__return_true',
              'meta'                => array( 'show_in_rest' => true ),
          ) );

          wp_register_ability( 'test-plugin/echo-input', array(
              'label'               => 'Echo Input',
              'description'         => 'Echoes back the input.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return $input;
              },
              'permission_callback' => '__return_true',
          ) );
      } );
      """

    When I run `wp ability list`
    Then STDOUT should be a table containing rows:
      | name                       | label           | category       | description              |
      | test-plugin/get-site-title | Get Site Title  | test-category  | Returns the site title.  |
      | test-plugin/echo-input     | Echo Input      | test-category  | Echoes back the input.   |

    When I run `wp ability list --field=name`
    Then STDOUT should contain:
      """
      test-plugin/get-site-title
      """
    And STDOUT should contain:
      """
      test-plugin/echo-input
      """

    When I run `wp ability list --category=test-category`
    Then STDOUT should be a table containing rows:
      | name                       | label           | category       | description              |
      | test-plugin/get-site-title | Get Site Title  | test-category  | Returns the site title.  |

    When I run `wp ability list --category=nonexistent --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp ability list --format=json`
    Then STDOUT should contain:
      """
      "name":"test-plugin\/get-site-title"
      """

  @require-wp-6.9
  Scenario: Get ability details.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/get-site-title', array(
              'label'               => 'Get Site Title',
              'description'         => 'Returns the site title.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'title' => get_bloginfo( 'name' ) );
              },
              'permission_callback' => '__return_true',
              'meta'                => array( 'show_in_rest' => true ),
          ) );
      } );
      """

    When I run `wp ability get test-plugin/get-site-title`
    Then STDOUT should be a table containing rows:
      | Field       | Value                       |
      | name        | test-plugin/get-site-title  |
      | label       | Get Site Title              |
      | category    | test-category               |
      | description | Returns the site title.     |

    When I run `wp ability get test-plugin/get-site-title --field=label`
    Then STDOUT should be:
      """
      Get Site Title
      """

    When I run `wp ability get test-plugin/get-site-title --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"test-plugin/get-site-title"}
      """

    When I try `wp ability get nonexistent/ability`
    Then STDERR should be:
      """
      Error: Ability 'nonexistent/ability' not found.
      """

  @require-wp-6.9
  Scenario: Execute an ability.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/get-site-title', array(
              'label'               => 'Get Site Title',
              'description'         => 'Returns the site title.',
              'category'            => 'test-category',
              'input_schema'        => array(
                  'type'       => 'object',
                  'properties' => array(),
              ),
              'execute_callback'    => function( $input ) {
                  return array( 'title' => get_bloginfo( 'name' ) );
              },
              'permission_callback' => '__return_true',
          ) );

          wp_register_ability( 'test-plugin/echo-input', array(
              'label'               => 'Echo Input',
              'description'         => 'Echoes back the input.',
              'category'            => 'test-category',
              'input_schema'        => array(
                  'type'                 => 'object',
                  'additionalProperties' => true,
              ),
              'execute_callback'    => function( $input ) {
                  return $input;
              },
              'permission_callback' => '__return_true',
          ) );

          wp_register_ability( 'test-plugin/add-numbers', array(
              'label'               => 'Add Numbers',
              'description'         => 'Adds two numbers.',
              'category'            => 'test-category',
              'input_schema'        => array(
                  'type'       => 'object',
                  'properties' => array(
                      'a' => array( 'type' => 'integer' ),
                      'b' => array( 'type' => 'integer' ),
                  ),
              ),
              'execute_callback'    => function( $input ) {
                  $a = isset( $input['a'] ) ? (int) $input['a'] : 0;
                  $b = isset( $input['b'] ) ? (int) $input['b'] : 0;
                  return array( 'sum' => $a + $b );
              },
              'permission_callback' => '__return_true',
          ) );
      } );
      """

    When I run `wp ability run test-plugin/get-site-title`
    Then STDOUT should contain:
      """
      "title":
      """

    When I run `wp ability run test-plugin/echo-input --message=hello`
    Then STDOUT should be JSON containing:
      """
      {"message":"hello"}
      """

    When I run `wp ability run test-plugin/echo-input --input='{"foo":"bar"}'`
    Then STDOUT should be JSON containing:
      """
      {"foo":"bar"}
      """

    When I run `wp ability run test-plugin/add-numbers --a=5 --b=3`
    Then STDOUT should be JSON containing:
      """
      {"sum":8}
      """

    When I run `wp ability run test-plugin/add-numbers --input='{"a":10,"b":20}'`
    Then STDOUT should be JSON containing:
      """
      {"sum":30}
      """

    When I run `wp ability run test-plugin/get-site-title --format=yaml`
    Then STDOUT should contain:
      """
      title:
      """

    When I try `wp ability run nonexistent/ability`
    Then STDERR should be:
      """
      Error: Ability 'nonexistent/ability' not found.
      """

    When I try `wp ability run test-plugin/echo-input --input='invalid json'`
    Then STDERR should be:
      """
      Error: Invalid JSON provided for --input.
      """

  @require-wp-6.9
  Scenario: Check if ability exists.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/get-site-title', array(
              'label'               => 'Get Site Title',
              'description'         => 'Returns the site title.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'title' => get_bloginfo( 'name' ) );
              },
              'permission_callback' => '__return_true',
          ) );
      } );
      """

    When I run `wp ability exists test-plugin/get-site-title`
    Then the return code should be 0

    When I try `wp ability exists nonexistent/ability`
    Then the return code should be 1

  @require-wp-6.9
  Scenario: Filter abilities by namespace.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/get-site-title', array(
              'label'               => 'Get Site Title',
              'description'         => 'Returns the site title.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'title' => get_bloginfo( 'name' ) );
              },
              'permission_callback' => '__return_true',
              'meta'                => array( 'show_in_rest' => true ),
          ) );

          wp_register_ability( 'other-plugin/do-something', array(
              'label'               => 'Do Something',
              'description'         => 'Does something.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'result' => 'done' );
              },
              'permission_callback' => '__return_true',
              'meta'                => array( 'show_in_rest' => false ),
          ) );
      } );
      """

    When I run `wp ability list --namespace=test-plugin --format=count`
    Then STDOUT should contain:
      """
      1
      """

    When I run `wp ability list --namespace=other-plugin --field=name`
    Then STDOUT should contain:
      """
      other-plugin/do-something
      """

    When I run `wp ability list --namespace=nonexistent --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-6.9
  Scenario: Filter abilities by show_in_rest.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/public-ability', array(
              'label'               => 'Public Ability',
              'description'         => 'A public ability.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'public' => true );
              },
              'permission_callback' => '__return_true',
              'meta'                => array( 'show_in_rest' => true ),
          ) );

          wp_register_ability( 'test-plugin/private-ability', array(
              'label'               => 'Private Ability',
              'description'         => 'A private ability.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'private' => true );
              },
              'permission_callback' => '__return_true',
              'meta'                => array( 'show_in_rest' => false ),
          ) );
      } );
      """

    When I run `wp ability list --namespace=test-plugin --show-in-rest=true --field=name`
    Then STDOUT should be:
      """
      test-plugin/public-ability
      """

    When I run `wp ability list --namespace=test-plugin --show-in-rest=false --field=name`
    Then STDOUT should be:
      """
      test-plugin/private-ability
      """

  @require-wp-6.9
  Scenario: Display ability annotations.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/annotated-ability', array(
              'label'               => 'Annotated Ability',
              'description'         => 'An ability with annotations.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'result' => 'done' );
              },
              'permission_callback' => '__return_true',
              'meta'                => array(
                  'annotations' => array(
                      'readonly'    => true,
                      'destructive' => false,
                      'idempotent'  => true,
                  ),
              ),
          ) );
      } );
      """

    When I run `wp ability get test-plugin/annotated-ability --format=json`
    Then STDOUT should be JSON containing:
      """
      {"readonly":"1","destructive":"0","idempotent":"1"}
      """

    When I run `wp ability list --namespace=test-plugin --fields=name,readonly,destructive,idempotent`
    Then STDOUT should be a table containing rows:
      | name                           | readonly | destructive | idempotent |
      | test-plugin/annotated-ability  | 1        | 0           | 1          |

  @require-wp-6.9
  Scenario: Check permission to run an ability.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/allowed-ability', array(
              'label'               => 'Allowed Ability',
              'description'         => 'An ability anyone can run.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'allowed' => true );
              },
              'permission_callback' => '__return_true',
          ) );

          wp_register_ability( 'test-plugin/denied-ability', array(
              'label'               => 'Denied Ability',
              'description'         => 'An ability no one can run.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'denied' => true );
              },
              'permission_callback' => '__return_false',
          ) );
      } );
      """

    When I run `wp ability can-run test-plugin/allowed-ability`
    Then the return code should be 0

    When I try `wp ability can-run test-plugin/denied-ability`
    Then the return code should be 1

    When I try `wp ability can-run nonexistent/ability`
    Then STDERR should be:
      """
      Error: Ability 'nonexistent/ability' not found.
      """

  @require-wp-6.9
  Scenario: Validate ability input.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'test-category', array(
              'label'       => 'Test Category',
              'description' => 'A test category.',
          ) );
      } );

      add_action( 'wp_abilities_api_init', function() {
          wp_register_ability( 'test-plugin/typed-ability', array(
              'label'               => 'Typed Ability',
              'description'         => 'An ability with typed input.',
              'category'            => 'test-category',
              'input_schema'        => array(
                  'type'       => 'object',
                  'properties' => array(
                      'count' => array( 'type' => 'integer' ),
                      'name'  => array( 'type' => 'string' ),
                  ),
                  'required'   => array( 'count' ),
              ),
              'execute_callback'    => function( $input ) {
                  return $input;
              },
              'permission_callback' => '__return_true',
          ) );

          wp_register_ability( 'test-plugin/no-input-ability', array(
              'label'               => 'No Input Ability',
              'description'         => 'An ability with no input schema.',
              'category'            => 'test-category',
              'execute_callback'    => function( $input ) {
                  return array( 'no_input' => true );
              },
              'permission_callback' => '__return_true',
          ) );
      } );
      """

    When I run `wp ability validate test-plugin/typed-ability --count=5 --name=test`
    Then STDOUT should be:
      """
      Success: Input is valid.
      """

    When I run `wp ability validate test-plugin/typed-ability --input='{"count":10,"name":"hello"}'`
    Then STDOUT should be:
      """
      Success: Input is valid.
      """

    When I try `wp ability validate test-plugin/typed-ability --name=test`
    Then STDERR should contain:
      """
      Error:
      """

    When I run `wp ability validate test-plugin/no-input-ability`
    Then STDOUT should be:
      """
      Success: Input is valid.
      """

    When I try `wp ability validate nonexistent/ability`
    Then STDERR should be:
      """
      Error: Ability 'nonexistent/ability' not found.
      """
