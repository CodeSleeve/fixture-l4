<?php namespace Codesleeve\Fixture;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use DB, Config, Str;

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
			DB::table($table)->truncate();
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
		if ($fixtures) 
		{
			$this->loadSomeFixtures($fixtures);

			return;
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
	 * Load a fixture's data into the database.
	 * We'll also store it inside the fixtures property for easy
	 * access as an array element or class property from our tests.
	 * 
	 * @param  array $fixture 
	 * @return void
	 */
	protected function loadFixture($fixture)
	{
		$tableName = basename($fixture, '.php');
		$model = $this->generateModelName($tableName);
		$this->tables[] = $tableName;
		$records = include $fixture;
		
		foreach ($records as $recordName => $recordValues) 
		{
			$record = $this->buildRecord($model, $recordName, $recordValues);
			$this->fixtures[$tableName][$recordName] = $record;
		}
	}

	/**
	 * Build a fixture record using the passed in values.
	 * 
	 * @param  Model $model        
	 * @param  string $recordName   
	 * @param  mixed $recordValues 
	 * @return Model             
	 */
	protected function buildRecord($model, $recordName, $recordValues)
	{
		$record = new $model;

		foreach ($recordValues as $columnName => $columnValue) 
		{
			$camelKey = camel_case($columnName);

			// If a column name exists as a method on the model, we will just assume
		    // it is a relationship and we'll generate the primary key for it and store 
			// it as a foreign key on the model.
			if (method_exists($record, $camelKey))
			{
				$this->insertRelatedRecords($recordName, $record, $camelKey, $columnValue);

				continue;
			}
			
			$record->$columnName = $columnValue;
		}

		// Generate a hash for this record's primary key.  We'll simply hash the name of the 
		// fixture into an integer value so that related fixtures don't have to rely on
		// an auto-incremented primary key when creating foreign keys.
		$primaryKeyName = $record->getKeyName(); 
		$record->$primaryKeyName = $this->generateKey($recordName);
		$record->save();

		return $record;
	}

	/**
	 * Insert related records for a fixture.
	 *
	 * @param  string $recordName
	 * @param  Model $record      
	 * @param  string $camelKey    
	 * @param  string $columnValue 
	 * @return void              
	 */
	protected function insertRelatedRecords($recordName, $record, $camelKey, $columnValue)
	{
		$relation = $record->$camelKey();
		
		if ($relation instanceof BelongsTo) 
		{
			$foreignKeyName = $relation->getForeignKey();
			$foreignKeyValue = $this->generateKey($columnValue);
			$record->$foreignKeyName = $foreignKeyValue;

			return;
		}

		if ($relation instanceof BelongsToMany) 
		{
			$joinTable = $relation->getTable();
			$this->tables[] = $joinTable;
			$relatedRecords = explode(',', str_replace(', ', ',', $columnValue));
			$foreignKeyName = $relation->getForeignKey();
			$otherKeyName = $relation->getOtherKey();
			$foreignKeyValue = $this->generateKey($recordName);

			foreach ($relatedRecords as $relatedRecord) 
			{
				$otherKeyValue = $this->generateKey($relatedRecord);
				DB::table($joinTable)->insert([$foreignKeyName => $foreignKeyValue, $otherKeyName => $otherKeyValue]);
			}

			return;
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

	/**
	 * Generate an integer hash of a string.
	 * We'll use this method to convert a fixture's name into the
	 * primary key of it's corresponding database table record.
	 * 
	 * @param  string $value - This should be the name of the fixture.
	 * @return integer      
	 */
	protected function generateKey($value)
	{
		$hash = sha1($value);
		$integerHash = base_convert($hash, 16, 10);
		
		return (int)substr($integerHash, 0, 10);
	}
}