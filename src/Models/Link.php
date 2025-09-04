<?php
/**
 * amoCRM Entities Link model
 */
namespace Ufee\AmoV4\Models;

class Link extends Model
{
	protected $required = [
		'to_entity_id', 'to_entity_type'
	];
	
    /**
     * Unlink current
	 * @return bool
     */
    public function delete()
    {
		return $this->service->delete($this->fields);
	}
}
