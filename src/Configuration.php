<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


final class Configuration
{
	private ?Storage $storage = null;


	/**
	 * @return string[]
	 */
	public function loadAll(): array
	{
		return $this->getStorage()->loadAll();
	}


	public function get(string $key, ?string $namespace = null): ?string
	{
		return $this->getStorage()->get(Helpers::formatKey($key, $namespace));
	}


	/**
	 * Find multiple of keys directly with better loading performance.
	 *
	 * @param string[] $keys in format (finalKey => findKey) or (numeric => findKey)
	 * @return string[]|null[]
	 */
	public function getMultiple(array $keys, ?string $namespace = null): array
	{
		$keyMap = [];
		foreach ($keys as $keyReturn => $keyFind) {
			$keyMap[Helpers::formatKey($keyFind, $namespace)] = \is_int($keyReturn) ? $keyFind : $keyReturn;
		}

		$return = [];
		$multiple = $this->getStorage()->getMultiple(array_keys($keyMap));
		foreach ($keyMap as $realKey => $expectedKey) {
			$return[$expectedKey] = $multiple[$realKey] ?? null;
		}

		return $return;
	}


	public function save(string $key, ?string $value, ?string $namespace = null): void
	{
		if ($value === null) {
			$this->getStorage()->remove(Helpers::formatKey($key, $namespace));
		} else {
			if (($length = mb_strlen($value, 'UTF-8')) > 512) {
				throw new \RuntimeException('Maximal value length is 512 characters, but ' . $length . ' given.');
			}

			$this->getStorage()->save(Helpers::formatKey($key, $namespace), $value);
		}
	}


	public function remove(string $key, ?string $namespace = null): void
	{
		$this->getStorage()->remove(Helpers::formatKey($key, $namespace));
	}


	public function increment(string $key, int $count = 1, ?string $namespace = null): void
	{
		if (Helpers::isNumeric($value = (string) $this->get($key, $namespace)) === false) {
			throw new \RuntimeException('Constant "' . Helpers::formatKey($key, $namespace) . '" should be numeric, but value "' . $value . '" given.');
		}

		$this->save($key, (string) (((int) $value) + $count), $namespace);
	}


	public function getStorage(): Storage
	{
		if ($this->storage === null) {
			throw new \RuntimeException('Configuration storage does not exist. Did you defined "' . DynamicConfigurationExtension::class . '" extension?');
		}

		return $this->storage;
	}


	public function setStorage(Storage $storage): void
	{
		$this->storage = $storage;
	}
}
