<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use App\Core\Permission\BaseControl;
use App\Core\Permission\Factory;
use App\Core\Permission\Roles\RolesRepository;
use Dibi\DriverException;
use Dibi\Exception;
use Dibi\Result;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Nette\Application\UI\Form;


class UsersControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly UserRepository $userRepository,
		private readonly UserRolesRepository $userRolesRepository,
		private readonly RolesRepository $rolesRepository,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Users.latte');
		$template->render();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	protected function createComponentUsers(): Form
	{
		$form = $this->factory->create();
		$users = $this->userRepository->getAllUsers();

		$form->addSelect(UsersValues::UserId, 'Name', $users)
			->setRequired('Please enter name.')
			->setPrompt('Select user');

		$roles = $this->rolesRepository->getAllRoles();
		$form->addMultiSelect(UsersValues::RoleId, 'Role', $roles)
			->setRequired('Please enter permissions.')
			->setHtmlAttribute('placeholder', 'Select role');

		$form->addHidden('id', $this->id)
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->success(...);
		return $form;
	}


	/**
	 * @throws DriverException
	 */
	private function success(Form $form, UsersValues $values): void
	{
		$repository = $this->userRolesRepository;

		try {
			$repository->beginTransaction();
			$repository->delete(UsersRolesEntity::ColumnUserId, $values->user_id)
				->execute();

			$entity = new UsersRolesEntity;
			foreach ($values->role_id as $role) {
				$entity->user_id = $values->user_id;
				$entity->role_id = $role;
				$repository->insert($entity)
					->execute();
			}

			$repository->commit();
			$message = $values->id > 0 ? 'Update successful.' : 'Insert successful.';
			$this->redrawFlashMessage($message, Alert::Success);

			$form->reset();
			$this->closeComponent();
			$this->redrawControl();

		} catch (\Throwable $e) {
			$repository->rollBack();
		}
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function handleEdit(int $id): void
	{
		$items = $this->userRolesRepository->getUserRoles($id);
		$items ?: $this->error();

		$roleId = UsersValues::RoleId;
		$rolesIdList = array_column($items, $roleId, $roleId);

		$factory = $this->getComponent('users');
		if ($factory instanceof Form) {
			$factory->setDefaults([
				UsersValues::UserId => $items[0]->user_id,
				UsersValues::RoleId => $rolesIdList,
			]);
		}

		$this->getFormComponent($factory, 'send')
			->setCaption('Edit roles');

		$this->getFormComponent($factory, UsersValues::UserId)
			->setHtmlAttribute('data-locked');

		$this->redrawOffCanvas();
	}


	protected function getResultRepository(int $id): Result|int|null
	{
		// TODO: Implement getResultRepository() method.
	}


	protected function getItemRepository(int $id): string|null
	{
		// TODO: Implement getItemRepository() method.
	}
}
