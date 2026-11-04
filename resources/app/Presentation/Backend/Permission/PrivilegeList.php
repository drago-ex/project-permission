<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission;

use Drago\Permission\PrivilegeCatalog;
use Nette\Application\InvalidPresenterException;
use Nette\Application\IPresenterFactory;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Finder;
use ReflectionClass;
use ReflectionException;
use Throwable;


/**
 * The privileges of a presenter for the administration, read from its code by PrivilegeCatalog of drago-ex/permission.
 * Cached until a file of the presenter directory changes, so a new action or button shows up while developing
 * and production reads the code once.
 */
readonly class PrivilegeList
{
	private Cache $cache;


	public function __construct(
		private PrivilegeCatalog $catalog,
		private IPresenterFactory $presenterFactory,
		Storage $storage,
	) {
		$this->cache = new Cache($storage, 'permission.privileges');
	}


	/**
	 * @return list<string>
	 * @throws Throwable
	 */
	public function get(string $resource): array
	{
		return $this->cache->load($resource, function (?array &$dependencies) use ($resource): array {
			$dependencies[Cache::Files] = $this->getFiles($resource);
			return $this->catalog->getPrivileges($resource);
		});
	}


	/** @return list<string> the PHP and Latte files of the presenter directory, with its components */
	private function getFiles(string $resource): array
	{
		try {
			$file = (new ReflectionClass($this->presenterFactory->getPresenterClass($resource)))->getFileName();
		} catch (InvalidPresenterException|ReflectionException) {
			return [];
		}

		if ($file === false) {
			return [];
		}

		$files = [];
		foreach (Finder::findFiles('*.php', '*.latte')->from(dirname($file)) as $item) {
			$files[] = $item->getPathname();
		}

		return $files;
	}
}
