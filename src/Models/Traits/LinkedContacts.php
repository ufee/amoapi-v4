<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Models\Contact;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Collections\Contacts;
use Ufee\AmoV4\Collections\Links;

trait LinkedContacts
{
    /**
     * Create entity link
	 * @param integer|Contact $entity - id or model
	 * @return bool|Link;
     */
	public function attachContact($entity)
	{
		return $this->attachEntity($entity, 'contacts');
	}

    /**
     * Create entities links
	 * @param array|Contacts $entities - model collection or array of ids
	 * @return bool|Links;
     */
	public function attachContacts($entities)
	{
		return $this->attachEntities($entities, 'contacts');
	}
	
    /**
     * Delete entity link
	 * @param integer|Contact $entity - id or model
	 * @return bool;
     */
	public function detachContact($entity)
	{
		return $this->detachEntity($entity, 'contacts');
	}
	
    /**
     * Delete entities links
	 * @param array|Contacts $entities - model collection or array of ids
	 * @return bool;
     */
	public function detachContacts($entities)
	{
		return $this->detachEntities($entities, 'contacts');
	}

    /**
     * Get linked contacts
	 * @return Contacts|bool
     */
    public function contacts()
    {
		return $this->links()->get(['to_entity_type' => 'contacts'])->contacts();
	}
}
