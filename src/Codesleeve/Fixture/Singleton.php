<?php namespace Codesleeve\Fixture;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

class Singleton
{
    /**
     * An instance of Laravel's DatabaseManager class
     * @var DatabaseManager
     */
    protected $db;

    /**
     * An instance of Laravel's Str class
     * @var Str
     */
    protected $str;

    /**
     * An array of configuration options
     * @var Array
     */
    protected $config = [];

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @staticvar Singleton $instance The *Singleton* instances of this class.
     *
     * @param DatabaseManager $db
     * @param Str $str
     * @param array $config
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance(DatabaseManager $db, Str $str, $config = null)
    {
        static $instance = null;
        
        if (null === $instance) {
            $instance = new static($db, $str, $config);
        }

        return $instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     *
     * @param DatabaseManager $db
     * @param Str $str
     * @param array $config
     * @return void
     */
    protected function __construct(DatabaseManager $db, Str $str, $config = null)
    {
        $this->db = $db;
        $this->str = $str;

        if ($config) {
            $this->config = $config;
        }
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
}