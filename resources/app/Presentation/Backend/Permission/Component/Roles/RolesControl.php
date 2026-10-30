<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Form\Autocomplete;
use Drago\Permission\Role;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use RuntimeException;
use Tracy\Debugger;
use Tracy\ILogger;


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
		$this->setEditMode($factory, $items->name);
		$this->redrawOffCanvas();
	}


	/**
	 * @throws AttributeDetectionException|InvalidColumnException
	 * @throws InvalidConfigurationException
	 */
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

		$writeAllowed = $this->isWriteAllowed();
		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addAction(
			name: 'permissions',
			label: 'Permissions',
			callback: fn(int $id) => $this->handlePermissions($id),
			class: 'btn btn-xs btn-secondary',
		)->setVisible($writeAllowed);
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
			->setVisible($writeAllowed);
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id))
			->setVisible($writeAllowed);

		return $grid;
	}


	protected function createComponentRoles(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(RolesValues::Name, 'Role name')
			->setPlaceholder('e.g. member, editor')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter role name.')
			->addRule($form::Pattern, 'Only lowercase letters, numbers and hyphens.', '[a-z0-9-]+')
			->addRule($form::MaxLength, 'The role name can have at most %d characters.', RolesEntity::ColumnNameSize);

		$form->addTextInput(RolesValues::Description, 'Description role')
			->setPlaceholder('Description role')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter description role.')
			->addRule($form::MaxLength, 'The description can have at most %d characters.', RolesEntity::ColumnDescriptionSize);

		$form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processRoleForm(...);
		// errors found on the server (also those added by processRoleForm()) are shown in the open offcanvas
		$form->onError[] = function (Form $form): void {
			$role = $this->rolesRepository->get((int) $form->getComponent('id')->getValue())->record();
			if ($role !== null) {
				$this->setEditMode($form, $role->name);
			}

			$this->redrawOffCanvas();
		};
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


	/**
	 * The form edits a role: the name of a system role cannot be changed. Also after a server error,
	 * when the form is drawn again from the submitted values.
	 */
	private function setEditMode(Form $form, string $name): void
	{
		if ($this->isSystemRole($name)) {
			$this->getFormComponent($form, RolesValues::Name)?->setHtmlAttribute('readonly');
		}

		$this->getFormComponent($form, 'send')?->setCaption('Edit roles');
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
			// code 1 is a message for the user, 1062 a duplicate role; anything else is unexpected and gets logged
			$form->addError(match ($e->getCode()) {
				1 => $e->getMessage(),
				1062 => 'This role already exists.',
				default => $this->logUnexpected($e),
			});
		}
	}


	/** Logs an exception the user cannot fix and returns the general message for the form. */
	private function logUnexpected(\Throwable $e): string
	{
		Debugger::log($e, ILogger::EXCEPTION);
		return 'Unknown status code.';
	}


	private function isSystemRole(string $name): bool
	{
		return in_array($name, [Role::RoleAdmin, Role::RoleUser, Role::RoleGuest], true);
	}
}
