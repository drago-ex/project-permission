<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use Dibi\Connection;
use Dibi\Fluent;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<ResourcesEntity> */
#[Table(ResourcesEntity::Table, ResourcesEntity::PrimaryKey, class: ResourcesEntity::class)]
class ResourcesRepository
{
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	public function getAll(): Fluent
	{

	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getAllResources(): array
	{
		return $this->read('*')
			->fetchPairs(
				ResourcesEntity::PrimaryKey,
				ResourcesEntity::ColumnDescription,
			);
	}
}
