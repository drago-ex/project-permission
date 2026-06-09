<?php

declare(strict_types=1);

namespace App\UI\Backend\Permission\Component\Roles;

use Drago\Utils\ExtraArrayHash;


/** Roles values. */
class RolesValues extends ExtraArrayHash
{
	public const string
		Name = 'name',
		Description = 'description';

	public ?int $id = null;
	public string $name;
	public string $description;
}
