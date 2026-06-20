<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission;

use App\Presentation\Backend\BackendPresenter;
use App\Presentation\Backend\Permission\Component\Authorization\AuthorizationControl;
use App\Presentation\Backend\Permission\Component\Roles\RolesControl;
use App\Presentation\Backend\Permission\Component\Users\UsersControl;
use Exception;
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
