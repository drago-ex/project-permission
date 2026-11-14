<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Roles;

use App\Presentation\Backend\Permission\Component\Authorization\AuthorizationControl;
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
use Nette\Application\UI\InvalidLinkException;
use Nette\Security\Permission;
use Nette\Utils\Html;
use RuntimeException;
use Tracy\Debugger;
use Tracy\ILogger;


/**
 * The custom roles; the base roles (guest, user, admin) of the ACL neon files are not listed. The privileges
 * of a role open in the offcanvas of the component "authorization".
 */
class RolesControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
		private readonly RoleRules $roleRules,
		private readonly AuthorizationControl $authorizationControl,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
		assert($template instanceof RolesTemplate);
		$template->permissionsAllowed = $this->isAllowed('permissions');
		$template->baseRoles = AuthorizationControl::BaseRoles;
		$template->setFile(__DIR__ . '/Roles.latte');
		$template->render();
	}


	/**
	 * Activates or deactivates a role (the menu of the state in the grid); the privileges of a role that is not
	 * active do not apply.
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(methods: 'POST', ajax: true)]
	public function handleActivate(int $id, bool $active = true): void
	{
		$this->rolesRepository->setActive($id, $active);
		$this->roleRules->clean();
		$this->addFlashMessage($active ? 'The role has been activated.' : 'The role has been deactivated.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this['dataGrid']->redrawDataGrid();
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
		$grid->setFilterMode('inline');
		$grid->setDataSource($this->rolesRepository->getRolesFluent())
			->setPrimaryKey('id');

		$grid->addColumnText(RolesEntity::ColumnTitle, 'Role')
			->setFilterText();

		$grid->addColumnText(RolesEntity::ColumnName, 'Slug')
			->setFilterText();

		// a role has its privileges: the shield opens them in the offcanvas
		if ($this->isAllowed('permissions')) {
			$grid->addColumnText(
				RolesEntity::PrimaryKey,
				'Permissions',
				sortable: false,
				formatter: fn(mixed $value, array $row): Html => $this->formatPermissions((string) $row[RolesEntity::ColumnName]),
			)->setExportable(false);
		}

		$grid->addColumnText(RolesEntity::ColumnDescription, 'Description', sortable: false)
			->setFilterText();

		// the state is a badge that opens a menu of the states; without the privilege it is only read
		$grid->addColumnText(RolesEntity::ColumnActive, 'State')
			->setState(
				'activate',
				fn(int $id, int|string $active) => $this->handleActivate($id, $active === 1),
				[
					1 => ['Active', 'success'],
					0 => ['Inactive', 'secondary'],
				],
				enabled: $this->isAllowed('activate'),
			)
			->setFilterSelect([1 => 'Active', 0 => 'Inactive']);

		// The actions stand in the bar under the table and work with the rows chosen by their checkboxes
		// or by a click anywhere on a row; Edit is for one row.
		$grid->setRowClickAction('select');
		$grid->addBulkActionEdit(fn(int $id) => $this->handleEdit($id))
			->setVisible($this->isAllowed('edit'));
		$grid->addBulkActionExport()
			->setVisible($this->isAllowed('export'));
		$grid->addBulkActionDelete(fn(array $ids) => $this->handleDelete($ids))
			->setVisible($this->isAllowed('delete'));

		// "Export" under the table: all rows of the current filters
		$grid->setExport('roles.csv', button: $this->isAllowed('export'));

		return $grid;
	}


	protected function createComponentAuthorization(): AuthorizationControl
	{
		$control = $this->authorizationControl;
		$control->translator = $this->translator;
		return $control;
	}


	#[Access('edit')]
	protected function createComponentRoles(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(RolesValues::Name, 'Role name')
			->setPlaceholder('e.g. member, editor')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter role name.')
			->addRule($form::Pattern, 'Only lowercase letters, numbers and hyphens.', '[a-z0-9\-]+')
			->addRule($form::MaxLength, 'The role name can have at most %d characters.', RolesEntity::ColumnNameSize);

		$form->addTextInput(RolesValues::Title, 'Role title')
			->setPlaceholder('e.g. Editor')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter the title of the role.')
			->addRule($form::MaxLength, 'The title can have at most %d characters.', RolesEntity::ColumnTitleSize);

		$form->addTextInput(RolesValues::Description, 'Description')
			->setPlaceholder('What the role is for')
			->setAutocomplete(Autocomplete::Off)
			->setNullable()
			->addRule($form::MaxLength, 'The description can have at most %d characters.', RolesEntity::ColumnDescriptionSize);

		$form->addCheckbox(RolesValues::Active, 'Active')
			->setDefaultValue(true);

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
		return $this->rolesRepository->table->get($id)->record()?->title;
	}


	/**
	 * The cell of the permissions in the grid: a shield that opens the privileges of the role.
	 * @throws InvalidLinkException
	 */
	private function formatPermissions(string $role): Html
	{
		return Html::el('a', [
			'href' => $this->link('authorization:open!', ['role' => $role]),
			'class' => 'ajax btn btn-xs btn-icon btn-subtle theme-primary',
			'data-naja-history' => 'off',
			'data-tooltip' => true,
			'aria-label' => $this->translate('Set the permissions of the role'),
		])->addHtml(Html::el('i', ['class' => 'bi bi-shield', 'aria-hidden' => 'true']));
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
