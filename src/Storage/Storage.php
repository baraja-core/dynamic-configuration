<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


interface Storage
{
	/**
	 * Return list of all constants as simple array: key => value.
	 *
	 * @return array<string, string>
	 */
	public function loadAll(): array;

	public function get(string $key): ?string;

	/**
	 * @param array<int, string> $keys
	 * @return array<string, string|null>
	 */
	public function getMultiple(array $keys): array;

	public function save(string $key, string $value): void;

	public function remove(string $key): void;
}
