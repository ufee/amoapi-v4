<?php
/**
 * amoCRM API client Leads service
 */
namespace Ufee\AmoV4\Services;

class Leads extends Service
{
	use Traits\SearchByName;
	use Traits\SearchByCustomField;
	use Traits\Cfields;
	use Traits\Notes;
	
	protected $api_path = '/api/v4/leads';
	protected $entity_key = 'leads';

	protected $entity_model = '\Ufee\AmoV4\Models\Lead';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Leads';
}
