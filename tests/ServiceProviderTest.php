<?php

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\App;
use Codesleeve\FixtureL4\Facades\Fixture;

class FixtureTest extends PHPUnit_Framework_TestCase
{
	/**
     * setUp method.
     */
	public function setUp()
	{
		// Bootstrap the application container
        $app = new Application;
		$app->instance('app', $app);
		$app->register('Codesleeve\FixtureL4\FixtureL4ServiceProvider');
		Facade::setFacadeApplication($app);
	}

    /**
     * tearDown method.
     */
    public function tearDown()
    {
    	m::close();
    }

    /**
     * Test that the service provider can register an instance of fixture
     * with the application container.
     * 
     * @test
     * @return void
     */
    public function it_should_be_able_to_register_an_instance_of_fixture_with_the_container()
    {
	   	App::bind('db', function($app) {
	   		$mockedDB = m::mock('Illuminate\Database\DatabaseManager');
			$mockedConnection = m::mock('Illuminate\Database\Connection');
			$pdo = new PDO('sqlite::memory:');
			$mockedDB->shouldReceive('connection')->once()->andReturn($mockedConnection);
		   	$mockedConnection->shouldReceive('getPdo')->once()->andReturn($pdo);

		   	return $mockedDB;
	   	});

	   	App::bind('Str', function($app) {
	   		return m::mock('Illuminate\Support\Str');
	   	});
	   	
	   	App::bind('config', function($app) {
	   		$mockedConfig = m::Mock('Illuminate\Config\Repository');
	   		$mockedConfig->shouldReceive('get')->once()->with('fixture-l4::config');

	   		return $mockedConfig;
	   	});
        
        $instance = Fixture::getFacadeRoot();

        $this->assertInstanceOf('Codesleeve\Fixture\Fixture', $instance);
    }
}