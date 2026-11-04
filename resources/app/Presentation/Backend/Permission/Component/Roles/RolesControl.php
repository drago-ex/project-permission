<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\RoleRules;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Form\Autocomplete;
use Drago\Permission\Access;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Security\Permission;
use RuntimeException;
use Tracy\Debugger;
use Tracy\ILogger;


/** The custom roles; the base roles (guest, user, admin) of the ACL neon files are not listed. */
class RolesControl extends BaseControl
{
	public string $permissionsDestination = 'permissions';


	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
		private readonly RoleRules $roleRules,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Roles.latte');
		$template->render();
	}


	/**
	 * Opens the permissions of the role; that page checks its own privilege.
	 * @throws Exception
	 */
	#[Access]
	public function handlePermissions(int $id): void
	{
		$role = $this->rolesRepository->table->get($id)->record() ?? $this->error();
		$this->addRedirect($this->permissionsDestination, [
			'authorization-role' => $role->name,
		]);
	}


	/** @throws Exception */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$role = $this->rolesRepository->table->get($id)->record() ?? $this->error();
		$form = $this->getComponent('roles');
		$form->setDefaults($role);
		$this->setEditMode($form);
		$this->redrawOffCanvas();
	}


	/**
	 * @throws InvalidColumnException
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

		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addAction(
			name: 'permissions',
			label: 'Permissions',
			callback: fn(int $id) => $this->handlePermissions($id),
			class: 'btn btn-xs btn-secondary',
		)->setVisible($this->isAllowed('permissions'));
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
			->setVisible($this->isAllowed('edit'));
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id))
			->setVisible($this->isAllowed('delete'));

		return $grid;
	}


	#[Access('edit')]
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
			if ((int) $form->getComponent('id')->getValue() > 0) {
				$this->setEditMode($form);
			}

			$this->redrawOffCanvas();
		};
		return $form;
	}


	/** @throws Exception */
	protected function deleteItem(int $id): void
	{
		$role = $this->rolesRepository->table->get($id)->record();
		if ($role !== null) {
			$this->rolesRepository->deleteRole($role->name);
			$this->roleRules->clean();
		}
	}


	/** @throws Exception */
	protected function getItemTitle(int $id): ?string
	{
		return $this->rolesRepository->table->get($id)->record()?->description;
	}


	/**
	 * The form edits a role: its name is the key of the user roles and the privileges, so it cannot be changed.
	 * Also after a server error, when the form is drawn again from the submitted values.
	 */
	private function setEditMode(Form $form): void
	{
		$this->getFormComponent($form, RolesValues::Name)?->setHtmlAttribute('readonly');
		$this->getFormComponent($form, 'send')?->setCaption('Edit roles');
	}


	private function processRoleForm(Form $form, RolesValues $values): void
	{
		try {
			if ($values->id > 0) {
				// the name is kept, whatever the request sends
				$original = $this->rolesRepository->table->get($values->id)->record()
					?? throw new RuntimeException('The role does not exist.', 1);
				$values->name = $original->name;

			} elseif ($this->isKnownRole($values->name)) {
				// a base role or a role of the neon files would be shadowed
				throw new RuntimeException('This role already exists.', 1);
			}

			$message = $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			$this->rolesRepository->table->save($values);
			$this->roleRules->clean();
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


	private function isKnownRole(string $name): bool
	{
		$acl = $this->getPresenter()->getUser()->getAuthorizatorIfExists();
		return $acl instanceof Permission && $acl->hasRole($name);
	}
}
