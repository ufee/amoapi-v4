<?php
/**
 * amoCRM API client LossReasons service
 */
namespace Ufee\AmoV4\Services;

class LossReasons extends Service
{
	protected $api_path = '/api/v4/leads/loss_reasons';
	protected $entity_key = 'loss_reasons';
	
	protected $entity_model = '\Ufee\AmoV4\Models\LossReason';
	protected $entity_collection = '\Ufee\AmoV4\Collections\LossReasons';
}
