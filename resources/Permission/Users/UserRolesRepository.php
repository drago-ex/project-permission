<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<UsersRolesEntity> */
#[Table(UsersRolesEntity::Table, UsersRolesEntity::ColumnUserId,  class: UsersRolesEntity::class)]
class UserRolesRepository
{
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	/**
	 * @return UsersRolesEntity[]
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function getUserRoles(int $userId): array
	{
		return $this->find(UsersRolesEntity::ColumnUserId, $userId)
			->recordAll();
	}
}
