<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use Dibi\Connection;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table(UsersEntity::Table, UsersEntity::PrimaryKey, class: UsersEntity::class)]
class UserRepository
{
	/** @phpstan-use Database<UsersEntity> */
	use Database;

	public function __construct(
		protected readonly Connection $connection,
	) {
	}


	/**
	 * @return array<int, string>
	 * @throws AttributeDetectionException
	 */
	public function getAllUsers(): array
	{
		return $this->read('*')
			->fetchPairs(UsersEntity::PrimaryKey, UsersEntity::ColumnUsername);
	}
}
