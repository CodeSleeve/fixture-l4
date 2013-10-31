<?php namespace Codesleeve\FixtureL4\Repositories;

class StandardRepository extends Repository implements RepositoryInterface
{
	/**
	 * Constructor method
	 *
	 * @param  PDO $db 
	 * @param  Str $str
	 */
	public function __construct(\PDO $db)
	{
		$this->db = $db;
	}

	/**
	 * Build a fixture record using the passed in values.
	 *
	 * @param  string $tableName
	 * @param  string $recordName   
	 * @param  mixed $recordValues 
	 * @return Model             
	 */
	public function buildRecord($tableName, $recordName, $recordValues)
	{
		// Generate a hash for this record's primary key.  We'll simply hash the name of the 
		// fixture into an integer value so that related fixtures don't have to rely on
		// an auto-incremented primary key when creating foreign keys.
		$recordValues = $this->setForeignKeys($recordValues);
		$recordValues = array_merge($recordValues, array('id' => $this->generateKey($recordName)));
		
		$fields = implode(', ', array_keys($recordValues));
		$values = array_values($recordValues);
		$placeholders = rtrim(str_repeat('?, ', count($recordValues)), ', ');
		$sql = "INSERT INTO $tableName ($fields) VALUES ($placeholders)";
		$sth = $this->db->prepare($sql);
		$sth->execute($values);

		return (object) $recordValues;
	}

	/**
	 * Truncate a table.
	 * 
	 * @param  string $tableName 
	 * @return void           
	 */
	public function truncate($tableName)
	{
		$this->db->query("TRUNCATE TABLE $tableName");
	}

	/**
	 * Loop through each of the fixture column/values.
	 * If a column ends in '_id' we're going to assume it's
	 * a foreign key and we'll hash it's values.
	 * 
	 * @param array $values 
	 */
	protected function setForeignKeys($values)
	{
		foreach ($values as $key => &$value) 
		{
			if ($this->endsWith($key, '_id')) {
				$value = $this->generateKey($value);
			}
		}

		return $values;
	}

	/**
	 * Determine if a string ends with a set of specified characters.
	 * 
	 * @param  string $haystack 
	 * @param  string $needle   
	 * @return boolean
	 */
	protected function endsWith($haystack, $needle)
	{
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}
}