<?php
/**
 * amoCRM API client Events service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\Collections;

class Events extends Service
{
	protected $api_path = '/api/v4/events';
	protected $entity_key = 'events';

	protected $entity_model = '\Ufee\AmoV4\Models\Event';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Events';
	
	/**
     * Get task type
	 * @param string|null $lang – en, es, ru, pt
	 * @return object|null
     */
    public function types($lang = null)
    {
		if (is_null($lang)) {
			$lang = $this->instance->getParam('lang');
		}
		$query = $this->instance->query('GET', $this->api_path.'/types');
		$query->setArgs([
			'language_code' => $lang
		]);
		$query->execute();
		
		return new Collections\Collection($query->response->validatedEntities('events_types'));
	}
}
