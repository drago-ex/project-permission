<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Roles;

use Dibi\Connection;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;


/** @extends Database<RolesEntity> */
#[Table(RolesEntity::Table, RolesEntity::PrimaryKey, class: RolesEntity::class)]
class RolesRepository
{
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getAllRoles(): array
	{
		return $this->read('*')
			->fetchPairs(RolesEntity::PrimaryKey, RolesEntity::ColumnDescription);
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getRolesFluent(): ExtraFluent
	{
		return $this->read('*');
	}
}
