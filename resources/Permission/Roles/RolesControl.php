<?php

declare(strict_types=1);

namespace App\Core\Permission\Roles;

use App\Core\Permission\BaseControl;
use App\Core\Permission\BaseTemplate;
use App\Core\Permission\Control;
use App\Core\Permission\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Component\ModalHandle;
use Drago\Component\OffcanvasHandle;
use Drago\Form\Autocomplete;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;
use Tracy\Debugger;


/**
 * @property-read BaseTemplate $template
 */
class RolesControl extends BaseControl implements Control, OffcanvasHandle, ModalHandle
{
	#[Parameter]
	public ?int $id = null;


	public function __construct(
		private readonly Factory $factory,
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

			Debugger::barDump($values);

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


	public function handleDelete(int $id): void
	{
		// TODO: Implement handleDelete() method.
	}


	#[Requires(ajax: true)]
	public function handleOpenModal(): void
	{
		// TODO: Implement handleOpenModal() method.
	}


	#[Requires(ajax: true)]
	public function handleOpenOffcanvas(): void
	{
		$this->redrawOffCanvas();
	}
}
