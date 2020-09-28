<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


interface Storage
{
	/**
	 * Return list of all constants as simple array: key => value.
	 *
	 * @return string[]
	 */
	public function loadAll(): array;

	public function get(string $key): ?string;

	/**
	 * @param string[] $keys
	 * @return string[]|null[]
	 */
	public function getMultiple(array $keys): array;

	public function save(string $key, string $value): void;

	public function remove(string $key): void;
}
