<?php namespace Codesleeve\FixtureL4\Repositories;

interface RepositoryInterface {

	/**
	 * Build a fixture record using the passed in values.
	 *
	 * @param  string $tableName
	 * @param  string $recordName   
	 * @param  mixed $recordValues 
	 * @return Model             
	 */
	public function buildRecord($tableName, $recordName, $recordValues);

	/**
	 * Truncate a table.
	 * 
	 * @param  string $tableName 
	 * @return void           
	 */
	public function truncate($tableName);
}