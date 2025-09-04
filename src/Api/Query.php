<?php
/**
 * amoCRM API Query model
 */
namespace Ufee\AmoV4\Api;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Base\Services\Service;
use Ufee\AmoV4\Exceptions;
	
class Query
{
	protected 
		$system = [
			'url',
			'method',
			'args',
			'post_data',
			'json_data',
			'retry',
			'start_time',
			'end_time',
			'execution_time',
			'sleep_time',
			'hash',
			'memory_usage',
			'headers',
			'retries'
		],
		$hidden = [
			'client_id',
			'service',
			'curl',
			'response'
		],
		$attributes = [];
	
    /**
     * Constructor
	 * @param ApiClient $instance
	 * @param string $service_class
     */
    public function __construct(ApiClient &$instance, $service_class = '')
    {
        foreach ($this->system as $field_key) {
			$this->attributes[$field_key] = null;
		}
        foreach ($this->hidden as $field_key) {
			$this->attributes[$field_key] = null;
		}
		$this->attributes['client_id'] = $instance->getIntegration('client_id');
		$this->attributes['service'] = $service_class;
		$this->attributes['retry'] = true;
		$this->attributes['retries'] = 0;
		$this->_boot();
	}
	
    /**
     * Model on load
	 * @return void
     */
	protected function _boot()
	{
		$this->attributes['headers'] = [];
		$this->attributes['method'] = 'GET';
		$this->attributes['post_data'] = [];
		$this->attributes['json_data'] = [];
		$this->prepare();
	}
	
    /**
     * Prepare query
	 * @param bool $reset_retry
	 * @return Query
     */
	public function prepare(bool $reset_retry = false)
	{
		$instance = $this->instance;
		$this->attributes['curl'] = \curl_init();
		curl_setopt_array($this->curl, [
			CURLOPT_AUTOREFERER => true,
			CURLOPT_USERAGENT => $instance->getParam('user_agent'),
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => $instance->getParam('connect_timeout'),
			CURLOPT_TIMEOUT => $instance->getParam('query_timeout'),
			CURLOPT_HEADER => false,
			CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
		]);
		if ($interface = $instance->queries->getInterface()) {
			curl_setopt($this->curl, CURLOPT_INTERFACE, $interface);
		}
		if ($reset_retry) {
			$this->attributes['retries'] = 0;
		}
		return $this;
	}
	
    /**
     * Set query method
     * @param string $method
     */
    public function setMethod(string $method = 'GET')
    {
		$this->attributes['method'] = $method;
		return $this;
    }
    
    /**
     * Set query args
     * @param array $args
     */
    public function setArgs(array $args = [])
    {
		foreach ($args as $key=>$val) {
			$this->attributes['args'][$key] = $val;
		}
		return $this;
	}
	
    /**
     * Reset query args
     * @param array $args - argument keys to remove
     */
    public function resetArgs(array $args = [])
    {
		if (empty($args)) {
			$this->attributes['args'] = [];
		} else {
			foreach ($args as $key) {
				if (isset($this->attributes['args'][$key])) {
					unset($this->attributes['args'][$key]);
				}
			}
		}
		return $this;
    }

    /**
     * Set post query data
     * @param array $data
     */
    public function setPostData(array $data = [])
    {
		foreach ($data as $key=>$val) {
			$this->attributes['post_data'][$key] = $val;
		}
		return $this;
	}
	
    /**
     * Set post json data
     * @param array $data
     */
    public function setJsonData(array $data = [])
    {
		foreach ($data as $key=>$val) {
			$this->attributes['json_data'][$key] = $val;
		}
		$this->setHeader('Content-Type', 'application/json');
		return $this;
    }

    /**
     * Set url link
     * @param string $url
     */
    public function setUrl(string $url)
    {
		$this->attributes['url'] = $url;
		return $this;
	}
	
    /**
     * Set retry status
     * @param bool $status
     */
    public function setRetry(bool $status)
    {
		$this->attributes['retry'] = (bool)$status;
		return $this;
	}
	
    /**
     * Set time of sleep
     * @param int $value
     */
    public function setSleepTime(float $value)
    {
		$this->attributes['sleep_time'] = round($value, 3);
		return $this;
	}
	
    /**
     * Set headers
     * @param string $name
	 * @param mixed $value
     */
    public function setHeader(string $name, $value)
    {
		$this->attributes['headers'][$name] = $value;
		return $this;
	}

    /**
     * Get headers
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $name=>$value) {
            $headers[]= $name.': '.$value;
        }
		return $headers;
	}

    /**
     * Get url link
	 * @return string
     */
    public function getUrl()
    {
        $url = $this->url;
        if ($this->args) {
            $url .= '?' . http_build_query($this->args);
        }
        return 'https://'.$this->instance->getParam('crm_host').$url;
	}
	
