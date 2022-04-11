<?php

declare(strict_types=1);

namespace Baraja\DynamicConfiguration;


use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final class JsonStorage implements Storage
{
	private const MAX_EXPIRATION_MS = 500;

	/** @var string[][] (namespace => {data}) */
	private array $cache = [];

	/** @var array<string, float> (namespace => expiration) */
	private array $cacheExpiration = [];


	public function __construct(
		private string $storageDir,
	) {
		if (\is_dir($storageDir) === false) {
			FileSystem::createDir($storageDir);
		}
	}


	public function get(string $key): ?string
	{
		$parser = $this->parseKey($key);

		return $this->loadFile($parser['namespace'])[$parser['key']] ?? null;
	}


	public function getMultiple(array $keys): array
	{
		$return = [];
		foreach ($keys as $key) {
			$return[$key] = $this->get($key);
		}

		return $return;
	}


	/**
	 * @return array<string, string>
	 * @throws JsonException
	 */
	public function loadAll(): array
	{
		$return = [];
		foreach (new \FilesystemIterator($this->storageDir) as $item) {
			/** @var \SplFileInfo $item */
			if (
				$item->getExtension() === 'json'
				&& \is_file($item->getPathname()) === true
				&& preg_match('/^(.+)\.json$/', $item->getBasename(), $parser) === 1
			) {
				foreach (Json::decode(FileSystem::read($item->getPathname()), Json::FORCE_ARRAY) as $key => $value) {
					$return[$parser[1] . '__' . $key] = $value;
				}
			}
		}

		return $return;
	}


	public function remove(string $key): void
	{
		$parser = $this->parseKey($key);
		$this->loadFile($parser['namespace'], true);
		unset($this->cache[$parser['namespace']][$parser['key']]);
		$this->flushFile($parser['namespace']);
	}


	public function save(string $key, string $value): void
	{
		$parser = $this->parseKey($key);
		$this->loadFile($parser['namespace'], true);
		$this->cache[$parser['namespace']][$parser['key']] = $value;
		$this->flushFile($parser['namespace']);
	}


	/**
	 * @return string[]
	 */
	private function loadFile(string $namespace, bool $force = false): array
	{
		if (
			isset($this->cacheExpiration[$namespace]) === true
			&& $this->cacheExpiration[$namespace] >= microtime(true)
		) {
			$force = true;
		}
		if (isset($this->cache[$namespace]) === false || $force === true) {
			if (\is_file($path = $this->storageDir . '/' . $namespace . '.json') === true) {
				try {
					$this->cacheExpiration[$namespace] = microtime(true) + (self::MAX_EXPIRATION_MS / 1_000);
					$this->cache[$namespace] = Json::decode(FileSystem::read($path), Json::FORCE_ARRAY);
				} catch (JsonException $e) {
					throw new \RuntimeException('Invalid json in storage: ' . $e->getMessage(), $e->getCode(), $e);
				}
			} else {
				FileSystem::write($path, '{}');
				$this->cache[$namespace] = [];
			}
		}

		return $this->cache[$namespace] ?? [];
	}


	private function flushFile(string $namespace): void
	{
		if (isset($this->cache[$namespace]) === false) {
			throw new \RuntimeException('Can not flush namespace "' . $namespace . '", because namespace has not been selected.');
		}
		try {
			$json = Json::encode($this->cache[$namespace], Json::PRETTY);
		} catch (JsonException $e) {
			throw new \RuntimeException('Can not serialize json: ' . $e->getMessage(), $e->getCode(), $e);
		}

		FileSystem::write($this->storageDir . '/' . $namespace . '.json', $json);
	}


	/**
	 * @return array{namespace: string, key: string}
	 */
	private function parseKey(string $key): array
	{
		if (preg_match('/^([^_]+)__(.+)$/', $key, $keyParser) === 1) {
			return [
				'namespace' => $keyParser[1],
				'key' => $keyParser[2],
			];
		}

		return [
			'namespace' => 'global',
			'key' => $key,
		];
	}
}
