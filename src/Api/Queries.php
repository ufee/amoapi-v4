<?php
/**
 * amoCRM API Query Collection class
 */
namespace Ufee\AmoV4\Api;
use Ufee\AmoV4\Collections\Collection;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\Storage\Cache\AbstractStorage;

class Queries extends Collection
{
    protected $instance;
	protected $curl_interfaces = [];

    /**
     * Boot instance
     * @param ApiClient $instance
     */
    public function boot(ApiClient $instance)
    {
        $this->instance = $instance;
		
		// delay for api limits
		$instance->callbacks->on('query.delay', function(Query $query) {
			$last_time = 1;
			if ($lastQuery = $this->last()) {
				$last_time = $lastQuery->start_time;
			}
			$current_time = microtime(true);
			$time_offset = $current_time-$last_time;
			$sleep_time = 0;
			if ($query->instance->getParam('query_delay') > $time_offset) {
				$sleep_time = (float)($query->instance->getParam('query_delay')-$time_offset);
				usleep((int)$sleep_time*1000000);
			}
			$query->setSleepTime($sleep_time);
		});
		
		// api fails
		$instance->callbacks->on('query.response.code', function(int $code, Query $query) use($instance) {
			if ($code == 429 && $query->retries <= $query->instance->getParam('query_retries')) {
				// for limit requests
				sleep(1);
				$query->prepare()->execute();
				return false;
			}
			else if ($code == 401 && $query->retry) {
				// multiserver refresh token fix
				sleep(1);
				$oauth = $instance->oauth->get(null, true);
				$query->setHeader(
					'Authorization', $oauth['token_type'].' '.$oauth['access_token']
				);
				$query->prepare()->setRetry(false)->execute();
				return false;
			}
			else if (in_array($code, [502,504]) && $query->retry) {
				// for random api errors
				sleep(1);
				$query->prepare()->setRetry(false)->execute();
				return false;
			}
			return true;
		});
    }

    /**
     * Set curl interfaces
     * @param array $interfaces
     */
    public function viaInterfaces(array $interfaces)
    {
        $this->curl_interfaces = $interfaces;
    }

    /**
     * Get curl interface
     * @return string|null
     */
    public function getInterface()
    {
        if ($this->curl_interfaces === []) {
            return null;
        }
        return $this->curl_interfaces[array_rand($this->curl_interfaces)];
    }

    /**
     * Push new queries
     * @param QueryModel $query
     * @return QueryCollection
     */
    public function pushQuery(Query $query)
    {
		array_push($this->items, $query);
        return $this;
    }
}
