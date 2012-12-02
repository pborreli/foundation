<?php

use Mockery as m;

class ProviderRepositoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testServicesAreRegisteredWhenManifestIsNotRecompiled()
	{
		$repo = m::mock('Illuminate\Foundation\ProviderRepository[createProvider,loadManifest,shouldRecompile]', array(m::mock('Illuminate\Filesystem')));
		$repo->shouldReceive('loadManifest')->once()->andReturn(array('eager' => array('foo'), 'deferred' => array('deferred')));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(false);
		$app = m::mock('Illuminate\Foundation\Application[register,setDeferredServices]');
		$provider = m::mock('Illuminate\Support\ServiceProvider');
		$repo->shouldReceive('createProvider')->once()->with($app, 'foo')->andReturn($provider);
		$app->shouldReceive('register')->once()->with($provider);
		$app->shouldReceive('setDeferredServices')->once()->with(array('deferred'));

		$repo->load($app, array());
	}


	public function testManifestIsProperlyRecompiled()
	{
		$repo = m::mock('Illuminate\Foundation\ProviderRepository[createProvider,loadManifest,writeManifest,shouldRecompile]', array(m::mock('Illuminate\Filesystem')));
		$app = m::mock('Illuminate\Foundation\Application');

		$repo->shouldReceive('loadManifest')->once()->andReturn(array('eager' => array(), 'deferred' => array('deferred')));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(true);

		// foo mock is just a deferred provider
		$repo->shouldReceive('createProvider')->once()->with($app, 'foo')->andReturn($fooMock = m::mock('StdClass'));
		$fooMock->shouldReceive('isDeferred')->once()->andReturn(true);
		$fooMock->shouldReceive('provides')->once()->andReturn(array('foo.provides1', 'foo.provides2'));

		// bar mock is added to eagers since it's not reserved
		$repo->shouldReceive('createProvider')->once()->with($app, 'bar')->andReturn($barMock = m::mock('Illuminate\Support\ServiceProvider'));
		$barMock->shouldReceive('isDeferred')->once()->andReturn(false);
		$repo->shouldReceive('writeManifest')->once()->andReturnUsing(function($app, $manifest) { return $manifest; });

		// bar mock should be registered with the application since it's eager
		$repo->shouldReceive('createProvider')->once()->with($app, 'bar')->andReturn($barMock);
		$app->shouldReceive('register')->once()->with($barMock);

		// the deferred should be set on the application
		$app->shouldReceive('setDeferredServices')->once()->with(array('foo.provides1' => 'foo', 'foo.provides2' => 'foo'));

		$manifest = $repo->load($app, array('foo', 'bar'));
	}


	public function testShouldRecompileReturnsCorrectValue()
	{
		$repo = new Illuminate\Foundation\ProviderRepository(new Illuminate\Filesystem);
		$this->assertTrue($repo->shouldRecompile(null, array()));
		$this->assertTrue($repo->shouldRecompile(array('providers' => array('foo')), array('foo', 'bar')));
		$this->assertFalse($repo->shouldRecompile(array('providers' => array('foo')), array('foo')));
	}


	public function testLoadManifestReturnsParsedJSON()
	{
		$repo = new Illuminate\Foundation\ProviderRepository($files = m::mock('Illuminate\Filesystem'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/storage/meta/services.json')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/storage/meta/services.json')->andReturn(json_encode($array = array('users' => array('dayle' => true))));
		$app = new Illuminate\Foundation\Application;
		$app['path'] = __DIR__;

		$this->assertEquals($array, $repo->loadManifest($app));
	}


	public function testWriteManifestStoresToProperLocation()
	{
		$repo = new Illuminate\Foundation\ProviderRepository($files = m::mock('Illuminate\Filesystem'));
		$files->shouldReceive('put')->once()->with(__DIR__.'/storage/meta/services.json', json_encode(array('foo')));
		$app = new Illuminate\Foundation\Application;
		$app['path'] = __DIR__;

		$result = $repo->writeManifest($app, array('foo'));

		$this->assertEquals(array('foo'), $result);
	}

}