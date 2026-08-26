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
     * Create linked lead model
     * @return Lead
     */
    public function createLead()
    {
		$parent = $this;
		$lead = $this->service->instance->leads()->create();
		$lead->responsible_user_id = $this->responsible_user_id;

		if ($this->service->entity_key === 'contacts') {
			$lead->attachContact($this);
			if ($this->hasCompany()) {
				$lead->attachCompany($this->getCompanyId());
			}
		} elseif ($this->service->entity_key === 'companies') {
			$lead->attachCompany($this);
		}

		$lead->onCreate(function(&$model) use (&$parent) {
			$parent->attachLead($model);
		});
		return $lead;
	}

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
	 * Check if entity has leads
	 * @return bool
	 */
	public function hasLeads(): bool
	{
		return count($this->leads ?? []) > 0;
	}

    /**
     * Get linked leads
	 * @return Leads|bool
     */
    public function leads(array $with = [])
    {
		return $this->links()->get(['to_entity_type' => 'leads'])->leads($with);
	}
}
