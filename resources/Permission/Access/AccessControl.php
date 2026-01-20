<?php

declare(strict_types=1);

namespace App\Core\Permission\Access;

use App\Core\Permission\BaseControl;
use App\Core\Permission\BaseTemplate;
use Dibi\Result;
use Nette\Application\Attributes\Requires;


/**
 * @property-read BaseTemplate $template
 */
class AccessControl extends BaseControl
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


	protected function getResultRepository(int $id): Result|int|null
	{
		// TODO: Implement getDeleteRepository() method.
	}


	protected function getItemRepository(int $id): string
	{
		// TODO: Implement getItemRepository() method.
	}
}
