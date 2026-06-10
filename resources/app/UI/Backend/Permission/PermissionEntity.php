<?php

declare(strict_types=1);

namespace app\UI\Backend\Permission;

use Drago\Database\Entity;


/** Component entity. */
class PermissionEntity extends Entity
{
	public string $role;
	public string $privilege;
	public string $resource;
}
