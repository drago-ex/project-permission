<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Authorization;

use App\Presentation\Backend\Permission\Component\BaseTemplate;


class AuthorizationTemplate extends BaseTemplate
{
	/** @var array<string, string> the roles of the ACL by name, with their labels */
	public array $roles = [];
	public string $role = '';
	public string $roleName = '';

	/**
	 * The privileges by resource: allowed, own (set here, so it can be toggled) or inherited (allowed by the neon
	 * files or a parent role, locked here).
	 * @var array<string, list<array{privilege: string, allowed: bool, own: bool, inherited: bool}>>
	 */
	public array $groups = [];
	public int $allowedCount = 0;
	public int $deniedCount = 0;
	public bool $isAdminRole = false;
}
