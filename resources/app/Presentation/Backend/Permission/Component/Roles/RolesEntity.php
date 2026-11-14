<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use Drago\Database\Entity;


class RolesEntity extends Entity
{
	public const string Table = 'roles',
		PrimaryKey = 'id',
		ColumnName = 'name',
		ColumnTitle = 'title',
		ColumnDescription = 'description',
		ColumnActive = 'active';

	/** The name is equal to UserRoleEntity::ColumnRoleSize of project-auth, which stores it. */
	public const int ColumnNameSize = 50,
		ColumnTitleSize = 100,
		ColumnDescriptionSize = 255;

	public int $id;
	public string $name;
	public string $title;
	public ?string $description = null;
	public bool $active = true;
}
