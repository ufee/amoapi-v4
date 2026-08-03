<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;

trait MainContact
{
    /**
     * Has main linked contact
     * @return bool
     */
    public function hasMainContact()
    {
		foreach ($this->embedded['contacts'] ?? [] as $contact) {
			if (!empty($contact->is_main)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get main linked contact
	 * @param array $with
	 * @return Contact|null
	 */
	public function getMainContact(array $with = [])
	{
		foreach ($this->embedded['contacts'] ?? [] as $contact) {
			if (!empty($contact->is_main)) {
				return $this->service->instance->contacts()->find($contact->id, $with);
			}
		}
		return null;
	}
}
