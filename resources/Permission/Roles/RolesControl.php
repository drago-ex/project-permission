<?php

declare(strict_types=1);

namespace App\Core\Permission\Roles;

use App\Core\Permission\BaseControl;
use App\Core\Permission\Factory;
use Dibi\Exception;
use Dibi\Result;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Form\Autocomplete;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;


class RolesControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
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
		$grid->setDataSource($this->rolesRepository->getRolesFluent())
			->setPrimaryKey('id');

		$grid->addColumnText('id', 'ID')
			->setFilterText();

		$grid->addColumnText('description', 'Description')
			->setFilterText()
			->setNaturalSort();

		$grid->addAction('Edit', 'edit!', 'ajax btn btn-xs btn btn-primary',
			callback: fn (int $id) => $this->handleEdit($id),
		);

		$grid->addAction('Delete', 'delete!', 'ajax btn btn-xs btn-danger',
			callback: fn (int $id) => $this->handleDelete($id),
		);

		return $grid;
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Roles.latte');
		$template->render();
	}


	protected function createComponentRoles(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(RolesValues::Description, 'Description role')
			->setPlaceholder('Description role')
			->setRequired('Please enter description role.')
			->setAutocomplete(Autocomplete::Off);

		$form->addHidden('id', $this->id)
			->addRule($form::Integer)
			->setNullable();

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->success(...);
		return $form;
	}


	private function success(Form $form, RolesValues $values): void
	{
		try {
			$values->name = Strings::webalize($values->description);
			$message = $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			$this->rolesRepository->save($values);
			$this->redrawFlashMessage($message, Alert::Success);

			$form->reset();
			$this->closeComponent();
			$this->redrawControl();

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


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$items = $this->rolesRepository->get($id)->record();
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
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getResultRepository(int $id): Result|int|null
	{
		return $this->rolesRepository->delete(RolesEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getItemRepository(int $id): string|null
	{
		return $this->rolesRepository->get($id)
			->record()?->description;
	}
}
