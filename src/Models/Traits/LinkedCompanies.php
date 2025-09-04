<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Models\Company;
use Ufee\AmoV4\Models\Link;

trait LinkedCompanies
{
    /**
     * Create entity link
	 * @param integer|Company $entity - id or model
	 * @return bool|Link;
     */
	public function attachCompany($entity)
	{
		return $this->attachEntity($entity, 'companies');
	}
	
    /**
     * Delete entity link
	 * @param integer|Company $entity - id or model
	 * @return bool;
     */
	public function detachCompany($entity)
	{
		return $this->detachEntity($entity, 'companies');
	}

    /**
     * Get linked companies
	 * @return Company|bool
     */
    public function company()
    {
		//return $this->links()->get(['to_entity_type' => 'companies'])->company();
		$company_id = $this->embedded['companies'][0]->id ?? null;
		if (!$company_id) {
			return false;
		}
		return $this->service->instance->companies()->find($company_id);
	}
}
