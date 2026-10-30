<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use Drago\Database\Entity;


/** The users as the permission administration needs them: only the name; the whole user is UserEntity of project-auth. */
class UsersEntity extends Entity
{
	public const string Table = 'users',
		PrimaryKey = 'id',
		ColumnUsername = 'username';

	public int $id;
	public string $username;
}
