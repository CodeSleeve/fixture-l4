<?php namespace Codesleeve\Fixture;

use Config, Str;

class Fixture extends Singleton implements \Arrayaccess
{
	/**
	 * An array of eloquent collections (one for each loaded fixture).
	 * 
	 * @var array
	 */
	protected $fixtures;

	/**
	 * An array of tables that have had fixture data loaded into them.
	 * 
	 * @var array
	 */
	protected $tables;

	/**
	 * The filesystem location where the fixture files are stored.
	 * 
	 * @var string
	 */
	protected $fixturesLocation;

	/**
	 * Build fixtures.
	 * 
	 * @param  array $fixtures 
	 * @return void
	 */
	public function up($fixtures = [])
	{
		$this->fixturesLocation = Config::get('fixture::location');

		if (!is_dir($this->fixturesLocation)) {
			throw new Exception("Could not find fixtures folder, please make sure $this->fixturesLocation exists", 1);
			
		}

		$this->loadFixtures($fixtures);
	}

	/**
	 * Destroy fixtures.
	 * 
	 * @return void          
	 */
	public function down()
	{
		foreach ($this->tables as $table) {
			\DB::table($table)->truncate();
		}

		$this->tables = [];
	}

	/**
	 * Array access method for setting a fixture.
	 * 
	 * @param  string $offset 
	 * @param  mixed $value  
	 * @return void         
	 */
	public function offsetSet($offset, $value) 
	{
        if (is_null($offset)) {
            $this->fixtures[] = $value;
        } 
        else {
            $this->fixtures[$offset] = $value;
        }
    }

    /**
     * Array access method for determining if a fixture exists.
     * 
     * @param  string $offset 
     * @return void boolean      
     */
    public function offsetExists($offset) 
    {
        return isset($this->fixtures[$offset]);
    }

    /**
     * Array access method for unsetting a fixture.
     * 	
     * @param  string $offset 
     * @return void
     */
    public function offsetUnset($offset) 
    {
        unset($this->fixtures[$offset]);
    }

    /**
     * Array access method for returning a fixture.
     * 
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset) 
    {
        return array_key_exists($offset, $this->fixtures) ? $this->fixtures[$offset] : null;
    }

    /**
	 * Magic method for setting a fixture.
	 * 
	 * @param  string $offset 
	 * @param  mixed $value  
	 * @return void         
	 */
    public function __set($offset, $value)
    {
        $this->fixtures[$offset] = $value;
    }

    /**
     * Magic method for returning a fixture.
     * 
     * @param string $offset
     * @return mixed
     */
    public function __get($offset)
    {
        return array_key_exists($offset, $this->fixtures) ? $this->fixtures[$offset] : null;
    }

	/**
	 * Load fixtures.
	 *
	 * @param  array $fixtures
	 * @return void 
	 */
	protected function loadFixtures($fixtures)
	{
		if ($fixtures) {
			$this->loadSomeFixtures($fixtures);
		}

		$this->loadAllFixtures();
	}

	/**
	 * Load all fixtures from the fixture location.
	 * 
	 * @return void
	 */
	protected function loadAllFixtures()
	{
		$fixtures = glob("$this->fixturesLocation/*.php");

		foreach ($fixtures as $fixture) {
		    $this->loadFixture($fixture);
		}
	}

	/**
	 * Load a only a subset of fixtures from the fixtures folder.
	 * 
	 * @param  array $selectedFixtures 
	 * @return void           
	 */
	protected function loadSomeFixtures($selectedFixtures)
	{
		$fixtures = glob("$this->fixturesLocation/*.php");

		foreach ($fixtures as $fixture) 
		{
		    $tableName = basename($fixture, '.php');

		    if (in_array($tableName, $selectedFixtures)) {
		    	$this->loadFixture($fixture);
		    }
		}
	}

	/**
	 * Load fixture data into the database.
	 * We'll also store it inside the fixtures property for easy
	 * access as an array element or class property from our tests.
	 * 
	 * @param  array $fixture 
	 * @return void
	 */
	protected function loadFixture($fixture)
	{
		$tableName = basename($fixture, '.php');
		$this->tables[] = $tableName;
		$records = include $fixture;
		
		foreach ($records as $recordName => $recordValues) 
		{
			$model = $this->generateModelName($tableName);
			$record = $model::create($recordValues);
			$this->fixtures[$tableName][$recordName] = $record;
		}
	}

	/**
	 * Generate the name of table's corresponding model.
	 * 
	 * @param  string $tableName 
	 * @return string
	 */
	protected function generateModelName($tableName)
	{
		return Str::singular(str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))));
	}

}