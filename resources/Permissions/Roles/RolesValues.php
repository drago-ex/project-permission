<?php

declare(strict_types=1);

namespace App\Core\Permissions\Roles;

use Drago\Utils\ExtraArrayHash;


class RolesValues extends ExtraArrayHash
{
	public const string
		Name = 'name',
		Description = 'description';

	public ?int $id = null;
	public string $name;
	public string $description;
}
