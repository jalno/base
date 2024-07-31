<?php
namespace packages\base;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use packages\base\Console\Commands\RunCommand;
use packages\base\Routing\ControllerDispatcher;

class ServiceProvider extends SupportServiceProvider
{
	public function register(): void
	{

		$this->app->singleton(PackagesContainer::class);
		$this->app->singleton(OptionsHandler::class);
		$this->app->bind(ControllerDispatcherContract::class, ControllerDispatcher::class);
		$this->app->make(Kernel::class)->prependMiddleware(Http\Middlewares\SetHttp::class);

		Options::loadFromFile();
		$this->connectToDatabaseIfPossible();
		Options::loadFromDatabase();

		Packages::registerFromComposer();
		Packages::loadDynamicStorages();

		$this->commands([
			RunCommand::class,
		]);
	}


	public static function connectToDatabaseIfPossible(): void
	{
		$config = Options::get('packages.base.loader.db');
		if (!$config) {
			return;
		}
		if (!isset($config['default'])) {
			$config = [
				'default' => $config,
			];
		}
		foreach ($config as $name => $config) {
			if (!isset($config['port']) or !$config['port']) {
				$config['port'] = 3306;
			}
			if (!isset($config['host'], $config['user'], $config['pass'], $config['dbname'])) {
				throw new DatabaseConfigException("{$name} connection is invalid");
			}
			DB::connect($name, $config['host'], $config['user'], $config['dbname'], $config['pass'], $config['port']);
		}
	}
}
