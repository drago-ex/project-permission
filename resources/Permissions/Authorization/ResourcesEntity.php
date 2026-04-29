<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use Drago\Database\Entity;


class ResourcesEntity extends Entity
{
	public const string
		Table = 'resources',
		PrimaryKey = 'id',
		ColumnResource = 'resource',
		ColumnPrivilege = 'privilege',
		ColumnDescription = 'description';

	public int $id;
	public string $resource;
	public string $privilege;
	public string $description;
}
