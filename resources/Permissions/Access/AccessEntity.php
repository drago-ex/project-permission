<?php

declare(strict_types=1);

namespace App\Core\Permissions\Access;

use Drago\Database\Entity;


class AccessEntity extends Entity
{
	public const string
		Table = 'access',
		ColumnRoleId = 'role_id',
		ColumnSourceId = 'source_id',
		ColumnEffect = 'effect';

	public int $role_id;
	public int $source_id;
	public string $effect;
}
