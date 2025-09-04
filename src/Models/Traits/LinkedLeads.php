<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Models\Lead;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Collections\Leads;
use Ufee\AmoV4\Collections\Links;

trait LinkedLeads
{
    /**
     * Create entity link
	 * @param integer|Lead $entity - id or model
	 * @return bool|Link;
     */
	public function attachLead($entity)
	{
		return $this->attachEntity($entity, 'leads');
	}

    /**
     * Create entities links
	 * @param array|Leads $entities - model collection or array of ids
	 * @return bool|Links;
     */
	public function attachLeads($entities)
	{
		return $this->attachEntities($entities, 'leads');
	}
	
    /**
     * Delete entity link
	 * @param integer|Lead $entity - id or model
	 * @return bool;
     */
	public function detachLead($entity)
	{
		return $this->detachEntity($entity, 'leads');
	}
	
    /**
     * Delete entities links
	 * @param array|Leads $entities - model collection or array of ids
	 * @return bool;
     */
	public function detachLeads($entities)
	{
		return $this->detachEntities($entities, 'leads');
	}
	
    /**
     * Get linked leads
	 * @return Leads|bool
     */
    public function leads()
    {
		return $this->links()->get(['to_entity_type' => 'leads'])->leads();
	}
}
