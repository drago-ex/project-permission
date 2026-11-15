<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use App\Model\User\UserRoleEntity;
use App\Presentation\Backend\Permission\Component\Authorization\RolePrivilegeEntity;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\ExtraFluent;
use Drago\Database\Table;
use Throwable;


/** The custom roles (table roles); the base roles live in the ACL neon files. */
class RolesRepository
{
	/** @var Table<RolesEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, RolesEntity::Table, RolesEntity::PrimaryKey, RolesEntity::class);
	}


	/**
	 * The titles of the custom roles by name.
	 * @return array<string, string>
	 */
	public function getTitles(): array
	{
		return $this->table->read('*')
			->fetchPairs(RolesEntity::ColumnName, RolesEntity::ColumnTitle);
	}


	/**
	 * The roles for the grid, the newest first.
	 * @return ExtraFluent<RolesEntity>
	 */
	public function getRolesFluent(): ExtraFluent
	{
		return $this->table->read('*')
			->orderBy(RolesEntity::PrimaryKey, 'DESC');
	}


	/** @throws Exception */
	public function setActive(int $id, bool $active): void
	{
		$this->table->update([RolesEntity::ColumnActive => $active])
			->where('%n = ?', RolesEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * Deletes the role with its privileges and takes it from the users, in one transaction.
	 * @throws Exception
	 * @throws Throwable
	 */
	public function deleteRole(string $name): void
	{
		$connection = $this->table->getConnection();
		$connection->transaction(function () use ($connection, $name): void {
			$connection->delete(RolePrivilegeEntity::Table)
				->where('%n = ?', RolePrivilegeEntity::ColumnRole, $name)
				->execute();

			$connection->delete(UserRoleEntity::Table)
				->where('%n = ?', UserRoleEntity::ColumnRole, $name)
				->execute();

			$connection->delete(RolesEntity::Table)
				->where('%n = ?', RolesEntity::ColumnName, $name)
				->execute();
		});
	}
}
