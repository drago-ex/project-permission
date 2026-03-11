<?php

declare(strict_types=1);

namespace App\Core\Permissions\Access;

use Dibi\Connection;
use Dibi\Fluent;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


/** @extends Database<SourceEntity> */
#[Table(SourceEntity::Table, SourceEntity::PrimaryKey, class: SourceEntity::class)]
class SourceRepository
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
	public function getAllSource(): array
	{
		return $this->read('*')
			->fetchPairs(
				SourceEntity::PrimaryKey,
				SourceEntity::ColumnDescription,
			);
	}
}
