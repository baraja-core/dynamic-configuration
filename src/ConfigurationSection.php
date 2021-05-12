<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


final class ConfigurationSection
{
	public function __construct(
		private Configuration $configuration,
		private string $namespace,
	) {
	}


	public function get(string $key): ?string
	{
		return $this->configuration->get($key, $this->namespace);
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
	public function getMultiple(array $keys): array
	{
		return $this->configuration->getMultiple($keys, $this->namespace);
	}


	public function save(string $key, ?string $value): void
	{
		$this->configuration->save($key, $value, $this->namespace);
	}


	public function remove(string $key): void
	{
		$this->configuration->remove($key, $this->namespace);
	}


	public function increment(string $key, int $count = 1): void
	{
		$this->configuration->increment($key, $count, $this->namespace);
	}
}
