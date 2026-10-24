<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission;

use App\Presentation\Backend\BackendPresenter;
use App\Presentation\Backend\Permission\Component\Authorization\AuthorizationControl;
use App\Presentation\Backend\Permission\Component\Roles\RolesControl;
use App\Presentation\Backend\Permission\Component\Users\UsersControl;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Throwable;


class PermissionPresenter extends BackendPresenter
{
	public function __construct(
		private readonly UsersControl $usersControl,
		private readonly RolesControl $rolesControl,
		private readonly AuthorizationControl $authorizationControl,
	) {
		parent::__construct();
	}


	/**
	 * The module has no page of its own: its address opens the roles, or the users for a user who may only see those.
	 * Here, because Nette checks the requirements before the onStartup handlers, where the ACL check (no
	 * "default-read") runs; the opened page checks the access itself.
	 */
	public function checkRequirements(ReflectionClass|ReflectionMethod $element): void
	{
		if ($this->getAction() === 'default') {
			$user = $this->getUser();
			$this->redirect($user->isAllowed('Backend:Permission', 'users-read')
				&& !$user->isAllowed('Backend:Permission', 'roles-read') ? 'users' : 'roles');
		}

		parent::checkRequirements($element);
	}


	/** @throws Throwable|Exception */
	public function createComponentUsers(): UsersControl
	{
		$control = $this->usersControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	/** @throws Throwable|Exception */
	public function createComponentRoles(): RolesControl
	{
		$control = $this->rolesControl;
		$control->translator = $this->getTranslator();
		$control->permissionsDestination = 'permissions';
		return $control;
	}


	/** @throws Throwable|Exception */
	public function createComponentAuthorization(): AuthorizationControl
	{
		$control = $this->authorizationControl;
		$control->translator = $this->getTranslator();
		return $control;
	}
}
