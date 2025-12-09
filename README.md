wp-cli/ability-command
======================

Lists, inspects, and executes abilities registered via the WordPress Abilities API.

[![Testing](https://github.com/wp-cli/ability-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/ability-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp ability

Lists, inspects, and executes abilities registered via the WordPress Abilities API.

~~~
wp ability
~~~

The Abilities API, introduced in WordPress 6.9, provides a standardized way
to register and discover distinct units of functionality within a WordPress site.

**EXAMPLES**

    # List all registered abilities.
    $ wp ability list
    +---------------------------+----------------------+----------+------------------------------------------+
    | name                      | label                | category | description                              |
    +---------------------------+----------------------+----------+------------------------------------------+
    | core/get-site-info        | Get Site Information | site     | Returns site information configured i... |
    | core/get-user-info        | Get User Information | user     | Returns basic profile details for the... |
    | core/get-environment-info | Get Environment Info | site     | Returns core details about the site's... |
    +---------------------------+----------------------+----------+------------------------------------------+

    # Get details of a specific ability.
    $ wp ability get core/get-site-info --fields=name,label,category,readonly,show_in_rest
    +---------------+----------------------+
    | Field         | Value                |
    +---------------+----------------------+
    | name          | core/get-site-info   |
    | label         | Get Site Information |
    | category      | site                 |
    | readonly      | 1                    |
    | show_in_rest  | 1                    |
    +---------------+----------------------+

    # Execute an ability with JSON input (required for array values).
    $ wp ability run core/get-site-info --input='{"fields":["name","version"]}' --user=admin
    {
        "name": "Test Blog",
        "version": "6.9"
    }

    # Check if an ability exists.
    $ wp ability exists core/get-site-info
    $ echo $?
    0

    # Check if user can run an ability.
    $ wp ability can-run core/get-site-info
    $ echo $?
    0

    # Validate input before execution.
    $ wp ability validate core/get-site-info --input='{"fields":["name","version"]}'
    Success: Input is valid.



### wp ability list

Lists all registered abilities.

~~~
wp ability list [--category=<slug>] [--namespace=<prefix>] [--show-in-rest=<bool>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--category=<slug>]
		Filter abilities by category slug.

	[--namespace=<prefix>]
		Filter abilities by namespace prefix (e.g., 'core' for 'core/*' abilities).

	[--show-in-rest=<bool>]
		Filter abilities by REST API exposure.

	[--field=<field>]
		Prints the value of a single field for each ability.

	[--fields=<fields>]
		Limit the output to specific ability fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each ability:

* name
* label
* category
* description

These fields are optionally available:

* readonly
* destructive
* idempotent
* show_in_rest

**EXAMPLES**

    # List all abilities.
    $ wp ability list
    +---------------------------+----------------------+----------+------------------------------------------+
    | name                      | label                | category | description                              |
    +---------------------------+----------------------+----------+------------------------------------------+
    | core/get-site-info        | Get Site Information | site     | Returns site information configured i... |
    | core/get-user-info        | Get User Information | user     | Returns basic profile details for the... |
    +---------------------------+----------------------+----------+------------------------------------------+

    # List abilities in a specific category.
    $ wp ability list --category=site

    # List abilities by namespace.
    $ wp ability list --namespace=core

    # List abilities exposed to REST API.
    $ wp ability list --show-in-rest=true

    # List abilities as JSON.
    $ wp ability list --format=json

    # List only ability names.
    $ wp ability list --field=name
    core/get-site-info
    core/get-user-info
    core/get-environment-info



### wp ability get

Gets details about a registered ability.

~~~
wp ability get <name> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<name>
		The ability name (namespace/ability-name format).

	[--field=<field>]
		Instead of returning the whole ability, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

* name
* label
* category
* description
* input_schema
* output_schema
* readonly
* destructive
* idempotent
* show_in_rest

**EXAMPLES**

    # Get details of a specific ability.
    $ wp ability get core/get-site-info
    +---------------+----------------------+
    | Field         | Value                |
    +---------------+----------------------+
    | name          | core/get-site-info   |
    | label         | Get Site Information |
    | category      | site                 |
    | description   | Returns site info... |
    | input_schema  | {"type":"object"}    |
    | output_schema | {"type":"object"}    |
    | readonly      | 1                    |
    | destructive   | 0                    |
    | idempotent    | 1                    |
    | show_in_rest  | 1                    |
    +---------------+----------------------+

    # Get ability as JSON.
    $ wp ability get core/get-site-info --format=json

    # Get only the description.
    $ wp ability get core/get-site-info --field=description
    Returns site information configured in WordPress.



### wp ability run

Executes a registered ability.

~~~
wp ability run <name> [--input=<json>] [--<field>=<value>] [--format=<format>]
~~~

**OPTIONS**

	<name>
		The ability name (namespace/ability-name format).

	[--input=<json>]
		JSON string containing input data for the ability. Use '-' to read from stdin.

	[--<field>=<value>]
		Individual input fields. Alternative to --input for simple inputs.

	[--format=<format>]
		Render output in a particular format.
		---
		default: json
		options:
		  - json
		  - yaml
		  - var_export
		---

**EXAMPLES**

    # Execute an ability.
    $ wp ability run core/get-site-info --user=admin
    {
        "name": "Test Blog",
        "description": "Just another WordPress site",
        "url": "http://example.com",
        ...
    }

    # Execute an ability with JSON input (required for array values).
    $ wp ability run core/get-site-info --input='{"fields":["name","version"]}' --user=admin
    {
        "name": "Test Blog",
        "version": "6.9"
    }

    # Execute an ability with simple string arguments.
    $ wp ability run my-plugin/greet --name=World

    # Execute and output as YAML.
    $ wp ability run core/get-site-info --format=yaml --user=admin
    name: Test Blog
    description: Just another WordPress site
    ...

    # Execute with input from stdin.
    $ echo '{"fields":["name"]}' | wp ability run core/get-site-info --input=- --user=admin



### wp ability exists

Checks whether an ability is registered.

~~~
wp ability exists <name>
~~~

Exits with return code 0 if the ability exists, 1 if it does not.

**OPTIONS**

	<name>
		The ability name (namespace/ability-name format).

**EXAMPLES**

    # Check if an ability exists.
    $ wp ability exists core/get-site-info
    $ echo $?
    0

    # Check for non-existent ability.
    $ wp ability exists nonexistent/ability
    $ echo $?
    1

    # Use in a script.
    $ if wp ability exists core/get-site-info; then
    >   wp ability run core/get-site-info
    > fi



### wp ability can-run

Checks if the current user can execute an ability.

~~~
wp ability can-run <name> [--input=<json>] [--<field>=<value>]
~~~

Validates permissions without actually executing the ability.
Exits with return code 0 if permitted, 1 if not.

**OPTIONS**

	<name>
		The ability name (namespace/ability-name format).

	[--input=<json>]
		JSON string containing input data for permission checking.

	[--<field>=<value>]
		Individual input fields for permission checking.

**EXAMPLES**

    # Check if current user can run an ability (as admin).
    $ wp ability can-run core/get-site-info --user=admin
    $ echo $?
    0

    # Check permission when not permitted.
    $ wp ability can-run core/get-site-info
    $ echo $?
    1

    # Use in a script.
    $ if wp ability can-run core/get-site-info --user=admin; then
    >   wp ability run core/get-site-info --user=admin
    > fi



### wp ability validate

Validates input against an ability's schema.

~~~
wp ability validate <name> [--input=<json>] [--<field>=<value>]
~~~

Validates the input data without executing the ability.
Useful for testing input before execution.

**OPTIONS**

	<name>
		The ability name (namespace/ability-name format).

	[--input=<json>]
		JSON string containing input data to validate.

	[--<field>=<value>]
		Individual input fields to validate.

**EXAMPLES**

    # Validate input for an ability (use JSON for array values).
    $ wp ability validate core/get-site-info --input='{"fields":["name","version"]}'
    Success: Input is valid.

    # Validate simple string arguments.
    $ wp ability validate my-plugin/greet --name=World
    Success: Input is valid.

    # Validation failure shows error message.
    $ wp ability validate core/get-site-info --input='{"fields":"invalid"}'
    Error: Ability "core/get-site-info" has invalid input. Reason: ...



### wp ability category

Lists and inspects ability categories registered via the WordPress Abilities API.

~~~
wp ability category
~~~

The Abilities API, introduced in WordPress 6.9, uses categories to organize
related abilities for better discoverability.

**EXAMPLES**

    # List all registered ability categories.
    $ wp ability category list
    +------+-------+-----------------------------------------------------+
    | slug | label | description                                         |
    +------+-------+-----------------------------------------------------+
    | site | Site  | Abilities that retrieve or modify site information. |
    | user | User  | Abilities that retrieve or modify user information. |
    +------+-------+-----------------------------------------------------+

    # Get details of a specific category.
    $ wp ability category get site
    +-------------+-----------------------------------------------------+
    | Field       | Value                                               |
    +-------------+-----------------------------------------------------+
    | slug        | site                                                |
    | label       | Site                                                |
    | description | Abilities that retrieve or modify site information. |
    | meta        | {}                                                  |
    +-------------+-----------------------------------------------------+

    # Check if a category exists.
    $ wp ability category exists site
    $ echo $?
    0





### wp ability category list

Lists all registered ability categories.

~~~
wp ability category list [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--field=<field>]
		Prints the value of a single field for each category.

	[--fields=<fields>]
		Limit the output to specific category fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each category:

* slug
* label
* description

**EXAMPLES**

    # List all categories.
    $ wp ability category list
    +------+-------+-----------------------------------------------------+
    | slug | label | description                                         |
    +------+-------+-----------------------------------------------------+
    | site | Site  | Abilities that retrieve or modify site information. |
    | user | User  | Abilities that retrieve or modify user information. |
    +------+-------+-----------------------------------------------------+

    # List categories as JSON.
    $ wp ability category list --format=json
    [{"slug":"site","label":"Site","description":"..."},{"slug":"user",...}]

    # List only category slugs.
    $ wp ability category list --field=slug
    site
    user



### wp ability category get

Gets details about a registered ability category.

~~~
wp ability category get <slug> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<slug>
		The category slug.

	[--field=<field>]
		Instead of returning the whole category, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

* slug
* label
* description
* meta

**EXAMPLES**

    # Get details of a specific category.
    $ wp ability category get site
    +-------------+-----------------------------------------------------+
    | Field       | Value                                               |
    +-------------+-----------------------------------------------------+
    | slug        | site                                                |
    | label       | Site                                                |
    | description | Abilities that retrieve or modify site information. |
    | meta        | {}                                                  |
    +-------------+-----------------------------------------------------+

    # Get category as JSON.
    $ wp ability category get site --format=json
    {"slug":"site","label":"Site","description":"...","meta":"{}"}

    # Get only the label.
    $ wp ability category get site --field=label
    Site



### wp ability category exists

Checks whether an ability category is registered.

~~~
wp ability category exists <slug>
~~~

Exits with return code 0 if the category exists, 1 if it does not.

**OPTIONS**

	<slug>
		The category slug.

**EXAMPLES**

    # Check if a category exists.
    $ wp ability category exists site
    $ echo $?
    0

    # Check for non-existent category.
    $ wp ability category exists nonexistent
    $ echo $?
    1

    # Use in a script.
    $ if wp ability category exists site; then
    >   echo "Category exists"
    > fi

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/ability-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/ability-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/ability-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/ability-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
