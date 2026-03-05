<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Services;

trait Subscriptions
{
    /**
     * Get entity subscriptions collection
	 * @return \Ufee\AmoV4\Collections\Subscriptions
     */
	public function getSubscriptions()
	{
		return $this->subscriptions()->get();
	}

    /**
     * Get entity subscriptions service
	 * @return Services\Subscriptions
     */
	public function subscriptions()
	{
		$service = $this->service;
		return $service->instance->subscriptions($service->entity_key, $this->id);
	}
}
