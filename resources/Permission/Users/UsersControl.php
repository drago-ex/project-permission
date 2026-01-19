<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission\Users;

use App\Core\Permission\BaseControl;
use App\Core\Permission\BaseTemplate;
use App\Core\Permission\Control;
use App\Core\Permission\Factory;
use App\Core\Permission\Roles\RolesRepository;
use Dibi\DriverException;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Component\ModalHandle;
use Drago\Component\OffcanvasHandle;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;


/**
 * @property-read  BaseTemplate $template
 */
class UsersControl extends BaseControl implements Control, OffcanvasHandle, ModalHandle
{
	#[Parameter]
	public int $id = 0;


	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly UserRolesRepository $userRolesRepository,
		private readonly RolesRepository $rolesRepository,
		private readonly Factory $factory,
	) {
	}


	public function render(): void
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/Users.latte');
		$template->setTranslator($this->translator);
		$template->offcanvasId = $this->getUniqueIdComponent(self::Offcanvas);
		$template->render();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function createComponentRoles(): Form
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
	public function success(Form $form, UsersValues $values): void
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

		$factory = $this->getComponent('roles');
		$factory->setDefaults([
			UsersValues::UserId => $items[0]->user_id,
			UsersValues::RoleId => $rolesIdList,
		]);

		$this->getFormComponent($factory, 'send')
			->setCaption('Edit roles');

		$this->getFormComponent($factory, UsersValues::UserId)
			->setHtmlAttribute('data-locked');

		$this->redrawOffCanvas();
	}


	public function handleDelete(int $id): void
	{
		// TODO: Implement handleDelete() method.
	}


	#[Requires(ajax: true)]
	public function handleOpenModal(): void
	{
		// TODO: Implement handleOpenModal() method.
	}


	#[Requires(ajax: true)]
	public function handleOpenOffcanvas(): void
	{
		$this->redrawOffCanvas();
	}
}
