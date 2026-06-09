<?php

declare(strict_types=1);

namespace App\UI\Backend\Permission;

use Drago\Permission\Provider;
use Drago\Permission\Role;
use Nette\Security\Permission;


/** Backend permission provider. */
class PermissionProvider implements Provider
{
	private const string Resource = 'Backend:Permission';


	/** Registers permissions. */
	public function register(Permission $acl): void
	{
		$acl->addResource(self::Resource);
		$acl->allow(Role::RoleAdmin);
	}
}
