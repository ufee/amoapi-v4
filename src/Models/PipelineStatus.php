<?php
/**
 * amoCRM PipelineStatus model
 */
namespace Ufee\AmoV4\Models;

class PipelineStatus extends Model
{
    /**
     * Delete current
	 * @return bool
     */
    public function delete()
    {
		return $this->service->delete($this->id);
	}
}
