<?php
/**
 * amoCRM Event model
 */
namespace Ufee\AmoV4\Models;

class Event extends Model
{
	/**
     * Get task type
	 * @return object|null
     */
    public function type()
    {
		return $this->service->instance->cache->eventTypes()->find('key', $this->type)->first();
	}
}
