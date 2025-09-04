<?php
/**
 * amoCRM Task model
 */
namespace Ufee\AmoV4\Models;

class Task extends Model
{
	protected $required = [
		'text', 'complete_till'
	];
	
	/**
	 * Set completed status
	 * @param bool $state
	 * @param string $result
	 * @return Task
	 */
	public function setCompleted(bool $state, string $result = '')
	{
		$this->is_completed = $state;
		if (mb_strlen($result)) {
			$this->result = [
				'text' => $result
			];
		} else {
			$this->result = [];
		}
		return $this;
	}
	
	/**
     * Get task type
	 * @return object|null
     */
    public function type()
    {
		return $this->service->instance->cache->account()->taskTypes->find('id', +$this->task_type_id)->first();
	}
}
