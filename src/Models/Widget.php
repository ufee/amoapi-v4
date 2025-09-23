<?php
/**
 * amoCRM Widget model
 */
namespace Ufee\AmoV4\Models;

class Widget extends Model
{
    /**
     * Uninstall widget
	 * @return bool
     */
    public function uninstall()
    {
		return $this->service->uninstall($this->code);
	}
}
