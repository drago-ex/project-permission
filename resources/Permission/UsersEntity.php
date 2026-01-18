<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission;

use Drago;


class UsersEntity extends Drago\Database\Entity
{
	public const string
		Table = 'users',
		PrimaryKey = 'id',
		ColumnUsername = 'username';

	/**
	 * Primary key
	 * Column size 10
	 */
	public int $id;

	/** Column size 50 */
	public string $username;
}
