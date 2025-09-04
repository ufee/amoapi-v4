<?php
/**
 * amoCRM User model
 */
namespace Ufee\AmoV4\Models;

class User extends Model
{
	/**
     * Get user group
	 * @return object|null
     */
    public function group()
    {
		return $this->service->instance->cache->account()->userGroups->find('id', +$this->group_id)->first();
	}
}
