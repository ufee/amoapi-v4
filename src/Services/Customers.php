<?php
/**
 * amoCRM API client Customers service
 */
namespace Ufee\AmoV4\Services;

class Customers extends Service
{
	use Traits\SearchByName;
	use Traits\Cfields;

	protected $api_path = '/api/v4/customers';
	protected $entity_key = 'customers';

	protected $entity_model = '\Ufee\AmoV4\Models\Customer';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Customers';

	/**
	 * Get customer segments service
	 * @return CustomerSegments
	 */
	public function segments()
	{
		return $this->instance->customerSegments();
	}
}
