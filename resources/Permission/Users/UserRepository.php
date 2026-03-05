<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use Dibi\Connection;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<UsersEntity> */
#[Table(UsersEntity::Table, UsersEntity::PrimaryKey, class: UsersEntity::class)]
class UserRepository
{
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getAllUsers(): array
	{
		return $this->read('*')
			->fetchPairs(UsersEntity::PrimaryKey, UsersEntity::ColumnUsername);
	}
}
