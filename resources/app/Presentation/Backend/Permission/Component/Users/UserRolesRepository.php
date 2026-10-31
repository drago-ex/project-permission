<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use App\Presentation\Backend\Permission\Component\Roles\RolesEntity;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Drago\Database\Table;


readonly class UserRolesRepository
{
	/** @var Table<UsersRolesEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, UsersRolesEntity::Table, UsersRolesEntity::ColumnUserId, UsersRolesEntity::class);
	}


	/**
	 * @return list<UsersRolesEntity>
	 * @throws Exception
	 */
	public function getUserRoles(int $userId): array
	{
		return $this->table->find(UsersRolesEntity::ColumnUserId, $userId)
			->recordAll();
	}


	public function getAllUserRoles(): Fluent
	{
		return $this->table->read('ur.user_id id, u.username, GROUP_CONCAT(r.description SEPARATOR ", ") AS roles')->as('ur')
			->innerJoin(UsersEntity::Table)
			->as('u')
			->on('ur.user_id = u.id')
			->innerJoin(RolesEntity::Table)
			->as('r')
			->on('ur.role_id = r.id')
			->groupBy('u.id, u.username')
			->orderBy('id', 'DESC');
	}
}
