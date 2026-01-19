<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table(UsersRolesEntity::Table, class: UsersRolesEntity::class)]
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
