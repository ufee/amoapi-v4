<?php
/**
 * amoCRM API Client Callbacks class
 */
namespace Ufee\AmoV4\Api;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Api\Query;

/**
 * @property ApiClient $instance
 * @method Callbacks on(string $event, callable $callback)
 * @method Callbacks once(string $event, callable $callback)
 * @method Callbacks off(string $event)
 * @method bool has(string $event)
 * @method bool trigger(string $event, ...$args)
 */
class Callbacks
{
	protected $instance;
	protected $_callbacks = [];
	
    /**
     * Boot instance
     * @param ApiClient $instance
     */
    public function __construct(ApiClient $instance)
    {
		$this->instance = $instance;
	}
	
    /**
     * On event set callback
     * @param string $event
     * @param callable $callback
	 * @return Callbacks
     */
    public function on(string $event, callable $callback)
    {
        $this->_callbacks[$event][]= $callback;
        return $this;
    }
	
    /**
     * Once event set callback
     * @param string $event
     * @param callable $callback
	 * @return Callbacks
     */
	public function once(string $event, callable $callback)
	{
		$wrapper = function (...$args) use ($event, $callback) {
			$this->off($event);
			return $callback(...$args);
		};
		return $this->on($event, $wrapper);
	}

    /**
     * On event unset callbacks
     * @param string $event
	 * @return Callbacks
     */
    public function off(string $event)
    {
        unset($this->_callbacks[$event]);
        return $this;
    }

    /**
     * Check event callback
     * @param string $event
	 * @return bool
     */
    public function has(string $event)
    {
		return !empty($this->_callbacks[$event]);
    }

    /**
     * Run event callback
     * @param string $event
     * @param mixed $args
	 * @return bool
     */
    public function trigger(string $event, ...$args)
    {
		if (empty($this->_callbacks[$event])) {
			return true;
		}
		foreach ($this->_callbacks[$event] as $callback) {
			$result = $callback(...$args);
			if ($result === false) {
				return false;
			}
		}
		return true;
    }
}
