<?php
/**
 * amoCRM Account model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Services\Service;
use Ufee\AmoV4\Collections\Collection;

class Account extends Model
{
    /**
     * Constructor
	 * @param array $data
	 * @param Service $service
     */
    public function __construct(array $data, Service $service)
    {
		parent::__construct($data, $service);
		
		$this->fields['userGroups'] = new Collection($this->embedded['users_groups']);
		$this->fields['taskTypes'] = new Collection($this->embedded['task_types']);
		
		unset($this->embedded['users_groups'], $this->embedded['task_types']);
    }
}
