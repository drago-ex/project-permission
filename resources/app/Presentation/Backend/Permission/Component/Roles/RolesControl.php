<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Form\Autocomplete;
use Drago\Permission\Role;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use RuntimeException;


class RolesControl extends BaseControl
{
	public string $permissionsDestination = 'permissions';


	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Roles.latte');
		$template->render();
	}


	public function handlePermissions(int $id): void
	{
		$this->addRedirect($this->permissionsDestination, [
			'authorization-roleId' => $id,
		]);
	}


	/**
	 * Handles roles edit.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$items = $this->rolesRepository->get($id)->record();
		if ($items === null) {
			$this->error();
		}

		$factory = $this->getComponent('roles');
		$factory->setDefaults($items);

		if ($this->isSystemRole($items->name)) {
			$nameControl = $this->getFormComponent($factory, RolesValues::Name);
			$nameControl?->setHtmlAttribute('readonly');
		}

		$sendControl = $this->getFormComponent($factory, 'send');
		$sendControl?->setCaption('Edit roles');

		$this->redrawOffCanvas();
	}


	/** @throws AttributeDetectionException|InvalidColumnException */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setDataSource($this->rolesRepository->getRolesFluent())
			->setPrimaryKey('id');

		$grid->addColumnText('description', 'Role description')
			->setFilterText();

		$grid->addColumnText('name', 'System name')
			->setFilterText();

		if ($this->isWriteAllowed()) {
			// A click on the row opens the form, the Edit button is then hidden by the grid.
			$grid->setRowClickAction('edit');
			$grid->addAction(
				name: 'permissions',
				label: 'Permissions',
				callback: fn(int $id) => $this->handlePermissions($id),
				class: 'btn btn-xs btn-secondary',
			);
			$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
			$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));
		}

		return $grid;
	}


	protected function createComponentRoles(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(RolesValues::Name, 'Role name')
			->setPlaceholder('e.g. member, editor')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter role name.')
			->addRule($form::Pattern, 'Only lowercase letters, numbers and hyphens.', '[a-z0-9-]+');

		$form->addTextInput(RolesValues::Description, 'Description role')
			->setPlaceholder('Description role')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter description role.');

		$form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processRoleForm(...);
		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function deleteItem(int $id): void
	{
		// Checked again here: the delete form posts the id, so the check when opening the dialog is not enough.
		$role = $this->rolesRepository->get($id)->record();
		if ($role !== null && $this->isSystemRole($role->name)) {
			throw new RuntimeException('System role cannot be deleted.', 1);
		}

		$this->rolesRepository
			->delete(RolesEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getItemTitle(int $id): ?string
	{
		$role = $this->rolesRepository->get($id)->record();

		if ($role !== null && $this->isSystemRole($role->name)) {
			$this->error('System role cannot be deleted.', 403);
		}

		return $role?->description;
	}


	private function processRoleForm(Form $form, RolesValues $values): void
	{
		try {
			if ($values->id > 0) {
				$original = $this->rolesRepository->get($values->id)->record();
				if ($original !== null && $this->isSystemRole($original->name)) {
					$values->name = $original->name;
				}
			}

			$message = $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			$this->rolesRepository->save($values);
			$this->addFlashMessage($message, Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$form->reset();
			$this->closeComponent();
			$this['dataGrid']->redrawDataGrid();

		} catch (\Throwable $e) {
			$message = match ($e->getCode()) {
				1 => $e->getMessage(),
				1062 => 'This role already exists.',
				default => 'Unknown status code.',
			};

			$form->addError($message);
			$this->redrawOffCanvas();
		}
	}


	private function isSystemRole(string $name): bool
	{
		return in_array($name, [Role::RoleAdmin, Role::RoleUser, Role::RoleGuest], true);
	}
}
