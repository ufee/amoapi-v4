<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Api\Paginate;
use Ufee\AmoV4\Models\Task;

trait Tasks
{
    /**
     * Find entity Task by id
	 * @param int $task_id
	 * @return Task|null;
     */
	public function findTask(int $task_id)
	{
		return $this->service->instance->tasks()->find($task_id);
	}
	
    /**
     * Get entity Tasks
	 * @param array $conditions - filter
	 * @return Paginate;
     */
	public function getTasks(array $conditions = [])
	{
		$service = $this->service;
		$conditions = array_merge($conditions, ['entity_id' => $this->id, 'entity_type' => $service->entity_key]);
		return $this->service->instance->tasks()->filter($conditions);
	}
	
    /**
     * Create entity Task, lazy
	 * @param int $type - 1/2/{type_id}/...
	 * @return Task;
     */
	public function createTask(int $type = 1)
	{
		$service = $this->service;
		return $service->instance->tasks()->create(['entity_id' => $this->id, 'entity_type' => $service->entity_key, 'task_type_id' => $type]);
	}
}
