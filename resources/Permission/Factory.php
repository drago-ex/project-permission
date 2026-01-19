<?php

declare(strict_types=1);

namespace App\Core\Permission;

use Drago\Form\Forms;
use Drago\Localization\Translator;
use Nette\Application\UI\Form;
use Nette\Security\User;


readonly class Factory
{
	public function __construct(
		private Translator $translator,
		private User $user,
	) {
	}


	public function create(): Forms
	{
		$form = new Forms;

		// Add form protection if the user is logged in
		if ($this->user->isLoggedIn()) {
			$form->addProtection();
		}

		// Set the translator for form
		$form->setTranslator($this->translator);

		return $form;
	}


	/**
	 * Creates a delete form with a hidden ID field.
	 */
	public function createDelete(int $id): Form
	{
		$form = $this->create();
		$form->addHidden('id', $id)
			->addRule($form::Integer);

		return $form;
	}
}
