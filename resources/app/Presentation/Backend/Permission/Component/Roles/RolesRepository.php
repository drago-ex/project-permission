<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use Dibi\Connection;
use Drago\Database\ExtraFluent;
use Drago\Database\Table;


class RolesRepository
{
	/** @var Table<RolesEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, RolesEntity::Table, RolesEntity::PrimaryKey, RolesEntity::class);
	}


	/** @return array<int, string> */
	public function getAllRoles(): array
	{
		return $this->table->read('*')
			->fetchPairs(RolesEntity::PrimaryKey, RolesEntity::ColumnDescription);
	}


	/** @return ExtraFluent<RolesEntity> */
	public function getRolesFluent(): ExtraFluent
	{
		return $this->table->read('*')
			->orderBy(RolesEntity::PrimaryKey, 'DESC');
	}
}
