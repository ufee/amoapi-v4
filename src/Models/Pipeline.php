<?php
/**
 * amoCRM Pipeline model
 */
namespace Ufee\AmoV4\Models;
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
			foreach($this->embedded['statuses'] as $status) {
				
				$this->_statuses->push(new PipelineStatus((array)$status, $this->service));
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
     * Delete current
	 * @return bool
     */
    public function delete()
    {
		return $this->service->delete($this->id);
	}
}
