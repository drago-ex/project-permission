<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use App\Model\User\UserRoleEntity;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Drago\Permission\Role;


/** The roles of the users, in the table user_roles of drago-ex/project-auth. */
readonly class UserRolesRepository
{
	public function __construct(
		private Connection $connection,
	) {
	}


	/**
	 * All users with their role names ("user" for a user without a role, who has the default role). A derived table,
	 * so the grid filters and sorts "roles" like a column (WHERE cannot use the alias of GROUP_CONCAT).
	 */
	public function getAllUserRoles(): Fluent
	{
		$users = $this->connection->select(
			'u.id, u.username, COALESCE(GROUP_CONCAT(ur.role ORDER BY ur.role SEPARATOR ", "), %s) AS roles',
			Role::User,
		)
			->from(UsersEntity::Table)
			->as('u')
			->leftJoin(UserRoleEntity::Table)
			->as('ur')
			->on('ur.%n = u.id', UserRoleEntity::ColumnUserId)
			->groupBy('u.id, u.username');

		return $this->connection->select('*')
			->from('(%SQL) AS t', (string) $users)
			->orderBy('id DESC');
	}


	/**
	 * @return list<string>
	 * @throws Exception
	 */
	public function getRoles(int $userId): array
	{
		$roles = $this->connection->select(UserRoleEntity::ColumnRole)
			->from(UserRoleEntity::Table)
			->where('%n = ?', UserRoleEntity::ColumnUserId, $userId)
			->fetchPairs(value: UserRoleEntity::ColumnRole);

		return array_values(array_map(strval(...), $roles));
	}


	/**
	 * Replaces the roles of the user, in one transaction; no role leaves the user with the default role "user".
	 * @param list<string> $roles
	 * @throws Exception
	 */
	public function setRoles(int $userId, array $roles): void
	{
		$this->connection->transaction(function () use ($userId, $roles): void {
			$this->connection->delete(UserRoleEntity::Table)
				->where('%n = ?', UserRoleEntity::ColumnUserId, $userId)
				->execute();

			foreach ($roles as $role) {
				$this->connection->insert(UserRoleEntity::Table, [
					UserRoleEntity::ColumnUserId => $userId,
					UserRoleEntity::ColumnRole => $role,
				])->execute();
			}
		});
	}
}
