<?php

declare(strict_types=1);

namespace App\Core\Permissions\Access;

use Dibi\Connection;
use Dibi\Fluent;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<AccessEntity> */
#[Table(AccessEntity::Table, class: AccessEntity::class)]
class AccessRepository
{
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	public function getAll(): Fluent
	{

	}


	public function getAllPermissions()
	{

	}
}
