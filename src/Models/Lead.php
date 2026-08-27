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
	use Traits\Files;
	use Traits\Subscriptions;
	use Traits\Links;
	use Traits\LinkedContacts;
	use Traits\LinkedCompanies;
	use Traits\LinkedCatalogElements;
	use Traits\ResponsibleUser;
	use Traits\MainContact;

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
