<?php
/**
 * amoCRM API client Subscriptions service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class Subscriptions extends Service
{
	protected $api_path = '/api/v4/{entity}/{id}/subscriptions';
	protected $entity_key = 'subscriptions';

	protected $entity_model = '\Ufee\AmoV4\Models\Subscription';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Subscriptions';

	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $args
	 */
	public function __construct(ApiClient $client, array $args)
	{
		$this->client_id = $client->client_id;

		if (count($args) < 2) {
			throw new \InvalidArgumentException('Subscriptions Service required entity_type and entity_id arguments');
		}
		$entity_type = (string)$args[0];
		$entity_id = $args[1];

		if (!in_array($entity_type, ['leads', 'customers'], true)) {
			throw new \InvalidArgumentException('Subscriptions Service supports only leads or customers entity_type');
		}
		if (!is_int($entity_id) && !(is_string($entity_id) && ctype_digit($entity_id))) {
			throw new \InvalidArgumentException('Subscriptions Service entity_id must be integer/string');
		}

		$this->api_path = str_replace(
			['{entity}', '{id}'],
			[$entity_type, (string)(int)$entity_id],
			$this->api_path
		);

		$this->_boot();
	}
}
