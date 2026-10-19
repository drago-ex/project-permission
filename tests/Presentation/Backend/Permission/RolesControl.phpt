<?php

/**
 * Test: App\Presentation\Backend\Permission\Component\Roles\RolesControl
 */

declare(strict_types=1);

use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesControl;
use App\Presentation\Backend\Permission\Component\Roles\RolesEntity;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use Dibi\Connection;
use Drago\Database\ExtraFluent;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


/** A query that returns a fixed record or result instead of running against a database. */
class FixedFluent extends ExtraFluent
{
	public function __construct(
		private ?RolesEntity $record = null,
		private ?Closure $onExecute = null,
	) {
		parent::__construct(new Connection(['driver' => 'mysqli', 'lazy' => true]));
	}


	public function record(): ?Dibi\Row
	{
		return $this->record;
	}


	public function execute(?string $return = null): Dibi\Result|int|null
	{
		($this->onExecute)();
		return 1;
	}
}


class MemoryRoles extends RolesRepository
{
	/** @var list<int> */
	public array $deleted = [];


	/** @param array<int, string> $roles id => name */
	public function __construct(
		private array $roles,
	) {
	}


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


	public function delete(string $column, mixed $args): ExtraFluent
	{
		return new FixedFluent(onExecute: function () use ($args): void {
			$this->deleted[] = $args;
		});
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

	Assert::same([], $roles->deleted);
});


test('A custom role is deleted', function () {
	$roles = new MemoryRoles([4 => 'editor']);

	deleteRole($roles, 4);
	Assert::same([4], $roles->deleted);
});
