<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use Drago\Utils\ExtraArrayHash;


class UsersValues extends ExtraArrayHash
{
	public const string UserId = 'userId',
		RoleId = 'roleId',
		Id = 'id';

	public ?int $id = null;
	public int $userId;

	/** @var array<int, int> */
	public array $roleId;
}
