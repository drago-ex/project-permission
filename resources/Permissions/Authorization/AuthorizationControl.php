<?php

declare(strict_types=1);

namespace App\Core\Permissions\Authorization;

use App\Core\Permissions\BaseControl;
use App\Core\Permissions\Factory;
use App\Core\Permissions\Roles\RolesRepository;
use Dibi\Exception;
use Dibi\Result;
use Drago\Attr\AttributeDetectionException;
use Nette\Application\Attributes\Parameter;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;


/**
 * @property-read AuthorizationTemplate $template
 */
class AuthorizationControl extends BaseControl
{
	#[Parameter]
	public ?int $roleId = null;


	public function __construct(
		public Factory $factory,
		private readonly RolesRepository $rolesRepository,
		private readonly AuthorizationRepository $authorizationRepository,
	) {
		parent::__construct($this->factory);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Authorization.latte');
		$template->setTranslator($this->translator);

		if ($template instanceof AuthorizationTemplate) {
			$template->roles = $this->rolesRepository->getAllRoles();
			$template->roleId = $this->roleId;

			if ($this->roleId !== null) {
				$template->rolePermissions = $this->authorizationRepository
					->getRolePermissions($this->roleId);

				$template->allowedCount = count(array_filter(
					$template->rolePermissions,
					static fn(object $item): bool => $item->effective_access === 'allow',
				));

				$template->deniedCount = count($template->rolePermissions) - $template->allowedCount;
				$template->roleName = $this->rolesRepository->get($this->roleId)
					->record()->description;
			}
		}

		$template->render();
	}


	protected function createComponentRoleSwitch(): Form
	{
		$form = $this->factory->create();
		$form->addSelect('role_id', 'Role', $this->rolesRepository->getAllRoles());
		$form->addSubmit('send');
		$form->onAnchor[] = function () use ($form): void {
			$form->setDefaults([
				'role_id' => $this->roleId,
			]);
		};
		$form->onSuccess[] = function (Form $form): void {
			$values = $form->getValues('array');
			$roleId = (int) $values['role_id'];
			$this->roleId = $roleId;

			if ($this->getPresenter()->isAjax()) {
				$this->redrawControl('permissions');
				return;
			}

			$this->getPresenter()->redirect('this', [
				'authorization-roleId' => $roleId,
			]);
		};

		return $form;
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 */
	#[Requires(ajax: true)]
	public function handleTogglePermission(int $roleId, int $resourceId): void
	{
		$allowed = (int) $this->getPresenter()
			->getHttpRequest()
			->getPost('allowed');

		if ($allowed === 1) {
			$this->authorizationRepository
				->allow($roleId, $resourceId);

		} else {
			$this->authorizationRepository
				->deny($roleId, $resourceId);
		}

		$this->roleId = $roleId;
		$this->redrawControl('permissions');
	}


	protected function getResultRepository(int $id): Result|int|null
	{
		// TODO: Implement getResultRepository() method.
		return null;
	}


	protected function getItemRepository(int $id): string|null
	{
		// TODO: Implement getItemRepository() method.
		return null;
	}
}
