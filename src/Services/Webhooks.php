<?php
/**
 * amoCRM API client Webhooks service
 */
namespace Ufee\AmoV4\Services;
use \Ufee\AmoV4\Collections;
use \Ufee\AmoV4\Models;
use Ufee\AmoV4\Exceptions;

class Webhooks extends Service
{
	protected $api_path = '/api/v4/webhooks';
	protected $entity_key = 'webhooks';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Webhook';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Webhooks';
	
    /**
     * Get webhooks subscribed
	 * @param string $destination - filter by url
	 * @return Collections\Webhooks
     */
	public function get($destination = null)
	{
		$query_args = $this->query_args;
		if (is_string($destination) && !empty($destination)) {
			$query_args['filter'] = ['destination' => $destination];
		}
		$query = $this->instance->query('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->execute();

		$validated = $query->response->validated();
		if (!property_exists($validated, '_embedded')) {
			throw new Exceptions\AmoException('Invalid API response for entities, embedded not found, code: '.$this->getCode(), $this->getCode());
		}
		if (property_exists($validated->_embedded, $this->entity_key)) {
			return $this->createCollection($validated->_embedded->{$this->entity_key});
		}
		return $this->createCollection();
	}
	
    /**
     * Subscribe to webhook
	 * @param string $url
	 * @param array $events
	 * @return Models\Webhook
     */
	public function subscribe(string $url, array $events)
	{
		$query = $this->instance->query('POST', $this->api_path);
		$query->setJsonData([
			'destination' => $url,
			'settings' => $events
		]);
		$query->execute();
		
		$result = $query->response->validated();
		return new Models\Webhook((array)$result, $this);
	}
	
    /**
     * Unsubscribe from webhook
	 * @param string $url
	 * @return bool
     */
    public function unsubscribe(string $url)
    {
		$query = $this->instance->query('DELETE', $this->api_path);
		$query->setJsonData([
			'destination' => $url
		]);
		$query->execute();
		return $query->response->getCode() === 204;
	}
}
