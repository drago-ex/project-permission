<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use App\Presentation\Backend\Permission\Component\BaseTemplate;


class RolesTemplate extends BaseTemplate
{
	/** The user may open the privileges of the roles. */
	public bool $permissionsAllowed = false;

	/** @var array<string, string> the base roles of the ACL with their labels, for the menu of their privileges */
	public array $baseRoles = [];
}
