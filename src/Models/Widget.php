<?php
/**
 * amoCRM Widget model
 */
namespace Ufee\AmoV4\Models;

class Widget extends Model
{
    /**
     * Install widget
	 * @param array $settings
	 * @return bool
     */
    public function install(array $settings)
    {
		return $this->service->install($this->code, $settings);
	}
	
    /**
     * Uninstall widget
	 * @return bool
     */
    public function uninstall()
    {
		return $this->service->uninstall($this->code);
	}
}
