<?php

namespace packages\base\Frontend;

use Exception;
use Illuminate\Support\Facades\Request;

class SourceContainer
{

	/**
	 * @var Source[]
	 */
	private array $sources = [];

	private ?Source $primarySource = null;

	/**
	 * Find the view by [parent] class name in sources.
	 *
	 * @param string $viewName [parent] class name in lower case
	 *
	 * @return array|null array will contain "name"(string), "source"(Source)
	 */
	public function locate(string $viewName): array
	{
		$reflection = new \ReflectionClass($viewName);
		$filename = $reflection->getFilename();


		foreach ($this->sources as $source) {
			if (!str_starts_with($filename, $source->getHome()->getPath() . DIRECTORY_SEPARATOR)) {
				continue;
			}

			return [
				'name' => $viewName,
				'source' => $source,
			];
		}

		throw new Exception("Cannot find source of '{$viewName}'");
	}

	/**
	 * Generate an  URL to file.
	 *
	 * @param string $file     path to file
	 * @param bool   $absolute make URL absolute by adding scheme and hostname
	 */
	public function url(string $file, bool $absolute = false): ?string
	{
		$url = '';
		if ($absolute) {
			$url .= Request::getSchemeAndHttpHost();
		}
		if (!$this->primarySource) {
			return null;
		}
		if ($this->primarySource->hasFileAsset($file)) {
			return $url . $this->primarySource->url($file);
		}

		$sources = self::byName($this->primarySource->getName());
		foreach ($sources as $source) {
			if ($source->hasFileAsset($file)) {
				return $url . $source->url($file);
			}
		}

		return $url . $this->primarySource->url($file);
	}


	public function setPrimarySource(Source $source): void
	{
		$this->primarySource = $source;
	}

	public function addSource(Source $source): void
	{
		$this->sources[] = $source;
	}

	/**
	 * Find frontend souce by home directory and remove it from inventory.
	 *
	 * @return bool wheter it can found it or not
	 */
	public function removeSource(string $path): bool
	{
		$found = false;
		foreach ($this->sources as $key => $source) {
			if ($source->getHome()->getPath() == $path) {
				$found = $key;
				break;
			}
		}
		if (false !== $found) {
			unset($this->sources[$found]);

			return true;
		}

		return false;
	}

	/**
	 * Find frontend source by home directory path.
	 *
	 * @return Source|null
	 */
	public function byPath(string $path): ?Source
	{
		foreach ($this->sources as $key => $source) {
			if ($source->getHome()->getPath() == $path) {
				return $source;
			}
		}

		return null;
	}

	/**
	 * Find frontend sources by given name.
	 *
	 * @return Source[]
	 */
	public function byName(string $name): array
	{
		$sources = [];
		foreach ($this->sources as $source) {
			if ($source->getName() == $name) {
				$sources[] = $source;
			}
		}

		return $sources;
	}

	/**
	 * Getter for all sources.
	 *
	 * @return Source[]
	 */
	public function get(): array
	{
		return $this->sources;
	}
}
