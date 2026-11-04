<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Authorization;

use Drago\Database\Entity;


class RolePrivilegeEntity extends Entity
{
	public const string Table = 'role_privileges',
		ColumnRole = 'role',
		ColumnResource = 'resource',
		ColumnPrivilege = 'privilege';

	public string $role;
	public string $resource;
	public string $privilege;
}
