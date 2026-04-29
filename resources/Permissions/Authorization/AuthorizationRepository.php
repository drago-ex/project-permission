<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use Dibi\Connection;
use Dibi\Fluent;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<AuthorizationEntity> */
#[Table(AuthorizationEntity::Table, class: AuthorizationEntity::class)]
class AuthorizationRepository
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
