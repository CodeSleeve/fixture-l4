<?php namespace Codesleeve\Fixture;

use Illuminate\Support\ServiceProvider;

class FixtureServiceProvider extends ServiceProvider {

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
		$this->package('codesleeve/fixture');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('fixture', function()
		{
		    return Fixture::getInstance($this->app['db'], $this->app['Str'], $this->app['config']->get('fixture::config'));
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