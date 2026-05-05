<?php

declare(strict_types=1);

namespace App\UI\Backend\AccessControl;

use App\Core\Permissions\Authorization\AuthorizationControl;
use App\Core\Permissions\Roles\RolesControl;
use App\Core\Permissions\Users\UsersControl;
use App\UI\BasePresenter;
use Exception;
use Throwable;


class AccessControlPresenter extends BasePresenter
{
	public function __construct(
		private readonly UsersControl $usersControl,
		private readonly RolesControl $rolesControl,
		private readonly AuthorizationControl $authorizationControl,
	) {
		parent::__construct();
	}


	/**
	 * @throws Throwable
	 * @throws Exception
	 */
	public function createComponentUsers(): UsersControl
	{
		$control = $this->usersControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	/**
	 * @throws Throwable
	 * @throws Exception
	 */
	public function createComponentRoles(): RolesControl
	{
		$control = $this->rolesControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	/**
	 * @throws Exception
	 * @throws Throwable
	 */
	public function createComponentAuthorization(): AuthorizationControl
	{
		$control = $this->authorizationControl;
		$control->translator = $this->getTranslator();
		return $control;
	}
}
