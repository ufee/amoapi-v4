<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;

use Ufee\AmoV4\Models\User;

trait ResponsibleUser
{
    /**
     * Get responsible User model
	 * @param array $with
	 * @return User|null;
     */
	public function responsibleUser(array $with = [])
	{
		if (empty($this->hasField('responsible_user_id'))) {
			return null;
		}
		return $this->service->instance->cache->user(+$this->responsible_user_id, $with);
	}
}
