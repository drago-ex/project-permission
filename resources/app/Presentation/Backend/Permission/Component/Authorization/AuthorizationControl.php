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
use Nette\Application\Attributes\Persistent;
use Nette\Application\Attributes\Requires;
use Nette\Application\BadRequestException;
use Nette\Security\Permission;
use Throwable;


/**
 * The privileges of one role: those of every presenter of the ACL, read from the code (PrivilegeList). A privilege
 * set here can be toggled; one allowed by the neon files or a parent role is shown as inherited and locked.
 * @property-read AuthorizationTemplate $template
 */
class AuthorizationControl extends BaseControl
{
	#[Persistent]
	public ?string $role = null;

	/** The presenter whose privileges are shown; the first one of the ACL when not set. */
	#[Persistent]
	public ?string $resource = null;


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
		$template->roles = $roles = $this->getRoles();
		// without the privilege the toggles are shown, but cannot be clicked
		$template->writeAllowed = $this->isAllowed('edit');

		$role = $this->role;
		if ($role === null || !isset($roles[$role])) {
			$template->render();
			return;
		}

		$acl = $this->getAcl();
		$own = $this->authorizationRepository->getPrivileges($role);
		$template->role = $role;
		$template->roleName = $roles[$role];
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


	/** Shows the privileges of another presenter; the page is enough. */
	#[Access]
	public function handleResource(): void
	{
		$this->redrawControl('permissions');
	}


	/**
	 * Allows or denies a privilege of the selected role; only a privilege of the code of a presenter of the ACL.
	 * @throws BadRequestException
	 * @throws Exception
	 * @throws Throwable
	 */
	#[Access('edit')]
	#[Requires(ajax: true)]
	public function handleTogglePermission(string $resource, string $privilege): void
	{
		$role = (string) $this->role;
		$acl = $this->getAcl();
		if (
			$role === Role::Admin
			|| !isset($this->getRoles()[$role])
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
	 * The roles of the ACL by name: the base ones with a translated label, the custom ones with their description.
	 * @return array<string, string>
	 * @throws BadRequestException
	 */
	private function getRoles(): array
	{
		$labels = [Role::Guest => 'Guest', Role::User => 'User', Role::Admin => 'Administrator'];
		$descriptions = $this->rolesRepository->getDescriptions();
		$roles = [];
		foreach ($this->getAcl()->getRoles() as $role) {
			$roles[$role] = $descriptions[$role] ?? $this->translate($labels[$role] ?? $role);
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
