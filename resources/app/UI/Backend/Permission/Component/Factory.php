<?php

declare(strict_types=1);

namespace App\UI\Backend\Permission\Component;

use Drago\Form\Forms;
use Nette\Application\UI\Form;


/**
 * Factory for creating permission forms.
 * @extends \Drago\Application\UI\Factory<Forms>
 */
readonly class Factory extends \Drago\Application\UI\Factory
{
	protected function createForm(): Forms
	{
		return new Forms;
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
