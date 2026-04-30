<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use App\Core\Permissions\BaseTemplate;


class AuthorizationTemplate extends BaseTemplate
{
	public array $rolePermissions = [];
	public array $roles = [];
	public string $roleName = '';
	public int $roleId = 0;
	public int $allowedCount = 0;
	public int $deniedCount = 0;
}
