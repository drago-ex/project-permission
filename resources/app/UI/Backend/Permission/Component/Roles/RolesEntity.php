<?php

declare(strict_types=1);

namespace App\UI\Backend\Permission\Component\Roles;

use Drago\Database\Entity;


/** Roles entity. */
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
