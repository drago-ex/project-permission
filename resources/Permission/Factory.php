<?php

declare(strict_types=1);

namespace App\Core\Permission;

use Drago\Form\Forms;
use Drago\Localization\Translator;
use Nette\Application\UI\Form;


/** Factory for creating forms. */
readonly class Factory
{
	public function __construct(
		private Translator $translator,
	) {
	}


	/** Creates a new form. */
	public function create(): Forms
	{
		$form = new Forms;
		$form->addProtection();
		$form->setTranslator($this->translator);
		return $form;
	}


	/** Creates a delete form with a hidden ID field. */
	public function createDelete(?int $id = null): Form
	{
		$form = $this->create();
		$form->addHidden('id', $id)
			->addRule($form::Integer)
			->setNullable();

		return $form;
	}
}
