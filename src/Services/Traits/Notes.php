<?php
/**
 * amoCRM API client service trait
 */
namespace Ufee\AmoV4\Services\Traits;
use Ufee\AmoV4\Services;

trait Notes
{
    /**
     * Get entity Notes
	 * @return Services\Notes
     */
	public function notes()
	{
		return $this->instance->notes($this->entity_key);
	}
}
