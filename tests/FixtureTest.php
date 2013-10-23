<?php  

use Codesleeve\Fixture\Fixture;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;
use \Mockery as m;

class FixtureTest extends PHPUnit_Framework_TestCase
{
    /**
     * Because this is a laravel specific package, we're going to be making extensive use
     * of the application container.  As such, in our setup method we're going to go ahead
     * and build up an application container facade with all the bindings, etc that this
     * package uses.
     */
    public function setUp()
    {
		$app = new Application;
		$app->instance('app', $app);
		$app->register('Codesleeve\Fixture\FixtureServiceProvider');
		Illuminate\Support\Facades\Facade::setFacadeApplication($app);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
	 * A basic functional test example.
	 *
	 * @expectedException Codesleeve\Fixture\Exceptions\InvalidFixtureLocationException
	 * @return void
	 */
	public function testUpThrowsAnException()
	{
		App::bind('config', function($app)
		{
		    $mockedConfig = m::mock();
			$mockedConfig->shouldReceive('get')
				->with('fixture::location')
				->once()
				->andReturn('');

			return $mockedConfig;
		});

		$fixture = Fixture::getInstance();
		$fixture->setApp(App::make('app'));
		$fixture->up();
	}
}
