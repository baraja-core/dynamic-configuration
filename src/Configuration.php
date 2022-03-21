<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


final class Configuration
{
	private ?Storage $storage = null;


	/**
	 * @return array<string, string>
	 */
	public function loadAll(): array
	{
		return $this->getStorage()->loadAll();
	}


	public function getSection(string $namespace): ConfigurationSection
	{
		return new ConfigurationSection($this, $namespace);
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
	 * @param array<string|int, string> $keys in format (finalKey => findKey) or (numeric => findKey)
	 * @return array<string, string|null>
	 */
	public function getMultiple(array $keys, ?string $namespace = null): array
	{
		$keyMap = [];
		foreach ($keys as $keyReturn => $keyFind) {
			$realKey = Helpers::formatKey($keyFind, $namespace);
			if (isset($keyMap[$realKey]) === true) {
				throw new \InvalidArgumentException(
					'Key "' . $realKey . '" already exist in key map, because "' . $keyFind . '"'
					. ' (or alias "' . $keyReturn . '") is duplicated.',
				);
			}
			$keyMap[$realKey] = is_int($keyReturn) ? $keyFind : $keyReturn;
		}

		$return = [];
		$multiple = $this->getStorage()->getMultiple(array_keys($keyMap));
		foreach ($keyMap as $realKey => $expectedKey) {
			$return[$expectedKey] = $multiple[$realKey] ?? null;
		}

		return $return;
	}


	/**
	 * This method finds all the required keys.
	 * If any of the values do not exist, throws an exception with a list of missing keys.
	 *
	 * @param array<string|int, string> $keys in format (finalKey => findKey) or (numeric => findKey)
	 * @return array<string, string>
	 */
	public function getMultipleMandatory(array $keys, ?string $namespace = null): array
	{
		$selection = $this->getMultiple($keys, $namespace);

		$return = [];
		$mandatory = [];
		foreach ($selection as $key => $value) {
			if ($value === null) {
				$mandatory[] = $key;
			} else {
				$return[$key] = $value;
			}
		}
		if ($mandatory !== []) {
			throw new \LogicException(
				'All mandatory keys must exist, but key' . (count($mandatory) === 1 ? '' : 's')
				. ' "' . implode('", "', $mandatory) . '" '
				. ($namespace !== null ? '(in namespace "' . $namespace . '") ' : '') . 'missing.'
				. "\n" . 'Did you check your configuration?',
			);
		}

		return $return;
	}


	public function save(string $key, ?string $value, ?string $namespace = null): void
	{
		$formattedKey = Helpers::formatKey($key, $namespace);
		$storage = $this->getStorage();
		if ($value === null) {
			$storage->remove($formattedKey);
		} elseif ($storage->get($formattedKey) !== $value) { // Save only if the value has changed.
			$storage->save($formattedKey, $value);
		}
	}


	public function remove(string $key, ?string $namespace = null): void
	{
		$this->getStorage()->remove(Helpers::formatKey($key, $namespace));
	}


	public function increment(string $key, int $count = 1, ?string $namespace = null): void
	{
		$value = (string) $this->get($key, $namespace);
		if (Helpers::isNumeric($value) === false) {
			throw new \RuntimeException(
				'Constant "' . Helpers::formatKey($key, $namespace) . '" should be numeric, '
				. 'but value "' . $value . '" given.',
			);
		}

		$this->save($key, (string) (((int) $value) + $count), $namespace);
	}


	public function getStorage(): Storage
	{
		if ($this->storage === null) {
			throw new \RuntimeException(
				'Configuration storage does not exist. '
				. 'Did you defined "' . DynamicConfigurationExtension::class . '" extension?',
			);
		}

		return $this->storage;
	}


	public function setStorage(Storage $storage): void
	{
		$this->storage = $storage;
	}
}
