<?php

declare(strict_types=1);

namespace App\Core\Permission;


interface Control
{
	/** Render template for factory. */
	public function render(): void;

	/** Signal edit. */
	public function handleEdit(int $id): void;

	/** Signal delete. */
	public function handleDelete(int $id): void;
}
