<?php namespace Codesleeve\Fixture\Facades;

use Illuminate\Support\Facades\Facade;

class ClassName extends Facade
{
	/**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'fixture'; }
}