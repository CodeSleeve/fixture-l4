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

		$fixture = Fixture::getInstance();
		$fixture->setRepository($mockedRepository);
		$fixture->up();
	}

	/**
	 * Test that the up method will populate all fixtures when called
	 * with an empty parameter list.
	 * 
	 * @return void
	 */
	public function testUpPopulatesAllFixtures()
	{
		$mockedRepository = m::mock('Codesleeve\FixtureL4\Repositories\IlluminateDatabaseRepository');
		$mockedRepository->shouldReceive('buildRecord')
			->once()
			->with('users', 'Travis', ['first_name' => 'Travis', 'last_name'  => 'Bennett','roles' => 'endUser, root'])
			->andReturn('foo');

		$mockedRepository->shouldReceive('buildRecord')
			->once()
			->with('games', 'Diablo3', ['title' => 'Diablo 3', 'user' => 'Travis'])
			->andReturn('bar');

		$mockedRepository->shouldReceive('buildRecord')
			->once()
			->with('roles', 'root', ['name' => 'root'])
			->andReturn('baz');

		$fixture = Fixture::getInstance();
		$fixture->setRepository($mockedRepository);
		$fixture->setConfig(['location' => dirname(__FILE__) . '/fixtures']);
		$fixture->up();

		$this->assertEquals('foo', $fixture->users('Travis'));
		$this->assertEquals('bar', $fixture->games('Diablo3'));
		$this->assertEquals('baz', $fixture->roles('root'));
	}

	/**
	 * Test that the up method will only populate fixtures that 
	 * are supplied to it via parameters.
	 * 
	 * @return void
	 */
	public function testUpPopulatesSomeFixtures()
	{
		$mockedRepository = m::mock('Codesleeve\FixtureL4\Repositories\IlluminateDatabaseRepository');
		$mockedRepository->shouldReceive('buildRecord')
			->once()
			->with('users', 'Travis', ['first_name' => 'Travis', 'last_name'  => 'Bennett','roles' => 'endUser, root'])
			->andReturn('foo');

		$mockedRepository->shouldReceive('buildRecord')
			->never()
			->with('games', 'Diablo3', ['title' => 'Diablo 3', 'user' => 'Travis']);

		$mockedRepository->shouldReceive('buildRecord')
			->never()
			->with('roles', 'root', ['name' => 'root']);

		$fixture = Fixture::getInstance();
		$fixture->setRepository($mockedRepository);
		$fixture->setConfig(['location' => dirname(__FILE__) . '/fixtures']);
		$fixture->up(['users']);

		$this->assertEquals('foo', $fixture->users('Travis'));
	}
}
