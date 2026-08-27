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
     * Create linked company model
     * @return Company
     */
    public function createCompany()
    {
		$parent = $this;
		$company = $this->service->instance->companies()->create();
		$company->responsible_user_id = $this->responsible_user_id;

		if ($this->service->entity_key === 'leads') {
			$company->attachLead($this);
			if ($this->hasMainContact()) {
				$company->attachContact($this->getMainContact());
			}
		} elseif ($this->service->entity_key === 'contacts') {
			$company->attachContact($this);
		}

		$company->onCreate(function(&$model) use (&$parent) {
			$parent->attachCompany($model);
		});
		return $company;
	}

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
     * Has linked company
	 * @return bool
     */
    public function hasCompany()
    {
		return !empty($this->embedded['companies'][0]->id);
	}

    /**
     * Get first linked company id
	 * @return int|null
     */
    public function getCompanyId()
    {
		return $this->embedded['companies'][0]->id ?? null;
	}

    /**
     * Get linked companies
	 * @return Company|bool
     */
    public function company(array $with = [])
    {
		$company_id = $this->getCompanyId();
		if (!$company_id) {
			return false;
		}
		return $this->service->instance->companies()->find($company_id, $with);
	}
}
