<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use Drago\Utils\ExtraArrayHash;


class UsersValues extends ExtraArrayHash
{
	public const string
		UserId = 'user_id',
		RoleId = 'role_id';

	public int $user_id;
	public array $role_id;
}
