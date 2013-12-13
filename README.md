#Fixture-L4
A fixture libraray for the Laravel 4 framework.  

## Requirements
* Laravel >= 4
* php >= 5.3

## Installation
Fixture is distributed as a composer package, which is how it should be used in your app.

Install the package using Composer.  Edit your project's `composer.json` file to require `codesleeve/fixture-l4`.

```js
  "require": {
    "codesleeve/fixture-l4": "dev-master"
  }
```

Once this operation completes, add the service provider. Open `app/config/app.php`, and add a new item to the providers array.

```php
    'Codesleeve\FixtureL4\FixtureServiceProvider'
```

Finally, add the Fixture facade to the aliases array (still inside `app/config/app.php`).

```php
	'Fixture' => 'Codesleeve\FixtureL4\Facades\Fixture',
```

## Overview
This package is a simple wrapper for adding some L4 goodness to the existings codesleeve\fixture package that can be found [here](https://github.com/CodeSleeve/fixture).  

## Example
### Step 1 - Fixture setup
Inside your application test folder, create a folder named fixtures.  Next, create a couple of fixture files inside this folder.  Fixture files are written using native php array syntax.  To create one, simply create a new file named after the table that the fixture corresponds to and have it return an array of data.  As an example of this, let's create some fixture data for a hypothetical authentication system.  In this system, we let's assume that we have both roles and users and that a user belongsToManyRoles (we're assumign the existance of a roles_users join table).  To insert some user fixture data into our database, all we need to do is create a couple of fixture files:

in tests/fixtures/users.php
```php
return array (
	'Travis' => array (
		'first_name' => 'Travis',
		'last_name'  => 'Bennett',
		'roles'      => 'admin, endUser'		
	),
	'Kelt' => array (
		'first_name' => 'Kelt',
		'last_name'  => 'Dockins',
		'roles' 	 => 'endUser'		
	)
);
```

in tests/fixtures/roles.php
```php
return array (
	'admin' => array (
		'name' => 'Admin'
	),
	'endUser' => array (
		'name' => 'End User',
	)
);
```

For each of these fixture files we're simple returning a nested array containing our fixture data.  Also, notice that each fixture record has a unique name; This is very important!  Because each fixture has a unique name, we can easily populate our 'roles_users' join table simply by referencing the relationship in the users fixture and passing in a comma separated list of roles we want that user to have (note that we are assuming a belongsToMany relationship named 'roles' exists on the User model).  We can also easily populate hasOne, hasMany, and belongsTo relationships in our fixtures as well.  To show this, let's go ahead and extend our example authentications system to include the concept of profiles.  We'll assume the existence of a profiles table and assume that a profile belongs to a user and that a user has one profile:

in tests/fixtures/profiles.php
```php
return array (
	'Travis Profile' => array (
		'user' => 'Travis',
		'email' => 'ktd_@hotmail.com',
		'state' => 'AR',
		'country' => 'US',
		'bio' => 'Travis bio information goes here.'
	),
	'Kelt Profile' => array (
		'user' => 'Kelt',
		'email' => 'ktd_@hotmail.com',
		'state' => 'AR',
		'country' => 'US',
		'bio' =. 'Kelt bio information goes here.'
	)
);
``` 

Notice that once again we simpley reference the name of the relationship ('user' in this case) inside the fixture.  No need to add a 'user_id' field; Fixture is smart enough to look up the relationship ('belongsTo') via the user column and populate it with the correct foreign key value.  No need to worry about juggling foreign keys, no need to worry about the order in which records are created. 

### Step 2 - Invoke Fixture::up() and Fixture::down() inside your unit tests.
Now that our fixtures have been created, all we need to do in order to load them into our database is to invoke the Fixture::up() method within our tests.  Before this can happen though we need to make sure that the database tables themselves have been created (don't worry about seeding).  Regardless of how your test database is configured (mysql, sqlite, sqlite in memory, etc) you're going to need to run migrations (at least once) in order to initialize your test database with tables.  If you're using mysql, postresql, or sqlite, simple make a call to laravel's migrate command from the command line before running your tests.  However, if you're running an in memory sqlite database (which you probably should be), you're going to need to do this right before your tests start running.  One way of accomplishing this is to tap into the createApplication method of the base TestCase supplied with Laravel:

```php
	/**
	 * Creates the application.
	 *
	 * @return Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	public function createApplication()
	{
		$unitTesting = true;

		$testEnvironment = 'testing';

		$start = require __DIR__.'/../../bootstrap/start.php';
		
		Artisan::call('migrate');
		
		return $start;
	}
```

Now that we've got the test databse set up, consider the following test (we're using PHPUnit here, but the testing framework doesn't matter; SimpleTest would work just as well):

in tests/exampleTest.php
```php
<?php

	class ExampleTest extends TestCase {

		public function setUp()
		{
			parent::setUp();

			Fixture::up()
		}

		public function tearDown()
		{
			Fixture::down();
		}
	}
?>
```

What's going on here?  A few things:
* We're invoking the up() method on the fixture facade.  This method seeds the database and caches the inserted records as Eloquent objects that can be accessed via the Fixture facade.
	* Invoking the up method with no params will seed all fixtures.
	* Invoking the up method with an array of fixture names will seed only those fixtures (e.g Fixture::up(array('soul_reapers')) would seed the soul_reapers table only).
* In the tearDown method we're invoking the down() method.  This method will truncate all tables that have had fixture data inserted into them.

In your tests, seeded database records can be accessed (if needed) as Eloquent objects from the fixture facade:

```php
// Returns 'Bennett'
echo Fixture::users('Travis')->last_name;

// Returns 'AR'
echo Fixture::users('Kelt')->profile->state;

// Returns 'Admin'
echo Fixture::roles('admin')->name;
```
By using fixtures to seed our test database we've gained very precise control over what's in our database at any given time during a test.  This in turn allows us to very easily test the pieces of our application that contain database specific logic. 