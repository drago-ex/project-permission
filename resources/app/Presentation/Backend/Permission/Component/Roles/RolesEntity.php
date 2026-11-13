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
		ColumnIcon = 'icon',
		ColumnActive = 'active';

	/** The name is equal to UserRoleEntity::ColumnRoleSize of project-auth, which stores it. */
	public const int ColumnNameSize = 50,
		ColumnTitleSize = 100,
		ColumnDescriptionSize = 255;

	/** The icon of a role that has none of its own. */
	public const string DefaultIcon = 'bi-person';

	/**
	 * The icons a role can have (Bootstrap Icons) with their label and the color of the theme.
	 * @var array<string, array{string, string}>
	 */
	public const array Icons = [
		self::DefaultIcon => ['Person', 'secondary'],
		'bi-people' => ['People', 'primary'],
		'bi-pencil' => ['Pencil', 'info'],
		'bi-chat-dots' => ['Comments', 'success'],
		'bi-eye' => ['Eye', 'secondary'],
		'bi-star' => ['Star', 'warning'],
		'bi-shield-check' => ['Shield', 'danger'],
	];

	public int $id;
	public string $name;
	public string $title;
	public ?string $description = null;
	public string $icon = self::DefaultIcon;
	public bool $active = true;
}
