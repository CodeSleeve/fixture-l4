<?php namespace Codesleeve\Fixture;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;

class Singleton
{
    /**
     * An instance of the laravel application container.
     * This is a full Illuminate\Foundation\Application instance, which inherits from the Container class.
     * We can resolve all of our dependencies from it just as we would using the App facade,  
     * however, we can still use the App facade if preferred.
     * 
     * @var Application
     */
    protected $app;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @staticvar Singleton $instance The *Singleton* instances of this class.
     *
     * @param mixed $app
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance($app = null)
    {
        static $instance = null;
        
        if (null === $instance) {
            $instance = new static($app);
        }

        return $instance;
    }

    /**
     * This method provides a way for us to inject app instances
     * into an already instantiated instance of this singleton.
     * 
     * @param Application $app
     */
    public function setApp($app)
    {
        $this->app = $app;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     *
     * @param mixed $app
     * @return void
     */
    protected function __construct(Application $app = null)
    {
        $this->app = $app ?: App::make('app');
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