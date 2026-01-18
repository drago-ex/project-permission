<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace App\Core\Permission;

use Dibi\DriverException;
use Dibi\Exception;
use Drago\Application\UI\ExtraControl;
use Drago\Attr\AttributeDetectionException;
use Drago\Component\Component;
use Drago\Component\OffcanvasHandle;
use Drago\Form\Forms;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;


/**
 * @property-read  RolesTemplate $template
 */
class RolesControl extends ExtraControl implements OffcanvasHandle
{
	use Component;

	#[Parameter]
	public int $id = 0;


	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly UserRolesRepository $userRolesRepository,
		private readonly RolesRepository $rolesRepository,
	) {
	}


	public function render(): void
	{
		$template = $this->template;
		$template->setFile(__DIR__ . '/Roles.latte');
		$template->setTranslator($this->translator);
		$template->offcanvasId = $this->getUniqueIdComponent(self::Offcanvas);
		$template->render();
	}


	private function offCanvas(): void
	{
		$this->offCanvasComponent(self::Offcanvas);
		$this->redrawControl();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function createComponentRoles(): Form
	{
		$form = new Forms;
		$form->setTranslator($this->translator);
		$users = $this->userRepository->getAllUsers();

		$form->addSelect(RolesValues::UserId, 'Name', $users)
			->setRequired('Please enter name.')
			->setPrompt('Select user');

		$roles = $this->rolesRepository->getAllRoles();
		$form->addMultiSelect(RolesValues::RoleId, 'Role', $roles)
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
	public function success(Form $form, RolesValues $values): void
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
			$this->getPresenter()->flashMessage($message);
			$this->getPresenter()->redrawControl('message');

			$form->reset();
			$this->closeComponent();
			$this->redrawControl();

		} catch (\Throwable $e) {
			$repository->rollBack();
		}
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$items = $this->userRolesRepository->getUserRoles($id);
		$items ?: $this->error();

		$roleId = RolesValues::RoleId;
		$rolesIdList = array_column($items, $roleId, $roleId);

		$factory = $this->getComponent('roles');
		$factory->setDefaults([
			RolesValues::UserId => $items[0]->user_id,
			RolesValues::RoleId => $rolesIdList,
		]);

		$this->getFormComponent($factory, 'send')
			->setCaption('Edit roles');

		$this->getFormComponent($factory, RolesValues::UserId)
			->setHtmlAttribute('data-locked');

		$this->offCanvas();
	}


	#[Requires(ajax: true)]
	public function handleOpenOffcanvas(): void
	{
		$this->offCanvas();
	}
}
