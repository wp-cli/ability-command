Feature: Manage ability categories registered via the WordPress Abilities API.

  Background:
    Given a WP install

  @less-than-wp-6.9
  Scenario: Require WordPress 6.9 or greater.
    When I try `wp ability category list`
    Then STDERR should contain:
      """
      Error: Requires WordPress 6.9 or greater.
      """

  @require-wp-6.9
  Scenario: List ability categories.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'content', array(
              'label'       => 'Content',
              'description' => 'Content management abilities.',
          ) );

          wp_register_ability_category( 'ai', array(
              'label'       => 'AI',
              'description' => 'AI-powered abilities.',
          ) );
      } );
      """

    When I run `wp ability category list`
    Then STDOUT should be a table containing rows:
      | slug    | label   | description                   |
      | content | Content | Content management abilities. |
      | ai      | AI      | AI-powered abilities.         |

    When I run `wp ability category list --field=slug`
    Then STDOUT should contain:
      """
      content
      """
    And STDOUT should contain:
      """
      ai
      """

    When I run `wp ability category list --format=json`
    Then STDOUT should contain:
      """
      {"slug":"content"
      """

  @require-wp-6.9
  Scenario: Get ability category details.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'content', array(
              'label'       => 'Content',
              'description' => 'Content management abilities.',
          ) );
      } );
      """

    When I run `wp ability category get content`
    Then STDOUT should be a table containing rows:
      | Field       | Value                         |
      | slug        | content                       |
      | label       | Content                       |
      | description | Content management abilities. |

    When I run `wp ability category get content --field=label`
    Then STDOUT should be:
      """
      Content
      """

    When I run `wp ability category get content --format=json`
    Then STDOUT should be JSON containing:
      """
      {"slug":"content"}
      """

    When I try `wp ability category get nonexistent`
    Then STDERR should be:
      """
      Error: Ability category 'nonexistent' not found.
      """

  @require-wp-6.9
  Scenario: Check if ability category exists.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'content', array(
              'label'       => 'Content',
              'description' => 'Content management abilities.',
          ) );
      } );
      """

    When I run `wp ability category exists content`
    Then the return code should be 0

    When I try `wp ability category exists nonexistent`
    Then the return code should be 1

  @require-wp-6.9
  Scenario: Get ability category with meta.
    Given a wp-content/mu-plugins/test-ability.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
          wp_register_ability_category( 'custom-meta', array(
              'label'       => 'Custom Meta Category',
              'description' => 'A category with custom meta.',
              'meta'        => array(
                  'custom_key' => 'custom_value',
                  'version'    => '1.0',
              ),
          ) );

          wp_register_ability_category( 'no-meta', array(
              'label'       => 'No Meta Category',
              'description' => 'A category without custom meta.',
          ) );
      } );
      """

    When I run `wp ability category get custom-meta --format=json`
    Then STDOUT should be JSON containing:
      """
      {"meta":"{\"custom_key\":\"custom_value\",\"version\":\"1.0\"}"}
      """

    When I run `wp ability category get no-meta --field=meta`
    Then STDOUT should be:
      """
      {}
      """
