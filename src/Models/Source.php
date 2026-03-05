<?php
/**
 * amoCRM Source model
 */
namespace Ufee\AmoV4\Models;

class Source extends Model
{
	protected $required = [
		'name'
	];
	
    /**
     * Delete this source
	 * @return bool
     */
    public function delete()
    {
		return $this->service->remove($this->id);
	}
}
