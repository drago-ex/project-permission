<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use Drago\Utils\ExtraArrayHash;


class UsersValues extends ExtraArrayHash
{
	public const string UserId = 'userId',
		UserName = 'userName',
		RoleId = 'roleId',
		Id = 'id';

	public ?int $id = null;

	/** Null in an edit, which shows the user readonly and saves the roles of the id. */
	public ?int $userId = null;

	/** @var array<int, int> */
	public array $roleId;
}
