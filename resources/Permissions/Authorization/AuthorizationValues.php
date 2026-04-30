<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use Drago\Utils\ExtraArrayHash;


class AuthorizationValues extends ExtraArrayHash
{
	public const string
		RoleId = 'role_id',
		ResourceId = 'resource_id';

	public int $role_id;
	public int $resource_id;
}
