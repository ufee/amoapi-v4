<?php
/**
 * amoCRM Webhook model
 */
namespace Ufee\AmoV4\Models;

class Webhook extends Model
{
	protected $required = [
		'destination', 'settings'
	];
	
    /**
     * Unsubscribe from this hook url
	 * @return bool
     */
    public function unsubscribe()
    {
		return $this->service->unsubscribe($this->destination);
	}
}
