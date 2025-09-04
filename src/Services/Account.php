<?php
/**
 * amoCRM API client Account service
 */
namespace Ufee\AmoV4\Services;
use \Ufee\AmoV4\Models;

class Account extends Service
{
	protected $api_path = '/api/v4/account';
	protected $entity_key = 'account';

    /**
     * Service on load
	 * @return void
     */
	protected function _boot()
	{
		$this->query_args['with'] = 'amojo_id,users_groups,task_types,version,datetime_settings,drive_url,is_api_filter_enabled';
	}
	
    /**
     * Get account info
	 * @param array $with
	 * @return Models\Account|null
     */
	public function get($with = null)
	{
		if (is_null($with)) {
			$with = [];
		}
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->execute();
		$row = $query->response->validated();
		
		return new Models\Account((array)$row, $this);
	}
}
