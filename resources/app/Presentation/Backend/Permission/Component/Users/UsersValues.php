<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use Drago\Utils\ExtraArrayHash;


class UsersValues extends ExtraArrayHash
{
	public const string Id = 'id',
		UserName = 'userName',
		Roles = 'roles';

	/** The edited user. */
	public int $id;

	/** @var list<string> role names; none leaves the user with the default role "user" */
	public array $roles = [];
}
