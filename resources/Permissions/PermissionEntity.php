<?php

declare(strict_types=1);

namespace App\Core\Permissions;

use Drago\Database\Entity;


class PermissionEntity extends Entity
{
	public string $role;
	public string $privilege;
	public string $resource;
}
