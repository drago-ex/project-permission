<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Accessory;

use Nette\Application\UI\Control;
use Nette\InvalidStateException;


/**
 * Decides whether an administration component shows its write buttons (add, edit, delete):
 * by the "{component}-write" privilege the Authorization trait requires for the component's signals,
 * so the buttons are shown exactly to those who may use them. The administrations of other packages
 * (project-blog, project-static-page, ...) use it in place of their own isWriteAllowed(), see the README.
 * @phpstan-require-extends Control
 */
trait WriteAccess
{
	public function isWriteAllowed(): bool
	{
		$presenter = $this->getPresenter();
		$component = explode('-', $this->getUniqueId())[0];

		try {
			return $presenter->getUser()->isAllowed((string) $presenter->getName(), "$component-write");
		} catch (InvalidStateException) {
			return false; // a presenter without an ACL resource is denied, as by the Authorization trait
		}
	}
}
