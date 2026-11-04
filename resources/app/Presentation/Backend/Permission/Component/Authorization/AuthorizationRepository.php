<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Authorization;

use Dibi\Connection;
use Dibi\Exception;


/** The privileges given to the roles in the administration (table role_privileges). */
readonly class AuthorizationRepository
{
	public function __construct(
		private Connection $connection,
	) {
	}


	/**
	 * The privileges of the role by resource.
	 * @return array<string, list<string>>
	 * @throws Exception
	 */
	public function getPrivileges(string $role): array
	{
		$rows = $this->connection->select('*')
			->from(RolePrivilegeEntity::Table)
			->where('%n = ?', RolePrivilegeEntity::ColumnRole, $role)
			->fetchAll();

		$privileges = [];
		foreach ($rows as $row) {
			$privileges[(string) $row[RolePrivilegeEntity::ColumnResource]][] = (string) $row[RolePrivilegeEntity::ColumnPrivilege];
		}

		return $privileges;
	}


	/** @throws Exception */
	public function allow(string $role, string $resource, string $privilege): void
	{
		$this->connection->insert(RolePrivilegeEntity::Table, [
			RolePrivilegeEntity::ColumnRole => $role,
			RolePrivilegeEntity::ColumnResource => $resource,
			RolePrivilegeEntity::ColumnPrivilege => $privilege,
		])
			->setFlag('IGNORE')
			->execute();
	}


	/** @throws Exception */
	public function deny(string $role, string $resource, string $privilege): void
	{
		$this->connection->delete(RolePrivilegeEntity::Table)
			->where('%n = ?', RolePrivilegeEntity::ColumnRole, $role)
			->where('%n = ?', RolePrivilegeEntity::ColumnResource, $resource)
			->where('%n = ?', RolePrivilegeEntity::ColumnPrivilege, $privilege)
			->execute();
	}
}
