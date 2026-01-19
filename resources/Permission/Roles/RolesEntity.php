<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Roles;

use Drago;


class RolesEntity extends Drago\Database\Entity
{
	public const string
		Table = 'roles',
		PrimaryKey = 'id',
		ColumnName = 'name',
		ColumnDescription = 'description';

	/**
	 * Primary key
	 * Column size 10
	 */
	public int $id;

	/** Column size 40 */
	public string $name;

	/** Column size 40 */
	public string $description;
}
