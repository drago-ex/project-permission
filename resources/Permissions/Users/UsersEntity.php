<?php

declare(strict_types=1);

namespace App\Core\Permissions\Users;

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
