<?php

declare(strict_types=1);

namespace App\Core\Permissions\Access;

use App\Core\Permissions\BaseControl;
use App\Core\Permissions\BaseTemplate;
use App\Core\Permissions\Factory;
use App\Core\Permissions\Roles\RolesRepository;
use Dibi\Exception;
use Dibi\Result;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Nette\Application\UI\Form;
use Tracy\Debugger;


/**
 * @property-read BaseTemplate $template
 */
class AccessControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly SourceRepository $sourceRepository,
		private readonly RolesRepository $rolesRepository,
		private readonly AccessRepository $authorizationRepository,
	) {
		parent::__construct($this->factory);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws InvalidColumnException
	 */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setDataSource($this->authorizationRepository->getAll())
			->setPrimaryKey('id');

		$grid->addColumnText('id', 'ID');
		$grid->addColumnText('description', 'Description')
			->setFilterText();

		$grid->addAction(
			label: 'Edit',
			signal: 'edit!',
			class: 'ajax btn btn-xs btn btn-primary',
			callback: fn(int $id) => $this->handleEdit($id),
		);

		$grid->addAction(
			label: 'Delete',
			signal: 'delete!',
			class: 'ajax btn btn-xs btn-danger',
			callback: fn(int $id) => $this->handleDelete($id),
		);

		return $grid;
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Authorization.latte');
		$template->setTranslator($this->translator);
		$template->render();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	protected function createComponentAuthorization(): Form
	{
		$form = $this->factory->create();
		$roles = $this->rolesRepository->getAllRoles();
		$form->addSelect(AccessValues::RoleId, 'Role', $roles)
			->setRequired('Please select a role.')
			->setPrompt('Select role');

		$source = $this->sourceRepository->getAllSource();
		$form->addSelect(AccessValues::SourceId, 'Source', $source)
			->setRequired('Please select a source.')
			->setPrompt('Select source');

		$effect = [
			'allow' => 'allow',
			'deny' => 'deny',
		];

		$form->addSelect(AccessValues::Effect, 'Effect', $effect)
			->setDefaultValue($effect['allow'])
			->setRequired('Please select effect.')
			->setPrompt('Select effect');

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = function (Form $form, AccessValues $values): void {
			Debugger::barDump($values);
		};
		return $form;
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	public function handleEdit(int $id): void
	{
		$items = $this->authorizationRepository->get($id)->record();
		$items ?: $this->error();

		$factory = $this->getComponent('roles');
		if ($factory instanceof Form) {
			$factory->setDefaults($items);
		}

		$this->getFormComponent($factory, 'send')
			->setCaption('Edit roles');

		$this->redrawOffCanvas();
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	protected function getResultRepository(int $id): Result|int|null
	{
		return $this->authorizationRepository
			->delete(SourceEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	protected function getItemRepository(int $id): string
	{
		return $this->authorizationRepository->get($id)
			->record()?->description;
	}
}
