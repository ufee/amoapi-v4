<?php
/**
 * amoCRM Lead model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Models\Traits;

class Lead extends WithCfield
{
	use Traits\Tags;
	use Traits\Tasks;
	use Traits\Notes;
	use Traits\Subscriptions;
	use Traits\Links;
	use Traits\LinkedContacts;
	use Traits\LinkedCompanies;
	use Traits\LinkedCatalogElements;
	use Traits\ResponsibleUser;
	use Traits\MainContact;

	/**
     * Create linked contact model
     * @return Contact
     */
    public function createContact()
    {
		$lead = $this;
		$contact = $this->service->instance->contacts()->create();
		$contact->responsible_user_id = $this->responsible_user_id;
		$contact->attachLead($this);

		if ($this->hasCompany()) {
			$contact->attachCompany($this->getCompanyId());
		}
		$contact->onCreate(function(&$model) use (&$lead) {
			$lead->attachContact($model);
		});
		return $contact;
	}

	/**
     * Create linked company model
     * @return Company
     */
    public function createCompany()
    {
		$lead = $this;
		$company = $this->service->instance->companies()->create();
		$company->responsible_user_id = $this->responsible_user_id;
		$company->attachLead($this);

		if ($this->hasMainContact()) {
			$company->attachContact($this->getMainContact());
		}
		$company->onCreate(function(&$model) use (&$lead) {
			$lead->attachCompany($model);
		});
		return $company;
	}

	/**
	 * Get pipeline
	 * @return Pipeline
	 */
	public function pipeline(): Pipeline
	{
		return $this->service->instance->cache->pipeline($this->pipeline_id);
	}

	/**
	 * Get status
	 * @return PipelineStatus|null
	 */
	public function status(): ?PipelineStatus
	{
		return $this->pipeline()->statuses()->where('id', $this->status_id)->first();
	}
}
