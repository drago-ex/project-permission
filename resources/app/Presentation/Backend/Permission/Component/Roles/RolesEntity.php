<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use Drago\Database\Entity;


class RolesEntity extends Entity
{
	public const string
		Table = 'roles',
		PrimaryKey = 'id',
		ColumnName = 'name',
		ColumnDescription = 'description';

	public int $id;
	public string $name;
	public string $description;
}
