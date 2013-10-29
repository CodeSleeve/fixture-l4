<?php namespace Codesleeve\FixtureL4\Facades;

use Illuminate\Support\Facades\Facade;

class Fixture extends Facade
{
	/**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'fixture'; }
}