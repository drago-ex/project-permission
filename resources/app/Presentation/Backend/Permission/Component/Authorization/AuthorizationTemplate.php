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
	 * The sources are the places of the code that need the privilege, by kind (PrivilegeCatalog::Page, Signal, Form).
	 * @var array<string, list<array{privilege: string, label: ?string, sources: array<string, list<string>>, allowed: bool, own: bool, inherited: bool}>>
	 */
	public array $groups = [];

	/** The presenter whose privileges are shown. */
	public string $resource = '';

	/** @var array<string, int> the number of allowed privileges by resource, for the menu */
	public array $resourceAllowed = [];
	public int $allowedCount = 0;
	public int $deniedCount = 0;
	public bool $isAdminRole = false;
}
