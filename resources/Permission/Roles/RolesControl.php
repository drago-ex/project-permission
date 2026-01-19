<?php

declare(strict_types=1);

namespace App\Core\Permission\Roles;

use App\Core\Permission\BaseControl;
use App\Core\Permission\BaseTemplate;
use App\Core\Permission\Control;
use Drago\Component\ModalHandle;
use Drago\Component\OffcanvasHandle;
use Nette\Application\Attributes\Requires;


/**
 * @property-read BaseTemplate $template
 */
class RolesControl extends BaseControl implements Control, OffcanvasHandle, ModalHandle
{
	public function render(): void
	{
		// TODO: Implement render() method.
	}


	public function handleEdit(int $id): void
	{
		// TODO: Implement handleEdit() method.
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
		// TODO: Implement handleOpenOffcanvas() method.
	}
}
