<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Authorization;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use Dibi\Exception;
use Dibi\Row;
use Drago\Attr\AttributeDetectionException;
use Drago\Permission\Role;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\InvalidStateException;
use Nette\Security\Permission;


/** @property-read AuthorizationTemplate $template */
class AuthorizationControl extends BaseControl
{
	#[Parameter]
	public ?int $roleId = null;


	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
		private readonly AuthorizationRepository $authorizationRepository,
	) {
		parent::__construct($this->factory);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function render(): void
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/Authorization.latte');
		$template->setTranslator($this->translator);
		$template->roles = $this->rolesRepository->getAllRoles();
		$template->roleId = $this->roleId ?? 0;
		// without the write privilege the toggles are shown, but cannot be clicked
		$template->writeAllowed = $this->isWriteAllowed();

		if ($this->roleId !== null) {
			$role = $this->rolesRepository->get($this->roleId)->record();

			if ($role === null) {
				$template->render();
				return;
			}

			$isAdminRole = $role->name === Role::RoleAdmin;
			$template->roleName = $role->description;
			$template->isAdminRole = $isAdminRole;

			$rolePermissions = $this->authorizationRepository->getRolePermissions($this->roleId);
			$template->groupedPermissions = $this->groupPermissionsByResource($rolePermissions);
			$template->inherited = $inherited = $isAdminRole ? [] : $this->getInheritedPrivileges($role->name, $rolePermissions);

			if ($isAdminRole) {
				$template->allowedCount = count($rolePermissions);
				$template->deniedCount = 0;
			} else {
				$template->allowedCount = count(array_filter(
					$rolePermissions,
					static fn(Row $item): bool => $item['access'] === 'allow' || isset($inherited[(int) $item['id']]),
				));
				$template->deniedCount = count($rolePermissions) - $template->allowedCount;
			}
		}

		$template->render();
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	#[Requires(ajax: true)]
	public function handleTogglePermission(int $roleId, int $resourceId): void
	{
		$allowed = (int) $this->getPresenter()
			->getHttpRequest()
			->getPost('allowed');

		if ($allowed === 1) {
			$this->authorizationRepository->allow($roleId, $resourceId);
		} else {
			$this->authorizationRepository->deny($roleId, $resourceId);
		}

		$this->roleId = $roleId;
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
	 * Privileges a parent role (e.g. "user", which every custom role inherits) already allows; the role cannot be
	 * denied them, so they are shown as inherited instead of a toggle. The parents are asked, not the role itself,
	 * so a toggle of the role in this request does not matter.
	 * @param Row[] $permissions
	 * @return array<int, true> by resource id
	 */
	private function getInheritedPrivileges(string $roleName, array $permissions): array
	{
		$acl = $this->getPresenter()->getUser()->getAuthorizator();
		if (!$acl instanceof Permission || !$acl->hasRole($roleName)) {
			return [];
		}

		$inherited = [];
		foreach ($permissions as $permission) {
			foreach ($acl->getRoleParents($roleName) as $parent) {
				try {
					if ($acl->isAllowed($parent, (string) $permission['resource'], (string) $permission['privilege'])) {
						$inherited[(int) $permission['id']] = true;
						break;
					}
				} catch (InvalidStateException) {
					// a resource of the table that no provider registers is not allowed to anybody
				}
			}
		}

		return $inherited;
	}


	/**
	 * @param Row[] $permissions
	 * @return array<string, Row[]>
	 */
	private function groupPermissionsByResource(array $permissions): array
	{
		$groupedPermissions = [];
		foreach ($permissions as $permission) {
			$resource = (string) $permission['resource'];
			$groupedPermissions[$resource][] = $permission;
		}

		return $groupedPermissions;
	}
}
