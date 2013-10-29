<?php namespace Codesleeve\FixtureL4\Repositories;

interface RepositoryInterface {

	/**
	 * Build a fixture record using the passed in values.
	 * 
	 * @param  Model $model        
	 * @param  string $recordName   
	 * @param  mixed $recordValues 
	 * @return Model             
	 */
	public function buildRecord($model, $recordName, $recordValues);

	/**
	 * Truncate a table.
	 * 
	 * @param  string $table 
	 * @return void           
	 */
	public function truncate($table);
}