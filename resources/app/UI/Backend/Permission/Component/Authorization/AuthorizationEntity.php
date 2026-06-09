<?php

declare(strict_types=1);

namespace App\UI\Backend\Permission\Component\Authorization;

use Drago\Database\Entity;


/** Authorization entity. */
class AuthorizationEntity extends Entity
{
	public const string
		Table = 'authorization',
		PrimaryKey = 'id',
		ColumnRoleId = 'role_id',
		ColumnResourceId = 'resource_id';

	public int $id;
	public int $role_id;
	public int $resource_id;
}
