<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Authorization;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Dibi\Row;
use Drago\Database\Table;


readonly class AuthorizationRepository
{
	/** @var Table<AuthorizationEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, AuthorizationEntity::Table, AuthorizationEntity::PrimaryKey, AuthorizationEntity::class);
	}


	public function getAll(): Fluent
	{
		return $this->table->read('*');
	}


	/** @return list<Row> */
	public function getRolePermissions(int $roleId): array
	{
		return $this->table->getConnection()->select("
				r.id,
				r.resource,
				r.privilege,
				r.description,
				CASE
					WHEN a.role_id IS NULL THEN 'deny'
					ELSE 'allow'
				END AS access
			")
			->from(ResourcesEntity::Table)
			->as('r')
			->leftJoin(AuthorizationEntity::Table)
			->as('a')
			->on('a.resource_id = r.id AND a.role_id = ?', $roleId)
			->orderBy('r.resource, r.privilege')
			->fetchAll();
	}


	/** @throws Exception */
	public function allow(int $roleId, int $resourceId): void
	{
		$this->table->getConnection()->insert(AuthorizationEntity::Table, [
			AuthorizationEntity::ColumnRoleId => $roleId,
			AuthorizationEntity::ColumnResourceId => $resourceId,
		])
			->execute();
	}


	/** @throws Exception */
	public function deny(int $roleId, int $resourceId): void
	{
		$this->table->getConnection()->delete(AuthorizationEntity::Table)
			->where('%n = ?', AuthorizationEntity::ColumnRoleId, $roleId)
			->where('%n = ?', AuthorizationEntity::ColumnResourceId, $resourceId)
			->execute();
	}
}
