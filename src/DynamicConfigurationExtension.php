<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Extensions\ParametersExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class DynamicConfigurationExtension extends CompilerExtension
{
	private const DOCTRINE_STORAGE_CLASS = '\Baraja\DoctrineConfiguration\DoctrineStorage';


	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'dataDir' => Expect::string()->default('baraja.json-storage'),
			'dataDirPath' => Expect::string(),
			'storage' => Expect::string(),
		])->castTo('array');
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var mixed[] $config */
		$config = $this->getConfig();

		if (isset($config['storage']) === true) {
			if (\class_exists($config['storage']) === false) {
				throw new \RuntimeException('Configuration storage class "' . $config['storage'] . '" does not exist.');
			}
			$storageService = $config['storage'];
		} else {
			$storageService = $this->resolveDefaultStorage($builder);
		}

		$builder->addDefinition('baraja.configuration')
			->setFactory(Configuration::class)
			->addSetup('?->setStorage(?)', ['@self', '@' . $storageService]);
	}


	private function resolveDefaultStorage(ContainerBuilder $builder): string
	{
		/** @var mixed[] $config */
		$config = $this->getConfig();

		if (\class_exists(self::DOCTRINE_STORAGE_CLASS) === true) { // try find recommended official Doctrine storage
			$builder->addDefinition('baraja.jsonStorage')
				->setFactory(self::DOCTRINE_STORAGE_CLASS)
				->setAutowired(self::DOCTRINE_STORAGE_CLASS);

			return self::DOCTRINE_STORAGE_CLASS;
		}

		// Define fallback json storage with writing to filesystem
		if (isset($config['dataDirPath']) === true) {
			$dataDir = $config['dataDirPath'];
		} else {
			/** @var CompilerExtension[] $params */
			$params = $this->compiler->getExtensions(ParametersExtension::class);
			if (isset($params['parameters']) === true) {
				if (\is_dir($appDir = $params['parameters']->getConfig()['appDir'] ?? '') === false) {
					throw new \RuntimeException('Configuration parameter "appDir" does not exist. Did you install Nette correctly?');
				}
				$dataDir = $appDir . '/../data/' . $config['dataDir'];
			} else {
				throw new \RuntimeException('DI parameters are not available now. Did you install Nette correctly?');
			}
		}

		$builder->addDefinition('baraja.jsonStorage')
			->setFactory(JsonStorage::class)
			->setAutowired(JsonStorage::class)
			->setArgument('storageDir', $dataDir);

		return JsonStorage::class;
	}
}
