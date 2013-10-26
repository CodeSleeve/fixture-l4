<?php namespace Codesleeve\Fixture;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


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
		$this->fixturesLocation = $this->config['location'];

		if (!is_dir($this->fixturesLocation)) {
			throw new Exceptions\InvalidFixtureLocationException("Could not find fixtures folder, please make sure $this->fixturesLocation exists", 1);
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
			$this->db->table($table)->truncate();
		}

		$this->tables = [];
	}

    /**
     * Handle dynamic method calls to this class.
     * This allows us to return fixture objects via method invocation.
     * 
     * @param  string $name      
     * @param  array $arguments 
     * @return mixed            
     */
    public function __call($name, $arguments)
    {
        $fixture = array_key_exists($name, $this->fixtures) ? $this->fixtures[$name] : null;

        if ($arguments && array_key_exists($arguments[0], $fixture)) 
        {
        	return $fixture[$arguments[0]];
        }

        return $fixture;
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
				$this->db->table($joinTable)->insert([$foreignKeyName => $foreignKeyValue, $otherKeyName => $otherKeyValue]);
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
		return $this->str->singular(str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))));
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