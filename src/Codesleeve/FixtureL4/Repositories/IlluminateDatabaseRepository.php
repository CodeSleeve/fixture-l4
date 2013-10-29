<?php namespace Codesleeve\FixtureL4\Repositories;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IlluminateDatabaseRepository implements RepositoryInterface
{
	/**
     * An instance of Laravel's DatabaseManager class.
     * 
     * @var DatabaseManager
     */
    protected $db;

	/**
	 * Constructor method
	 *
	 * @param  $db 
	 */
	public function __construct(DatabaseManager $db)
	{
		$this->db = $db;
	}

	/**
	 * Build a fixture record using the passed in values.
	 * 
	 * @param  Model $model        
	 * @param  string $recordName   
	 * @param  mixed $recordValues 
	 * @return Model             
	 */
	public function buildRecord($model, $recordName, $recordValues)
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
	 * Truncate a table.
	 * 
	 * @param  string $table 
	 * @return void           
	 */
	public function truncate($table)
	{
		$this->db->table($table)->truncate();
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
			$this->insertBelongsTo($record, $relation, $columnValue);

			return;
		}

		if ($relation instanceof BelongsToMany) 
		{
			$this->insertBelongsToMany($recordName, $relation, $columnValue);

			return;
		}
	}

	/**
	 * Insert a belongsTo foreign key relationship.
	 * 
	 * @param  Model $record      
	 * @param  Relation $relation    
	 * @param  int $columnValue 
	 * @return void              
	 */
	protected function insertBelongsTo($record, $relation, $columnValue)
	{
		$foreignKeyName = $relation->getForeignKey();
		$foreignKeyValue = $this->generateKey($columnValue);
		$record->$foreignKeyName = $foreignKeyValue;
	}

	/**
	 * Insert a belongsToMany foreign key relationship.
	 *
	 * @param  string recordName
	 * @param  Relation $relation    
	 * @param  int $columnValue 
	 * @return void              
	 */
	public function insertBelongsToMany($recordName, $relation, $columnValue)
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