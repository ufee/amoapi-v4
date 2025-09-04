<?php
/**
 * amoCRM API client Tasks service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class Tasks extends Service
{
	protected $api_path = '/api/v4/tasks';
	protected $entity_key = 'tasks';

	protected $entity_model = '\Ufee\AmoV4\Models\Task';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Tasks';
}
