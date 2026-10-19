<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use Dibi\DriverException;
use Dibi\Exception;
use Dibi\Result;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Tracy\Debugger;
use Tracy\ILogger;


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
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$items = $this->userRolesRepository->getUserRoles($id);
		$items ?: $this->error();

		$roleId = UsersValues::RoleId;
		$rolesIdList = array_column($items, $roleId, $roleId);

		$factory = $this->getComponent('users');
		$factory->setDefaults([
			UsersValues::Id => $id,
			UsersValues::UserId => $items[0]->user_id,
			UsersValues::RoleId => $rolesIdList,
		]);

		$sendControl = $this->getFormComponent($factory, 'send');
		$sendControl?->setCaption('Edit roles');

		$userIdControl = $this->getFormComponent($factory, UsersValues::UserId);
		$userIdControl?->setHtmlAttribute('data-locked');

		$this->redrawOffCanvas();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws InvalidColumnException
	 */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setRowClickAction('edit!');
		$grid->setDataSource($this->userRolesRepository->getAllUserRoles())
			->setPrimaryKey('id');

		$grid->addColumnText('username', 'User')
			->setFilterText();

		$grid->addColumnText('roles', 'Roles')
			->setFilterText();

		$user = $this->getPresenter()->getUser();

		if ($user->isAllowed('Backend:Permission', 'users-write')) {
			$grid->addAction(
				label: 'Edit',
				signal: 'edit!',
				class: 'ajax btn btn-xs btn-primary',
				callback: fn(int $id) => $this->handleEdit($id),
			);

			$grid->addAction(
				label: 'Delete',
				signal: 'delete!',
				class: 'ajax btn btn-xs btn-danger',
				callback: fn(int $id) => $this->handleDelete($id),
			);
		}

		return $grid;
	}


	/** @throws AttributeDetectionException */
	protected function createComponentUsers(): Form
	{
		$form = $this->factory->create();
		$users = $this->userRepository->getAllUsers();

		$form->addSelect(UsersValues::UserId, 'Name', $users)
			->setTranslator(null)
			->setPrompt($this->translate('Select user'))
			->setRequired('Please select a user.');

		$roles = $this->rolesRepository->getAllRoles();
		$form->addMultiSelect(UsersValues::RoleId, 'Role', $roles)
			->setHtmlAttribute('placeholder', $this->translate('Select role'))
			->setRequired('Please select a role.');

		$form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processUserRolesForm(...);
		return $form;
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	protected function getResultRepository(int $id): Result|int|null
	{
		return $this->userRolesRepository
			->delete(UsersRolesEntity::ColumnUserId, $id)
			->execute();
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	protected function getItemRepository(int $id): string|null
	{
		// The grid lists users, so the id is the user id (see getAllUserRoles()).
		return $this->userRepository->get($id)->record()?->username;
	}


	/**
	 * @throws DriverException
	 * @throws Exception
	 */
	private function processUserRolesForm(Form $form, UsersValues $values): void
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
				$repository->insert($entity->toArray())->execute();
			}

			$repository->commit();
			$message = $values->id > 0 ? 'Update successful.' : 'Insert successful.';
			$this->addFlashMessage($message, Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$form->reset();
			$this->closeComponent();
			$this->redrawControl();
			$this['dataGrid']->redrawDataGrid();

		} catch (\Throwable $e) {
			$repository->rollBack();
			Debugger::log($e, ILogger::EXCEPTION);
			$form->addError('Unknown status code.');
			$this->redrawOffCanvas();
		}
	}
}
