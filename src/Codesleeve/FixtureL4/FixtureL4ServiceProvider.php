<?php namespace Codesleeve\FixtureL4;

use Illuminate\Support\ServiceProvider;

class FixtureL4ServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('codesleeve/fixture-l4');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('repository', function() {
			return new Repositories\IlluminateDatabaseRepository($this->app['db']);
		});

		$this->app->bind('fixture', function()
		{
		    $fixture = Fixture::getInstance();
		    $fixture->setRepository($this->app['repository']);
		    $fixture->setStr($this->app['Str']);
		    $fixture->setConfig($this->app['config']->get('fixture-l4::config'));

		    return $fixture;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}