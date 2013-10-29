<?php  

use Codesleeve\FixtureL4\Fixture;
use \Mockery as m;

class FixtureTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
		
    }

    public function tearDown()
    {
        m::close();
    }

    /**
	 * Test that the up method thows an invalid fixture location exception
	 * for fixture locations that don't exist.
	 *
	 * @expectedException Codesleeve\FixtureL4\Exceptions\InvalidFixtureLocationException
	 * @return void
	 */
	public function testUpThrowsAnException()
	{
	    $mockedRepository = m::mock('Codesleeve\FixtureL4\Repositories\IlluminateDatabaseRepository');
		$mockedStr = m::mock('Illuminate\Support\Str');

		$fixture = Fixture::getInstance();
		$fixture->setRepository($mockedRepository);
		$fixture->setStr($mockedStr);
		$fixture->up();
	}

	public function testUpPopulatesFixtures()
	{
		$mockedRepository = m::mock('Codesleeve\FixtureL4\Repositories\IlluminateDatabaseRepository');
		$mockedRepository->shouldReceive('buildRecord')->times(6);
		
		$mockedStr = m::mock('Illuminate\Support\Str');
		$mockedStr->shouldReceive('singular')
			->once()
			->with('Users')
			->andReturn('User');

		$mockedStr->shouldReceive('singular')
			->once()
			->with('Roles')
			->andReturn('Role');

		$mockedStr->shouldReceive('singular')
			->once()
			->with('Games')
			->andReturn('Game');

		$fixture = Fixture::getInstance();
		$fixture->setRepository($mockedRepository);
		$fixture->setStr($mockedStr);
		$fixture->setConfig(['location' => dirname(__FILE__) . '/fixtures']);
		$fixture->up();

		/*$this->assertEquals('Travis', $fixture->users('Travis')->first_name);
		$this->assertEquals('Diablo 3', $fixture->users('Travis')->games[0]->title);
		$this->assertEquals('Skyrim', $fixture->users('Travis')->games[1]->title);
		$this->assertTrue($fixture->users('Travis')->hasRole('root'));*/
	}
}
