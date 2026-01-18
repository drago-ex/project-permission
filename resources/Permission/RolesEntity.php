<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission;

use Drago;


class RolesEntity extends Drago\Database\Entity
{
	public const string
		Table = 'roles',
		PrimaryKey = 'id',
		ColumnName = 'name',
		ColumnRole = 'role';

	/**
	 * Primary key
	 * Column size 10
	 */
	public int $id;

	/** Column size 40 */
	public string $name;

	/** Column size 40 */
	public string $role;
}
