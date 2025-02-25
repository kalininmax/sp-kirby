<?php

namespace Kirby\Cms;

use Closure;
use Kirby\Data\Data;
use Kirby\Filesystem\F;

/**
 * The Loader class is an internal loader for
 * core parts, like areas, components, sections, etc.
 *
 * It's exposed in the `$kirby->load()` and the
 * `$kirby->core()->load()` methods.
 *
 * With `$kirby->load()` you get access to core parts
 * that might be overwritten by plugins.
 *
 * With `$kirby->core()->load()` you get access to
 * untouched core parts. This is useful if you want to
 * reuse or fall back to core features in your plugins.
 *
 * @package   Kirby Cms
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
class Loader
{
	public function __construct(
		protected App $kirby,
		protected bool $withPlugins = true
	) {
	}

	/**
	 * Loads the area definition
	 */
	public function area(string $name): array|null
	{
		return $this->areas()[$name] ?? null;
	}

	/**
	 * Loads all areas and makes sure that plugins
	 * are injected properly
	 */
	public function areas(): array
	{
		$areas      = [];
		$extensions = match ($this->withPlugins) {
			true  => $this->kirby->extensions('areas'),
			false => []
		};

		// load core areas and extend them with elements
		// from plugins if they exist
		foreach ($this->kirby->core()->areas() as $id => $area) {
			$area = $this->resolveArea($area);

			if (isset($extensions[$id]) === true) {
				foreach ($extensions[$id] as $areaExtension) {
					$extension = $this->resolveArea($areaExtension);
					$area      = array_replace_recursive($area, $extension);
				}

				unset($extensions[$id]);
			}

			$areas[$id] = $area;
		}

		// add additional areas from plugins
		foreach ($extensions as $id => $areaExtensions) {
			foreach ($areaExtensions as $areaExtension) {
				$areas[$id] = $this->resolve($areaExtension);
			}
		}

		return $areas;
	}

	/**
	 * Loads a core component closure
	 */
	public function component(string $name): Closure|null
	{
		return $this->extension('components', $name);
	}

	/**
	 * Loads all core component closures
	 */
	public function components(): array
	{
		return $this->extensions('components');
	}

	/**
	 * Loads a particular extension
	 */
	public function extension(string $type, string $name): mixed
	{
		return $this->extensions($type)[$name] ?? null;
	}

	/**
	 * Loads all defined extensions
	 */
	public function extensions(string $type): array
	{
		return match ($this->withPlugins) {
			true  => $this->kirby->extensions($type),
			false => $this->kirby->core()->$type()
		};
	}

	/**
	 * The resolver takes a string, array or closure.
	 *
	 * 1.) a string is supposed to be a path to an existing file.
	 * The file will either be included when it's a PHP file and
	 * the array contents will be read. Or it will be parsed with
	 * the Data class to read yml or json data into an array
	 *
	 * 2.) arrays are untouched and returned
	 *
	 * 3.) closures will be called and the Kirby instance will be
	 * passed as first argument
	 */
	public function resolve(mixed $item): mixed
	{
		if (is_string($item) === true) {
			$item = match (F::extension($item)) {
				'php'   => F::load($item, allowOutput: false),
				default => Data::read($item)
			};
		}

		if (is_callable($item) === true) {
			$item = $item($this->kirby);
		}

		return $item;
	}

	/**
	 * Calls `static::resolve()` on all items
	 * in the given array
	 */
	public function resolveAll(array $items): array
	{
		$result = [];

		foreach ($items as $key => $value) {
			$result[$key] = $this->resolve($value);
		}

		return $result;
	}

	/**
	 * Areas need a bit of special treatment
	 * when they are being loaded
	 */
	public function resolveArea(string|array|Closure $area): array
	{
		$area = $this->resolve($area);

		// convert closure dropdowns to an array definition
		// otherwise they cannot be merged properly later
		foreach ($area['dropdowns'] ?? [] as $key => $dropdown) {
			if ($dropdown instanceof Closure) {
				$area['dropdowns'][$key] = [
					'options' => $dropdown
				];
			}
		}

		return $area;
	}

	/**
	 * Loads a particular section definition
	 */
	public function section(string $name): array|null
	{
		return $this->resolve($this->extension('sections', $name));
	}

	/**
	 * Loads all section defintions
	 */
	public function sections(): array
	{
		return $this->resolveAll($this->extensions('sections'));
	}

	/**
	 * Returns the status flag, which shows
	 * if plugins are loaded as well.
	 */
	public function withPlugins(): bool
	{
		return $this->withPlugins;
	}
}
