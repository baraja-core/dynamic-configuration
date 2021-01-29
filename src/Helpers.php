<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


use Nette\Utils\Strings;

final class Helpers
{
	/** @throws \Error */
	public function __construct()
	{
		throw new \Error('Class ' . static::class . ' is static and cannot be instantiated.');
	}


	/**
	 * Finds whether a string is a floating point number in decimal base.
	 */
	public static function isNumeric(string $value): bool
	{
		return preg_match('#^[+-]?\d*[.]?\d+$#D', $value) !== 0;
	}


	/**
	 * Reformat given key and namespace to single string.
	 * If given key contains specific namespace, given namespace will be ignored.
	 *
	 * Cases:
	 *
	 * | Key      | Namespace | Return        |
	 * |----------|-----------|---------------|
	 * | name     | e-shop    | e-shop__name  |
	 * | token-cs | gtm       | gtm__token-cs |
	 * | a__key   | a         | a__key        |
	 * | b__key   | a         | b__key        |
	 * | c__key   | NULL      | c__key        |
	 * | name     | NULL      | name          |
	 * | name     | "null"    | name          |
	 * | name     | "false"   | name          |
	 */
	public static function formatKey(string $key, ?string $namespace): string
	{
		if (strpos($key, '__') !== false) {
			return self::validateKeyLength($key);
		}
		if ($namespace !== null && (strtolower($namespace) === 'null' || strtolower($namespace) === 'false')) {
			$namespace = null;
		}

		return self::validateKeyLength(($namespace !== null ? $namespace . '__' : '') . $key);
	}


	private static function validateKeyLength(string $key): string
	{
		if (($length = Strings::length($key)) > 128) {
			throw new \RuntimeException('Maximal key length with namespace is 128 characters, but ' . $length . ' given.');
		}

		return $key;
	}
}
