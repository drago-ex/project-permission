<?php

declare(strict_types=1);

namespace App\Core\Permissions\Access;

use Drago\Utils\ExtraArrayHash;


class AccessValues extends ExtraArrayHash
{
	public const string
		RoleId = 'role_id',
		SourceId = 'source_id',
		Effect = 'effect';

	public int $role_id;
	public int $source_id;
	public string $effect;
}
