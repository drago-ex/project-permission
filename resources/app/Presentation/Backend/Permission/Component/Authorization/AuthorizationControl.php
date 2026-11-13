<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Authorization;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use App\Presentation\Backend\Permission\PrivilegeList;
use App\Presentation\Backend\Permission\RoleRules;
use Dibi\Exception;
use Drago\Permission\Access;
use Drago\Permission\Role;
use Nette\Application\Attributes\Requires;
use Nette\Application\BadRequestException;
use Nette\Security\Permission;
use Throwable;


/**
 * The privileges of one role in an offcanvas of the roles page: those of every presenter of the ACL, read from
 * the code (PrivilegeList). A privilege set here can be toggled; one allowed by the neon files or a parent role
 * is shown as inherited and locked. The role and the presenter come with every signal, nothing is kept in the URL.
 * @property-read AuthorizationTemplate $template
 */
class AuthorizationControl extends BaseControl
{
	/** The base roles of the ACL with their labels (translated); custom roles have their title. */
	public const array BaseRoles = [Role::Guest => 'Guest', Role::User => 'User', Role::Admin => 'Administrator'];

	/** The role whose privileges are shown; null until a signal opens one. */
	private ?string $role = null;

	/** The presenter whose privileges are shown; the first one of the ACL when not set. */
	private ?string $resource = null;


	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
		private readonly AuthorizationRepository $authorizationRepository,
		private readonly PrivilegeList $privilegeList,
		private readonly RoleRules $roleRules,
	) {
		parent::__construct($this->factory);
	}


	/**
	 * @throws BadRequestException
	 * @throws Exception
	 * @throws Throwable
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/Authorization.latte');
		$template->setTranslator($this->translator);
		$template->offcanvasId = $this->getUniqueIdComponent(self::Offcanvas);
		// without the privilege the toggles are shown, but cannot be clicked
		$template->writeAllowed = $this->isAllowed('edit');

		// the closed offcanvas is empty
		$role = $this->role;
		if ($role === null) {
			$template->render();
			return;
		}

		$acl = $this->getAcl();
		$own = $this->authorizationRepository->getPrivileges($role);
		$template->role = $role;
		$template->roleName = $this->getRoles()[$role];
		$template->isAdminRole = $role === Role::Admin;

		foreach ($acl->getResources() as $resource) {
			$labels = $this->privilegeList->getLabels($resource);
			foreach ($this->privilegeList->getSources($resource) as $privilege => $sources) {
				$privilege = (string) $privilege;
				$isOwn = in_array($privilege, $own[$resource] ?? [], true);
				$allowed = $acl->isAllowed($role, $resource, $privilege);
				$template->groups[$resource][] = [
					'privilege' => $privilege,
					'label' => $labels[$privilege] ?? null,
					'sources' => $sources,
					'allowed' => $allowed,
					'own' => $isOwn,
					'inherited' => $allowed && !$isOwn,
				];
				if ($allowed) {
					$template->allowedCount++;
					$template->resourceAllowed[$resource] = ($template->resourceAllowed[$resource] ?? 0) + 1;
				} else {
					$template->deniedCount++;
				}
			}
		}

		$resources = array_keys($template->groups);
		$template->resource = in_array($this->resource, $resources, true)
			? (string) $this->resource
			: (string) ($resources[0] ?? '');

		$template->render();
	}


	/**
	 * Opens the privileges of a role (the shield of the roles grid, the menu of the base roles).
	 * @throws BadRequestException
	 */
	#[Access('permissions')]
	#[Requires(ajax: true)]
	public function handleOpen(string $role): void
	{
		$this->setRole($role);
		$this->offCanvasComponent('permissions');
	}


	/**
	 * Shows the privileges of another presenter.
	 * @throws BadRequestException
	 */
	#[Access('permissions')]
	#[Requires(ajax: true)]
	public function handleResource(string $role, string $resource): void
	{
		$this->setRole($role);
		$this->resource = $resource;
		$this->redrawControl('permissions');
	}


	/**
	 * Allows or denies a privilege of the role; only a privilege of the code of a presenter of the ACL.
	 * @throws BadRequestException
	 * @throws Exception
	 * @throws Throwable
	 */
	#[Access('edit')]
	#[Requires(methods: 'POST', ajax: true)]
	public function handleTogglePermission(string $role, string $resource, string $privilege): void
	{
		$this->setRole($role);
		$this->resource = $resource;
		$acl = $this->getAcl();
		if (
			$role === Role::Admin
			|| !$acl->hasResource($resource)
			|| !in_array($privilege, $this->privilegeList->get($resource), true)
		) {
			$this->error();
		}

		if ((int) $this->getPresenter()->getHttpRequest()->getPost('allowed') === 1) {
			$this->authorizationRepository->allow($role, $resource, $privilege);
			$acl->allow($role, $resource, $privilege);
		} else {
			$this->authorizationRepository->deny($role, $resource, $privilege);
			$acl->removeAllow($role, $resource, $privilege);
		}

		$this->roleRules->clean();
		$this->redrawControl('permissions');
	}


	protected function deleteItem(int $id): void
	{
	}


	protected function getItemTitle(int $id): ?string
	{
		return null;
	}


	/**
	 * The role of a signal; only a role of the ACL.
	 * @throws BadRequestException
	 */
	private function setRole(string $role): void
	{
		if (!isset($this->getRoles()[$role])) {
			$this->error();
		}

		$this->role = $role;
	}


	/**
	 * The roles of the ACL by name: the base ones with a translated label, the custom ones with their title.
	 * @return array<string, string>
	 * @throws BadRequestException
	 */
	private function getRoles(): array
	{
		$titles = $this->rolesRepository->getTitles();
		$roles = [];
		foreach ($this->getAcl()->getRoles() as $role) {
			$roles[$role] = $titles[$role] ?? $this->translate(self::BaseRoles[$role] ?? $role);
		}

		return $roles;
	}


	/** @throws BadRequestException */
	private function getAcl(): Permission
	{
		$acl = $this->getPresenter()->getUser()->getAuthorizatorIfExists();
		return $acl instanceof Permission ? $acl : $this->error('The ACL is not a Nette\Security\Permission.', 500);
	}
}
