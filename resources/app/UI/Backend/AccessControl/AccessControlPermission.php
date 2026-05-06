<?php

declare(strict_types=1);

namespace App\UI\Backend\AccessControl;

use Drago\Permission\Provider;
use Drago\Permission\Role;
use Nette\Security\Permission;


class AccessControlPermission implements Provider
{
	private const string Resource = 'Backend:AccessControl';

	/** Read privileges granted to all authenticated members. */
	private const array ReadPrivileges = [
		'roles-read',
		'users-read',
	];


	public function register(Permission $acl): void
	{
		$acl->addResource(self::Resource);

		// admin has full access
		$acl->allow(Role::RoleAdmin);

		// read-only access for all members
		foreach (self::ReadPrivileges as $privilege) {
			$acl->allow(Role::RoleUser, self::Resource, $privilege);
		}
	}
}
