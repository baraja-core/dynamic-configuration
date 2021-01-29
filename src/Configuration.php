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
	 * The searched keys must not be duplicated in relation to each other or to the alias.
	 * You can use the real key (findKey) to search or rename the key to your own alias (finalKey).
	 * The namespace is used for all keys, you cannot search for more than one namespace at a time.
	 *
	 * @param string[] $keys in format (finalKey => findKey) or (numeric => findKey)
	 * @return string[]|null[]
	 */
	public function getMultiple(array $keys, ?string $namespace = null): array
	{
		$keyMap = [];
		foreach ($keys as $keyReturn => $keyFind) {
			if (isset($keyMap[$realKey = Helpers::formatKey($keyFind, $namespace)]) === true) {
				throw new \InvalidArgumentException(
					'Key "' . $realKey . '" already exist in key map, because "' . $keyFind . '"'
					. ' (or alias "' . $keyReturn . '") is duplicated.',
				);
			}
			$keyMap[$realKey] = \is_int($keyReturn) ? $keyFind : $keyReturn;
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
		$formattedKey = Helpers::formatKey($key, $namespace);
		$storage = $this->getStorage();
		if ($value === null) {
			$storage->remove($formattedKey);
		} else {
			if (($length = mb_strlen($value, 'UTF-8')) > 512) {
				throw new \RuntimeException('Maximal value length is 512 characters, but ' . $length . ' given.');
			}
			if ($storage->get($formattedKey) !== $value) { // Save only if the value has changed.
				$storage->save($formattedKey, $value);
			}
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
