<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use Drago\Database\Entity;


class AuthorizationEntity extends Entity
{
	public const string
		Table = 'access',
		ColumnRoleId = 'role_id',
		ColumnResourceId = 'resource_id',
		ColumnAccess = 'access';

	public int $role_id;
	public int $resource_id;
	public string $access;
}
