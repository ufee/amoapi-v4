<?php
/**
 * amoCRM API client Oauth handler - MongoDB
 */
namespace Ufee\AmoV4\Api\Oauth;
use Ufee\AmoV4\ApiClient;

class MongoDbStorage extends AbstractStorage
{
	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $options
	 */
	public function __construct(ApiClient $client, array $options)
	{
		parent::__construct($client, $options);

		if (empty($this->options['collection']) || !$this->options['collection'] instanceof \MongoDB\Collection) {
			throw new \Exception('MongoDB Storage options[collection] must be instance of \MongoDB\Collection');
		}
	}

	/**
	 * Init oauth handler
	 * @return void
	 */
	public function initialize()
	{
		parent::initialize();

		if ($row = $this->options['collection']->findOne(['_id' => $this->key])) {
			static::$_local[$this->key] = (array) $row->data;
		}
	}

	/**
	 * Set oauth data
	 * @param array $oauth
	 * @return bool
	 */
	public function set(array $oauth)
	{
		parent::set($oauth);

		$result = $this->options['collection']->updateOne(
			['_id' => $this->key],
			['$set' => ['data' => $oauth]],
			['upsert' => true]
		);
		return ($result->getUpsertedCount() || $result->getMatchedCount());
	}
}
