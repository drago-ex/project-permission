<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission;

use App\Presentation\Backend\Permission\Component\Authorization\RolePrivilegeEntity;
use App\Presentation\Backend\Permission\Component\Roles\RolesEntity;
use Dibi\Connection;
use Dibi\DriverException;
use Drago\Permission\Role;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Security\Authorizator;
use Nette\Security\Permission;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;


/**
 * The custom roles and the privileges set in the administration, added to the ACL of the neon files
 * (the decorator of conf.neon calls apply() on the authorizator; Nette registers it as Authorizator, so the decorator
 * is for that type, not for Permission). Cached; the administration calls clean() after
 * a change, so the database is read once per change, not on every request.
 */
readonly class RoleRules
{
	private const string CacheKey = 'rules';

	private Cache $cache;


	public function __construct(
		private Connection $connection,
		Storage $storage,
	) {
		$this->cache = new Cache($storage, 'permission.roleRules');
	}


	/**
	 * Adds the custom roles (inheriting "user") and the allowed privileges of known roles and resources.
	 * An authorizator other than Permission (replaced by the project) is left as it is.
	 * @throws Throwable
	 */
	public function apply(Authorizator $acl): void
	{
		if (!$acl instanceof Permission) {
			return;
		}

		[$roles, $privileges] = $this->load();

		foreach ($roles as $role) {
			if (!$acl->hasRole($role)) {
				$acl->addRole($role, Role::User);
			}
		}

		foreach ($privileges as [$role, $resource, $privilege]) {
			// a role or a presenter removed since (e.g. an uninstalled package) is skipped
			if ($acl->hasRole($role) && $acl->hasResource($resource)) {
				$acl->allow($role, $resource, $privilege);
			}
		}
	}


	/** Forgets the cached rules after a change in the administration. */
	public function clean(): void
	{
		$this->cache->remove(self::CacheKey);
	}


	/**
	 * The names of the custom roles and the privileges as [role, resource, privilege].
	 * @return array{list<string>, list<array{string, string, string}>}
	 * @throws Throwable
	 */
	protected function loadRules(): array
	{
		$roles = $this->connection->select(RolesEntity::ColumnName)
			->from(RolesEntity::Table)
			->fetchPairs(value: RolesEntity::ColumnName);

		$privileges = [];
		$rows = $this->connection->select('*')
			->from(RolePrivilegeEntity::Table)
			->fetchAll();

		foreach ($rows as $row) {
			$privileges[] = [
				(string) $row[RolePrivilegeEntity::ColumnRole],
				(string) $row[RolePrivilegeEntity::ColumnResource],
				(string) $row[RolePrivilegeEntity::ColumnPrivilege],
			];
		}

		return [array_values(array_map(strval(...), $roles)), $privileges];
	}


	/**
	 * @return array{list<string>, list<array{string, string, string}>}
	 * @throws Throwable
	 */
	private function load(): array
	{
		try {
			return $this->cache->load(self::CacheKey, fn() => $this->loadRules());
		} catch (DriverException $e) {
			// Without the tables (e.g. before the migrations) only the neon files apply; nothing is cached.
			Debugger::log($e, ILogger::WARNING);
			return [[], []];
		}
	}
}
