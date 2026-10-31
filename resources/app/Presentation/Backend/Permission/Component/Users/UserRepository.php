<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use Dibi\Connection;
use Drago\Database\Table;


readonly class UserRepository
{
	/** @var Table<UsersEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, UsersEntity::Table, UsersEntity::PrimaryKey, UsersEntity::class);
	}


	/** @return array<int, string> */
	public function getAllUsers(): array
	{
		return $this->table->read('*')
			->fetchPairs(UsersEntity::PrimaryKey, UsersEntity::ColumnUsername);
	}
}
