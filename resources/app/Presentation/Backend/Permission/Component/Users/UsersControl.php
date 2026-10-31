<?php declare(strict_types=1);

namespace App\Presentation\Backend\Permission\Component\Users;

use App\Presentation\Backend\Permission\Component\BaseControl;
use App\Presentation\Backend\Permission\Component\Factory;
use App\Presentation\Backend\Permission\Component\Roles\RolesRepository;
use Dibi\DriverException;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
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
			UsersValues::UserId => $items[0]->userId,
			UsersValues::RoleId => $rolesIdList,
		]);

		$this->setEditMode($factory);
		$this->redrawOffCanvas();
	}


	/**
	 * @throws AttributeDetectionException
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

		$writeAllowed = $this->isWriteAllowed();
		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
			->setVisible($writeAllowed);
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id))
			->setVisible($writeAllowed);

		return $grid;
	}


	/** @throws AttributeDetectionException */
	protected function createComponentUsers(): Form
	{
		$form = $this->factory->create();
		// User names and role descriptions are data, not texts: wrapped in Html they are not translated,
		// the captions and messages still are.
		$data = fn(array $items): array => array_map(fn(string $name): Html => Html::el()->setText($name), $items);

		$id = $form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		// an edit saves the roles of the edited id and shows the user readonly instead of the select, which is not sent then
		$form->addSelect(UsersValues::UserId, 'Name', $data($this->userRepository->getAllUsers()))
			->setPrompt('Select user')
			->addConditionOn($id, $form::Blank)
				->setRequired('Please select a user.');

		// the user of an edit, only shown: omitted from the values, so a changed value is never used
		$form->addTextInput(UsersValues::UserName, 'Name')
			->setReadOnly()
			->setOmitted();

		$form->addMultiSelect(UsersValues::RoleId, 'Role', $data($this->rolesRepository->getAllRoles()))
			->setHtmlAttribute('placeholder', $this->translate('Select role'))
			->setRequired('Please select a role.');

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processUserRolesForm(...);
		// errors found on the server (also those added by processUserRolesForm()) are shown in the open offcanvas
		$form->onError[] = function (Form $form): void {
			if ((int) $form->getComponent(UsersValues::Id)->getValue() > 0) {
				$this->setEditMode($form);
			}

			$this->redrawOffCanvas();
		};
		return $form;
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	protected function deleteItem(int $id): void
	{
		$this->userRolesRepository
			->delete(UsersRolesEntity::ColumnUserId, $id)
			->execute();
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	protected function getItemTitle(int $id): ?string
	{
		// The grid lists users, so the id is the user id (see getAllUserRoles()).
		return $this->userRepository->get($id)->record()?->username;
	}


	/**
	 * The form edits the roles of one user: the template shows the user readonly (the save takes the edited id
	 * anyway). Also after a server error, when the form is drawn again from the submitted values.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function setEditMode(Form $form): void
	{
		$this->getFormComponent($form, 'send')?->setCaption('Edit roles');
		$id = (int) $form->getComponent(UsersValues::Id)->getValue();
		$this->getFormComponent($form, UsersValues::UserName)?->setValue($this->getItemTitle($id));
	}


	/**
	 * @throws DriverException
	 * @throws Exception
	 */
	private function processUserRolesForm(Form $form, UsersValues $values): void
	{
		$repository = $this->userRolesRepository;

		// an edit changes the edited user (shown readonly, the select is not sent), never a userId of the request;
		// a new one has the user required by the form
		$userId = (int) $values->id > 0 ? (int) $values->id : (int) $values->userId;

		try {
			$repository->transaction(function () use ($repository, $values, $userId): void {
				$repository->delete(UsersRolesEntity::ColumnUserId, $userId)
					->execute();

				$entity = new UsersRolesEntity;
				foreach ($values->roleId as $role) {
					$entity->userId = $userId;
					$entity->roleId = $role;
					$repository->insert($entity)->execute();
				}
			});

		} catch (\Throwable $e) {
			Debugger::log($e, ILogger::EXCEPTION);
			$form->addError('Unknown status code.');
			return;
		}

		$message = $values->id > 0 ? 'Update successful.' : 'Insert successful.';
		$this->addFlashMessage($message, Alert::Success);
		$this->addRedraw($this->snippetMessage);

		$form->reset();
		$this->closeComponent();
		$this['dataGrid']->redrawDataGrid();
	}
}
