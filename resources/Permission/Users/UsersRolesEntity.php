<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use Drago;


class UsersRolesEntity extends Drago\Database\Entity
{
	public const string
		Table = 'users_roles',
		ColumnUserId = 'user_id',
		ColumnRoleId = 'role_id';

	/** Column size 10 */
	public int $user_id;

	/** Column size 10 */
	public int $role_id;
}
