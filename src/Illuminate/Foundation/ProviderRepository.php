<?php namespace Illuminate\Foundation;

use Illuminate\Filesystem;

class ProviderRepository {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Create a new service repository instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Register the application service providers.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  array  $providers
	 * @return void
	 */
	public function load(Application $app, array $providers)
	{
		// First we will load the service manifest, which contains information on all
		// service providers registered with the application and which services it
		// provides. This is used to know which services are "deferred" loaders.
		$manifest = $this->loadManifest($app);

		if ($this->shouldRecompile($manifest, $providers))
		{
			$manifest = $this->compileManifest($app, $providers);	
		}

		// We will go ahead and register all of the eagerly loaded providers with the
		// application so their services can be registered with the application as
		// a provided service. Then we will set the deferred service list on it.
		foreach ($manifest['eager'] as $provider)
		{
			$app->register($this->createProvider($app, $provider));
		}

		$app->setDeferredServices($manifest['deferred']);
	}

	/**
	 * Compile the application manifest file.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  array  $providers
	 * @return array
	 */
	protected function compileManifest(Application $app, $providers)
	{
		// The service manifest should contain a list of all of the providers for
		// the application so we can compare it on each request to the service
		// and determine if the manifest should be recompiled or is current.
		$manifest = $this->freshManifest($providers);

		foreach ($providers as $provider)
		{
			$instance = $this->createProvider($app, $provider);

			// When recompiling the service manifest, we will spin through each of the
			// providers and check if it's a deferred provider or not. If so we'll
			// add it's provided services to the manifest and note the provider.
			if ($instance->isDeferred())
			{
				foreach ($instance->provides() as $service)
				{
					$manifest['deferred'][$service] = $provider;
				}
			}

			// If the service providers are not deferred, we will simply add it to an
			// of eagerly loaded providers that will be registered with the app on
			// each request to the applications instead of being lazy loaded in.
			else
			{
				$manifest['eager'][] = $provider;
			}
		}

		return $this->writeManifest($app, $manifest);
	}

	/**
	 * Create a new provider instance.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  string  $provider
	 * @return Illuminate\Support\ServiceProvider
	 */
	public function createProvider(Application $app, $provider)
	{
		return new $provider($app);
	}

	/**
	 * Determine if the manifest should be compiled.
	 *
	 * @param  array  $manifest
	 * @param  array  $providers
	 * @return bool
	 */
	public function shouldRecompile($manifest, $providers)
	{
		return is_null($manifest) or $manifest['providers'] != $providers;
	}

	/**
	 * Load the service provider manifest JSON file.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return array
	 */
	public function loadManifest(Application $app)
	{
		$path = $this->manifestPath($app);

		// The service manifest is a file containing a JSON representation of every
		// service provided by the application and whether its provider is using
		// deferred loading or should be eagerly loaded on each request to us.
		if ($this->files->exists($path))
		{
			return json_decode($this->files->get($path), true);
		}
	}

	/**
	 * Write the service manifest file to disk.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  array  $manifest
	 * @return array
	 */
	public function writeManifest(Application $app, $manifest)
	{
		$this->files->put($this->manifestPath($app), json_encode($manifest));

		return $manifest;
	}

	/**
	 * Get the manifest file path.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return string
	 */
	protected function manifestPath($app)
	{
		return $app['path'].'/storage/meta/services.json';
	}

	/**
	 * Create a fresh manifest array.
	 *
	 * @param  array  $providers
	 * @return array
	 */
	protected function freshManifest(array $providers)
	{
		list($eager, $deferred) = array(array(), array());

		return compact('providers', 'eager', 'deferred');
	}

	/**
	 * Get the filesystem instance.
	 *
	 * @return Illuminate\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

}