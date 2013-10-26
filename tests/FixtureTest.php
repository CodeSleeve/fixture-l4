<?php  

use Codesleeve\Fixture\Fixture;
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
	 * @expectedException Codesleeve\Fixture\Exceptions\InvalidFixtureLocationException
	 * @return void
	 */
	public function testUpThrowsAnException()
	{
	    $mockedDB = m::mock('Illuminate\Database\DatabaseManager');
		$mockedStr = m::mock('Illuminate\Support\Str');

		$fixture = Fixture::getInstance($mockedDB, $mockedStr, ['location' => '']);
		$fixture->up();
	}
}
