<?php
/**
 * amoCRM API client service trait
 */
namespace Ufee\AmoV4\Services\Traits;
use Ufee\AmoV4\Services;

trait Cfields
{
    /**
     * Get entity Custom Fields
	 * @return Services\CustomFields
     */
	public function customFields()
	{
		return $this->instance->customFields($this->entity_key);
	}
}
