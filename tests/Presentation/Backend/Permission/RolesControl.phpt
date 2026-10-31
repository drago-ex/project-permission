<?php declare(strict_types=1);

/**
 * Test: App\Presentation\Backend\Permission\Component\Roles\RolesControl
 */

use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesControl;
use App\Presentation\Backend\Permission\Component\Roles\RolesEntity;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use Dibi\Connection;
use Drago\Database\ExtraFluent;
use Drago\Database\Table;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


/**
 * A query that returns a fixed record or result instead of running against a database.
 * @extends ExtraFluent<RolesEntity>
 */
class FixedFluent extends ExtraFluent
{
	public function __construct(
		private readonly ?RolesEntity $record = null,
		private readonly ?Closure $onExecute = null,
	) {
		parent::__construct(new Connection(['driver' => 'mysqli', 'lazy' => true]));
	}


	public function record(): ?RolesEntity
	{
		return $this->record;
	}


	public function execute(?string $return = null): Dibi\Result|int|null
	{
		($this->onExecute)();
		return 1;
	}
}


/**
 * The roles table in memory: get() returns the role of the id, delete() keeps the deleted id.
 * @extends Table<RolesEntity>
 */
readonly class MemoryRolesTable extends Table
{
	public function __construct(
		/** @var array<int, string> id => name */
		private array $roles,
		/** @var ArrayObject<int, mixed> */
		public ArrayObject $deleted = new ArrayObject,
	) {
		parent::__construct(
			new Connection(['driver' => 'mysqli', 'lazy' => true]),
			RolesEntity::Table,
			RolesEntity::PrimaryKey,
			RolesEntity::class,
		);
	}


	/** @return ExtraFluent<RolesEntity> */
	public function get(int $id): ExtraFluent
	{
		$role = null;
		if (isset($this->roles[$id])) {
			$role = new RolesEntity;
			$role->id = $id;
			$role->name = $this->roles[$id];
			$role->description = ucfirst($this->roles[$id]);
		}

		return new FixedFluent($role);
	}


	/** @return ExtraFluent<RolesEntity> */
	public function delete(string|array $column, mixed $args = null): ExtraFluent
	{
		return new FixedFluent(onExecute: function () use ($args): void {
			$this->deleted[] = $args;
		});
	}
}


class MemoryRoles extends RolesRepository
{
	public MemoryRolesTable $memory;


	/** @param array<int, string> $roles id => name */
	public function __construct(array $roles)
	{
		$this->table = $this->memory = new MemoryRolesTable($roles);
	}
}


function deleteRole(MemoryRoles $roles, int $id): void
{
	$factory = (new ReflectionClass(Factory::class))->newInstanceWithoutConstructor();
	$control = new RolesControl($factory, $roles);
	Assert::with($control, fn() => $this->deleteItem($id));
}


test('System roles cannot be deleted, even when the delete form is posted directly', function () {
	$roles = new MemoryRoles([1 => 'admin', 2 => 'user', 3 => 'guest']);

	foreach ([1, 2, 3] as $id) {
		Assert::exception(
			fn() => deleteRole($roles, $id),
			RuntimeException::class,
			'System role cannot be deleted.',
			1,
		);
	}

	Assert::same([], $roles->memory->deleted->getArrayCopy());
});


test('A custom role is deleted', function () {
	$roles = new MemoryRoles([4 => 'editor']);

	deleteRole($roles, 4);
	Assert::same([4], $roles->memory->deleted->getArrayCopy());
});