    /**
     * Execute request
     * @return bool
     */
    public function execute()
    {
        $this->attributes['retries']++;
        $instance = $this->instance;
		
		$oauth = $instance->oauth->get();
		if (empty($oauth['access_token'])) {
			throw new Exceptions\OauthException('Empty oauth access_token');
		}
		$expire_time = ($oauth['created_at']+$oauth['expires_in'])-time();
		
		if ($expire_time < $instance->getParam('token_refresh_time')) {
			$instance->oauth->refreshToken();
			$oauth = $instance->oauth->get();
		}
		$this->setHeader(
			'Authorization', $oauth['token_type'].' '.$oauth['access_token']
		);
        $this->generateHash();
		if ($this->retries === 1) {
			$instance->callbacks->trigger('query.request.before', $this);
		}
		$method = strtolower($this->method);
		
        $this->attributes['response'] = new Response(
        	call_user_func([$this, $method]), $this
        );
        curl_close($this->curl);
		
		$this->attributes['curl'] = null;
		$code = $this->response->getCode();

		if ($instance->callbacks->trigger('query.response.code', $code, $this) === false) {
			return false;
		}
        $this->attributes['end_time'] = microtime(true);
        $this->attributes['execution_time'] = round($this->end_time - $this->start_time, 5);
        $this->attributes['memory_usage'] = memory_get_peak_usage(true)/1024/1024;

		if (in_array($code, [200,204])) {
			$instance->queries->pushQuery($this);
		} else {
			$instance->callbacks->trigger('query.response.fail', $this, $code);
		}
		$instance->callbacks->trigger('query.response.after', $this, $code);
        return true;
    }

    /**
     * GET query
     * @return string|bool
     */
    public function get()
    {
		$this->attributes['start_time'] = microtime(true);
        curl_setopt($this->curl, CURLOPT_URL, $this->getUrl());
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
        return curl_exec($this->curl);
    }

    /**
     * POST query
     * @return string|bool
     */
    public function post()
    {
		$this->attributes['start_time'] = microtime(true);
        curl_setopt($this->curl, CURLOPT_URL, $this->getUrl());
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($this->curl, CURLOPT_POST, true);
		
		if (!empty($this->attributes['json_data'])) {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->json_data));
		} else {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->post_data));
        }
        return curl_exec($this->curl);
    }

    /**
     * PATCH query
     * @return string|bool
     */
    public function patch()
    {
		$this->attributes['start_time'] = microtime(true);
		curl_setopt($this->curl, CURLOPT_URL, $this->getUrl());
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
    	curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->json_data));
        
        return curl_exec($this->curl);
    }
	
    /**
     * DELETE query
     * @return string|bool
     */
    public function delete()
    {
		$this->attributes['start_time'] = microtime(true);
		curl_setopt($this->curl, CURLOPT_URL, $this->getUrl());
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
    	curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->json_data));
        
        return curl_exec($this->curl);
    }
	
    /**
     * Set start time
	 * @return string
     */
    public function setStartTime($time)
    {
		$this->attributes['start_time'] = $time;
	}

    /**
     * Set end time
	 * @return string
     */
    public function setEndTime($time)
    {
		$this->attributes['end_time'] = $time;
	}

    /**
     * Get start date
	 * @return string
     */
    public function startDate($format = 'Y-m-d H:i:s')
    {
        return date($format, (int)$this->start_time);
	}

    /**
     * Get end date
	 * @return string
     */
    public function endDate($format = 'Y-m-d H:i:s')
    {
        return date($format, (int)$this->end_time);
	}

    /**
     * Generate query hash
	 * @return string
     */
    public function generateHash()
    {
		$instance = $this->instance;
		if (!$this->attributes['hash']) {
			$args = $this->args;
			$this->attributes['hash'] = hash('fnv1a64', 
				$instance->getIntegration('domain').
				$instance->getIntegration('client_id').
				$instance->getIntegration('zone').
				$this->method.
				json_encode($args).
				json_encode($this->post_data).
				json_encode($this->json_data)
			);
		}
		return $this->attributes['hash'];
	}
	
    /**
     * DEBUG curl query
	 * @param resource $fp - fopen file
     * @return Query
     */
    public function verbose(resource $fp)
    {
		curl_setopt($this->curl, CURLOPT_VERBOSE, 1);
		curl_setopt($this->curl, CURLOPT_STDERR, $fp);
		return $this;
	}
	
    /**
     * Clear Model data
     * @return array
     */
    public function clear()
    {
		$this->attributes['curl'] = null;
		$this->attributes['response'] = null;
	}

    /**
     * Convert Model to array
     * @return array
     */
    public function toArray()
    {
		$fields = [];
		foreach ($this->attributes as $field_key=>$val) {
			if (in_array($field_key, $this->hidden)) {
				continue;
			}
			$fields[$field_key] = $val;
		}
		return $fields;
    }
	
	/**
	 * Get Service
	 * @param string $field
	 */
	public function __get($field)
	{
		if ($field === 'instance') {
			return ApiClient::getInstance($this->client_id);
		}
		if (!array_key_exists($field, $this->attributes)) {
			throw new \Exception('Invalid Query field: '.$field);
		}
		return $this->attributes[$field];
	}

    /**
     * Protect set model fields
	 * @param string $field
	 * @param string $value
     */
	public function __set($field, $value)
	{
		if (!array_key_exists($field, $this->attributes)) {
			throw new \Exception('Invalid Query field: '.$field);
		}
		throw new \Exception('Protected Query field set fail: '.$field);
	}
	
    /**
     * Close curl
     */
    public function __destruct()
    {
        if (is_resource($this->curl)) {
			curl_close($this->curl);
		}
    }
}
