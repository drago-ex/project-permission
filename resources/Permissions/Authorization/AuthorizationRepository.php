<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<AuthorizationEntity> */
#[Table(AuthorizationEntity::Table, AuthorizationEntity::PrimaryKey, class: AuthorizationEntity::class)]
class AuthorizationRepository
{
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getAll(): Fluent
	{
		return $this->read('*');
	}


	public function getRolePermissions(int $roleId): array
	{
		return $this->connection->select("
				r.id,
				r.resource,
				r.privilege,
				r.description,
				CASE
					WHEN a.role_id IS NULL THEN 'deny'
					ELSE 'allow'
				END AS effective_access
			")
			->from(ResourcesEntity::Table)->as('r')
			->leftJoin(AuthorizationEntity::Table)->as('a')
			->on('a.resource_id = r.id AND a.role_id = ?', $roleId)
			->orderBy('r.resource, r.privilege')
			->fetchAll();
	}


	/**
	 * @throws Exception
	 */
	public function allow(int $roleId, int $resourceId): void
	{
		$this->connection->insert(AuthorizationEntity::Table, [
			AuthorizationEntity::ColumnRoleId => $roleId,
			AuthorizationEntity::ColumnResourceId => $resourceId,
		])
			->execute();
	}


	/**
	 * @throws Exception
	 */
	public function deny(int $roleId, int $resourceId): void
	{
		$this->connection->delete(AuthorizationEntity::Table)
			->where('%n = %i', AuthorizationEntity::ColumnRoleId, $roleId)
			->where('%n = %i', AuthorizationEntity::ColumnResourceId, $resourceId)
			->execute();
	}
}
