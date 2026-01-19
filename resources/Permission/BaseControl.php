<?php

declare(strict_types=1);

namespace App\Core\Permission;

use Drago\Application\UI\ExtraControl;
use Drago\Component\Component;


class BaseControl extends ExtraControl
{
	use Component;

	protected string $snippetMessage = 'message';


	public function redrawOffCanvas(): void
	{
		$this->offCanvasComponent(self::Offcanvas);
		$this->redrawControl();
	}


	public function redrawModal(): void
	{
		$this->offCanvasComponent(self::Modal);
		$this->redrawControl();
	}


	public function redrawFlashMessage(string $message, string $type = 'info'): void
	{
		$this->getPresenter()->flashMessage($message, $type);
		$this->redrawControl($this->snippetMessage);
	}
}
