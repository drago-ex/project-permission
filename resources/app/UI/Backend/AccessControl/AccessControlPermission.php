<?php

declare(strict_types=1);

namespace App\UI\Backend\AccessControl;

use Drago\Permission\Provider;
use Drago\Permission\Role;
use Nette\Security\Permission;


class AccessControlPermission implements Provider
{
	private const string Resource = 'Backend:AccessControl';

	public function register(Permission $acl): void
	{
		$acl->addResource(self::Resource);

		// admin has full access
		$acl->allow(Role::RoleAdmin);
	}
}
