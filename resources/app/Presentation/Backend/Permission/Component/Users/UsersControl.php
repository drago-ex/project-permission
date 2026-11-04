<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Permission\Access;
use Drago\Permission\Role;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Security\Permission;
use Nette\Utils\Html;
use Tracy\Debugger;
use Tracy\ILogger;


/** The roles of the users: every user is listed, one without a role has the default role "user". */
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


	/** @throws Exception */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$this->getItemTitle($id) ?? $this->error();
		$form = $this->getComponent('users');
		$form->setDefaults([
			UsersValues::Id => $id,
			UsersValues::Roles => $this->userRolesRepository->getRoles($id),
		]);

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
		$grid->setDataSource($this->userRolesRepository->getAllUserRoles())
			->setPrimaryKey('id');

		$grid->addColumnText('username', 'User')
			->setFilterText();

		$grid->addColumnText('roles', 'Roles')
			->setFilterText();

		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
			->setVisible($this->isAllowed('edit'));
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id))
			->setVisible($this->isAllowed('delete'));

		return $grid;
	}


	#[Access('edit')]
	protected function createComponentUsers(): Form
	{
		$form = $this->factory->create();

		$form->addHidden(UsersValues::Id)
			->setRequired()
			->addRule($form::Integer);

		// the edited user, only shown: omitted from the values, so a changed value is never used
		$form->addTextInput(UsersValues::UserName, 'Name')
			->setReadOnly()
			->setOmitted();

		// role descriptions are data, not texts: wrapped in Html they are not translated
		$form->addMultiSelect(UsersValues::Roles, 'Role', array_map(
			fn(string $label): Html => Html::el()->setText($label),
			$this->getRoleOptions(),
		))
			->setHtmlAttribute('placeholder', $this->translate('Select role'));

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processUserRolesForm(...);
		// errors found on the server (also those added by processUserRolesForm()) are shown in the open offcanvas
		$form->onError[] = function (Form $form): void {
			$this->setEditMode($form);
			$this->redrawOffCanvas();
		};
		return $form;
	}


	/**
	 * Takes all roles from the user, who keeps the default role "user".
	 * @throws Exception
	 */
	protected function deleteItem(int $id): void
	{
		$this->userRolesRepository->setRoles($id, []);
	}


	/** @throws Exception */
	protected function getItemTitle(int $id): ?string
	{
		return $this->userRepository->table->get($id)->record()?->username;
	}


	/**
	 * The roles that can be given, by name: those of the ACL except the guest (custom ones with their description).
	 * @return array<string, string>
	 */
	private function getRoleOptions(): array
	{
		$acl = $this->getPresenter()->getUser()->getAuthorizatorIfExists();
		$descriptions = $this->rolesRepository->getDescriptions();
		$options = [];
		foreach ($acl instanceof Permission ? $acl->getRoles() : [] as $role) {
			if ($role !== Role::Guest) {
				$options[$role] = $descriptions[$role] ?? $role;
			}
		}

		return $options;
	}


	/**
	 * The form edits the roles of one user, shown readonly. Also after a server error, when the form is drawn again
	 * from the submitted values.
	 * @throws Exception
	 */
	private function setEditMode(Form $form): void
	{
		$this->getFormComponent($form, 'send')?->setCaption('Edit roles');
		$id = (int) $form->getComponent(UsersValues::Id)->getValue();
		$this->getFormComponent($form, UsersValues::UserName)?->setValue($this->getItemTitle($id));
	}


	/** @throws InvalidLinkException */
	private function processUserRolesForm(Form $form, UsersValues $values): void
	{
		try {
			$this->userRolesRepository->setRoles($values->id, $values->roles);

		} catch (\Throwable $e) {
			Debugger::log($e, ILogger::EXCEPTION);
			$form->addError('Unknown status code.');
			return;
		}

		$this->addFlashMessage('Update successful.', Alert::Success);
		$this->addRedraw($this->snippetMessage);

		$form->reset();
		$this->closeComponent();
		$this['dataGrid']->redrawDataGrid();
	}
}
