<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission;

use App\Presentation\Backend\Permission\Component\Authorization\AuthorizationEntity;
use App\Presentation\Backend\Permission\Component\Authorization\ResourcesEntity;
use App\Presentation\Backend\Permission\Component\Roles\RolesEntity;
use Dibi\Connection;
use Dibi\DriverException;
use Drago\Permission\Provider;
use Drago\Permission\Role;
use Nette\Security\Authorizator;
use Nette\Security\Permission;
use Tracy\Debugger;
use Tracy\ILogger;


class PermissionFactory
{
	/** @var iterable<Provider> */
	private iterable $initializers;


	/** @param iterable<Provider> $initializers */
	public function __construct(
		private readonly Connection $connection,
		iterable $initializers = [],
	) {
		$this->initializers = $initializers;
	}


	public function create(): Permission
	{
		$acl = new Permission;
		$acl->addRole(Role::RoleGuest);
		$acl->addRole(Role::RoleUser, Role::RoleGuest);
		$acl->addRole(Role::RoleAdmin, Role::RoleUser);

		foreach ($this->initializers as $initializer) {
			$initializer->register($acl);
		}

		try {
			/** @var RolesEntity[] $roles */
			$roles = $this->connection
				->select('*')
				->from(RolesEntity::Table)
				->fetchAll();

			// Roles added in the administration inherit the signed-in user (sign out, front, account).
			foreach ($roles as $role) {
				if (!$acl->hasRole($role->name)) {
					$acl->addRole($role->name, Role::RoleUser);
				}
			}

			/** @var ResourcesEntity[] $resources */
			$resources = $this->connection
				->select('*')
				->from(ResourcesEntity::Table)
				->fetchAll();

			foreach ($resources as $resource) {
				if (!$acl->hasResource($resource->resource)) {
					$acl->addResource($resource->resource);
				}
			}

			/** @var PermissionEntity[] $permissions */
			$permissions = $this->connection
				->select('r.name AS role, res.resource, res.privilege')
				->from(AuthorizationEntity::Table)
				->as('a')
				->innerJoin(RolesEntity::Table)
				->as('r')
				->on('a.role_id = r.id')
				->innerJoin(ResourcesEntity::Table)
				->as('res')
				->on('a.resource_id = res.id')
				->fetchAll();

			foreach ($permissions as $row) {
				$acl->allow($row->role, $row->resource, self::privilege($row->privilege));
			}
		} catch (DriverException $e) {
			// Without the tables (e.g. before migrations) only the rules from the providers apply.
			Debugger::log($e, ILogger::WARNING);
		}

		return $acl;
	}


	/**
	 * The privilege of a resources row for the ACL: "all" means every privilege of the resource.
	 */
	public static function privilege(string $privilege): ?string
	{
		return $privilege === 'all' ? Authorizator::All : $privilege;
	}
}
