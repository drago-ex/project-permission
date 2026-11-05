<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission;

use Drago\Permission\PrivilegeCatalog;
use Nette\Application\InvalidPresenterException;
use Nette\Application\IPresenterFactory;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use ReflectionClass;
use ReflectionException;
use Throwable;


/**
 * The privileges of a presenter for the administration, read from its code by PrivilegeCatalog of drago-ex/permission,
 * with their descriptions. Cached until a file of the presenter directory changes, so a new action or button shows
 * up while developing and production reads the code once.
 */
readonly class PrivilegeList
{
	private Cache $cache;


	/**
	 * @param array<string, array<string, string>> $labels descriptions of privileges by resource, the parameter
	 * permissionLabels of the acl.neon files of the packages
	 */
	public function __construct(
		private PrivilegeCatalog $catalog,
		private IPresenterFactory $presenterFactory,
		Storage $storage,
		private array $labels = [],
	) {
		$this->cache = new Cache($storage, 'permission.privilegeList');
	}


	/**
	 * @return list<string>
	 * @throws Throwable
	 */
	public function get(string $resource): array
	{
		return array_map(strval(...), array_keys($this->getSources($resource)));
	}


	/**
	 * The privileges with the places of the code that need them (PrivilegeCatalog::getSources()), for the labels.
	 * @return array<string, array<string, list<string>>>
	 * @throws Throwable
	 */
	public function getSources(string $resource): array
	{
		return $this->load($resource)['sources'];
	}


	/**
	 * Descriptions of the privileges, untranslated: from permissionLabels, else the title of the page template
	 * ({block title}{_'…'}{/block}).
	 * @return array<string, string>
	 * @throws Throwable
	 */
	public function getLabels(string $resource): array
	{
		return ($this->labels[$resource] ?? []) + $this->load($resource)['titles'];
	}


	/**
	 * @return array{sources: array<string, array<string, list<string>>>, titles: array<string, string>}
	 * @throws Throwable
	 */
	private function load(string $resource): array
	{
		return $this->cache->load($resource, function (?array &$dependencies) use ($resource): array {
			$dir = $this->getDirectory($resource);
			$dependencies[Cache::Files] = $dir !== null ? $this->getFiles($dir) : [];
			$sources = $this->catalog->getSources($resource);
			return ['sources' => $sources, 'titles' => $dir !== null ? $this->getTitles($dir, $sources) : []];
		});
	}


	/**
	 * The titles of the page templates, the translation keys of {block title}{_'…'}{/block}.
	 * @param array<string, array<string, list<string>>> $sources
	 * @return array<string, string>
	 */
	private function getTitles(string $dir, array $sources): array
	{
		$titles = [];
		foreach ($sources as $privilege => $kinds) {
			$file = "$dir/$privilege.latte";
			if (!isset($kinds[PrivilegeCatalog::Page]) || !is_file($file)) {
				continue;
			}

			$match = Strings::match(FileSystem::read($file), '~\{block title}\{_([\'"])(.+?)\1}\{/block}~');
			if ($match !== null) {
				$titles[(string) $privilege] = $match[2];
			}
		}

		return $titles;
	}


	/** The directory of the presenter class, null for a resource that is no presenter. */
	private function getDirectory(string $resource): ?string
	{
		try {
			$file = (new ReflectionClass($this->presenterFactory->getPresenterClass($resource)))->getFileName();
		} catch (InvalidPresenterException|ReflectionException) {
			return null;
		}

		return $file !== false ? dirname($file) : null;
	}


	/** @return list<string> the PHP and Latte files of the presenter directory, with its components */
	private function getFiles(string $dir): array
	{
		$files = [];
		foreach (Finder::findFiles('*.php', '*.latte')->from($dir) as $item) {
			$files[] = $item->getPathname();
		}

		return $files;
	}
}
