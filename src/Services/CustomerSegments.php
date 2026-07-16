<?php
/**
 * amoCRM API client CustomerSegments service
 */
namespace Ufee\AmoV4\Services;

class CustomerSegments extends Service
{
	use Traits\Cfields;

	protected $api_path = '/api/v4/customers/segments';
	protected $entity_key = 'segments';

	protected $entity_model = '\Ufee\AmoV4\Models\CustomerSegment';
	protected $entity_collection = '\Ufee\AmoV4\Collections\CustomerSegments';
}
