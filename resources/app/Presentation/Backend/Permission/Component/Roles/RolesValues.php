<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use Drago\Utils\ExtraArrayHash;


class RolesValues extends ExtraArrayHash
{
	public const string Name = 'name',
		Title = 'title',
		Description = 'description',
		Icon = 'icon',
		Active = 'active';

	public ?int $id = null;
	public string $name;
	public string $title;
	public ?string $description = null;
	public string $icon;
	public bool $active;
}
