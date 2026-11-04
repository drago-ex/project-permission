<?php declare(strict_types=1);

/**
 * Test: App\Presentation\Backend\Permission\RoleRules
 */

use App\Presentation\Backend\Permission\RoleRules;
use Dibi\Connection;
use Drago\Permission\Role;
use Nette\Caching\Storages\MemoryStorage;
use Nette\Security\Permission;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


/** The rules of the administration without a database; $loads counts how often they are read. */
readonly class FixedRoleRules extends RoleRules
{
	public function __construct(
		/** @var array{list<string>, list<array{string, string, string}>} */
		private array $rules,
		/** @var ArrayObject<int, true> */
		public ArrayObject $loads = new ArrayObject,
	) {
		parent::__construct(new Connection(['driver' => 'mysqli', 'lazy' => true]), new MemoryStorage);
	}


	protected function loadRules(): array
	{
		$this->loads[] = true;
		return $this->rules;
	}
}


/** The ACL of the neon files: the base roles and two presenters. */
function baseAcl(): Permission
{
	$acl = new Permission;
	$acl->addRole(Role::Guest);
	$acl->addRole(Role::User, Role::Guest);
	$acl->addRole(Role::Admin, Role::User);
	$acl->addResource('Backend:Blog');
	$acl->addResource('Account');
	$acl->allow(Role::User, 'Account');
	return $acl;
}


test('A custom role inherits the user and gets its privileges', function () {
	$rules = new FixedRoleRules([['moderator'], [['moderator', 'Backend:Blog', 'approveComment']]]);
	$acl = baseAcl();
	$rules->apply($acl);

	Assert::true($acl->roleInheritsFrom('moderator', Role::User));
	Assert::true($acl->isAllowed('moderator', 'Backend:Blog', 'approveComment'));
	Assert::false($acl->isAllowed('moderator', 'Backend:Blog', 'delete'));
	Assert::true($acl->isAllowed('moderator', 'Account', 'default'));
});


test('A base role can get privileges too', function () {
	$rules = new FixedRoleRules([[], [[Role::User, 'Backend:Blog', 'default']]]);
	$acl = baseAcl();
	$rules->apply($acl);

	Assert::true($acl->isAllowed(Role::User, 'Backend:Blog', 'default'));
});


test('A removed role or presenter is skipped instead of failing', function () {
	$rules = new FixedRoleRules([[], [['removed', 'Backend:Blog', 'default'], [Role::User, 'Backend:Removed', 'default']]]);
	$acl = baseAcl();
	Assert::noError(fn() => $rules->apply($acl));
	Assert::false($acl->hasResource('Backend:Removed'));
});


test('The rules are read once and again after clean()', function () {
	$rules = new FixedRoleRules([['moderator'], []]);
	$rules->apply(baseAcl());
	$rules->apply(baseAcl());
	Assert::count(1, $rules->loads);

	$rules->clean();
	$rules->apply(baseAcl());
	Assert::count(2, $rules->loads);
});


test('Another authorizator than Permission is left as it is', function () {
	$rules = new FixedRoleRules([['moderator'], []]);
	$rules->apply(new class implements Nette\Security\Authorizator {
		public function isAllowed(mixed $role, mixed $resource, mixed $privilege): bool
		{
			return false;
		}
	});
	Assert::count(0, $rules->loads);
});


test('The decorator of conf.neon applies the rules to the ACL, which Nette registers as Authorizator', function () {
	$temp = __DIR__ . '/../../../tmp/' . getmypid();
	@mkdir($temp, 0o777, true); // @ the directory may already exist

	$loader = new Nette\DI\ContainerLoader($temp, autoRebuild: true);
	$class = $loader->load(function (Nette\DI\Compiler $compiler): void {
		$compiler->addExtension('decorator', new Nette\DI\Extensions\DecoratorExtension);
		$compiler->loadConfig(Tester\FileMock::create(<<<'NEON'
			services:
				# the ACL as SecurityExtension of Nette defines it
				acl: {type: Nette\Security\Authorizator, factory: Nette\Security\Permission, setup: [addRole(user)]}
				rules: FixedRoleRules([[moderator], []])

			decorator:
				Nette\Security\Authorizator:
					setup:
						- @rules::apply(@self)
			NEON, 'neon'));
	}, __FILE__);

	$acl = (new $class)->getService('acl');
	Assert::true($acl instanceof Permission && $acl->hasRole('moderator'));
});
