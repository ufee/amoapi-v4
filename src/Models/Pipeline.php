<?php
/**
 * amoCRM Pipeline model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Services;
use Ufee\AmoV4\Models\PipelineStatus;
use Ufee\AmoV4\Collections\PipelineStatuses;

class Pipeline extends Model
{
	protected $_statuses;
	
    /**
     * Get statuses
	 * @return PipelineStatuses
     */
    public function statuses()
    {
		if (is_null($this->_statuses) || count($this->embedded['statuses']) !== $this->_statuses->count()) {
			
			$this->_statuses = new PipelineStatuses();
			$service = new Services\PipelineStatuses($this->service->instance, [$this->id]);
			
			foreach($this->embedded['statuses'] as $status) {
				
				$this->_statuses->push(new PipelineStatus((array)$status, $service));
			}
		}
		return $this->_statuses;
	}
	
    /**
     * Create Status
	 * @param array $fields
	 * @return PipelineStatus;
     */
	public function createStatus(array $fields = [])
	{
		return $this->service->instance->pipelineStatuses($this->id)->create($fields);
	}
	
    /**
     * Delete Status
	 * @param array $fields
	 * @return bool;
     */
	public function deleteStatus(int $status_id)
	{
		return $this->service->instance->pipelineStatuses($this->id)->delete($status_id);
	}
	
    /**
     * Delete current
	 * @return bool
     */
    public function delete()
    {
		return $this->service->delete($this->id);
	}
}
