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
		$group_id = $this->rights->group_id ?? null;
		if (is_null($group_id)) {
			return null;
		}
		return $this->service->instance->cache->account()->userGroups->find('id', +$group_id)->first();
	}
}
