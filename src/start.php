<?php

/*
|--------------------------------------------------------------------------
| Register Class Imports
|--------------------------------------------------------------------------
|
| Here we will just import a few classes that we need during the booting
| of the framework. These are mainly classes that involve loading the
| config files for this application, such as the config repository.
|
*/

use Illuminate\Filesystem;
use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\ProviderRepository;

/*
|--------------------------------------------------------------------------
| Register Application Exception Handling
|--------------------------------------------------------------------------
|
| We will go ahead and register the application exception handling here
| which will provide a great output of exception details and a stack
| trace in the case of exceptions while an application is running.
|
*/

$app->startExceptionHandling();

/*
|--------------------------------------------------------------------------
| Register The Configuration Repository
|--------------------------------------------------------------------------
|
| The configuration repository is used to lazily load in the options for
| this application from the configuration files. The files are easily
| separated by their concerns so they do not become really crowded.
|
*/

$config = new Config($app['config.loader'], $env);

$app->instance('config', $config);

/*
|--------------------------------------------------------------------------
| Set The Default Timezone
|--------------------------------------------------------------------------
|
| Here we will set the default timezone for PHP. PHP is notoriously mean
| if the timezone is not explicitly set. This will be used by each of
| the PHP date and date-time functions throughout the application.
|
*/

$config = $app['config']['app'];

date_default_timezone_set($config['timezone']);

/*
|--------------------------------------------------------------------------
| Register The Alias Loader
|--------------------------------------------------------------------------
|
| The alias loader is responsible for lazy loading the class aliases setup
| for the application. We will only register it if the "config" service
| is bound in the application since it contains the alias definitions.
|
*/

$app->registerAliasLoader($config['aliases']);

/*
|--------------------------------------------------------------------------
| Load The Illuminate Facades
|--------------------------------------------------------------------------
|
| The facades provide a terser static interface over the various parts
| of the application, allowing their methods to be accessed through
| a mixtures of magic methods and facade derivatives. It's slick.
|
*/

Illuminate\Support\Facade::setFacadeApplication($app);

/*
|--------------------------------------------------------------------------
| Register The Core Service Providers
|--------------------------------------------------------------------------
|
| The Illuminate core service providers register all of the core pieces
| of the Illuminate framework including session, caching, encryption
| and more. It's simply a convenient wrapper for the registration.
|
*/

$services = new ProviderRepository(new Filesystem);

$services->load($app, $config['providers']);

/*
|--------------------------------------------------------------------------
| Load The Application Start Script
|--------------------------------------------------------------------------
|
| The start script gives us the application the opportunity to override
| any of the existing IoC bindings, as well as register its own new
| bindings for things like repositories, etc. We'll load it here.
|
*/

$path = $appPath.'/start/global.php';

if (file_exists($path)) require $path;

/*
|--------------------------------------------------------------------------
| Load The Environment Start Script
|--------------------------------------------------------------------------
|
| The environment start script is only loaded if it exists for the app
| environment currently active, which allows some actions to happen
| in one environment while not in the other, keeping things clean.
|
*/

$path = $appPath."/start/{$env}.php";

if (file_exists($path)) require $path;

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| The Application routes are kept separate from the application starting
| just to keep the file a little cleaner. We'll go ahead and load in
| all of the routes now and return the application to the callers.
|
*/

require $appPath.'/routes.php';