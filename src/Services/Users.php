<?php
/**
 * amoCRM API client Users service
 */
namespace Ufee\AmoV4\Services;

class Users extends Service
{
	protected $api_path = '/api/v4/users';
	protected $entity_key = 'users';

	protected $entity_model = '\Ufee\AmoV4\Models\User';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Users';
	
    /**
     * Service on load
	 * @return void
     */
	protected function _boot()
	{
		$this->query_args['with'] = 'uuid,amojo_id,phone_number';
	}
}
