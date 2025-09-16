<?php
/**
 * amoCRM API client Pipelines service
 */
namespace Ufee\AmoV4\Services;

class Pipelines extends Service
{
	protected $api_path = '/api/v4/leads/pipelines';
	protected $entity_key = 'pipelines';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Pipeline';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Pipelines';
}
