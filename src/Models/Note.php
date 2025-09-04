<?php
/**
 * amoCRM Note model
 */
namespace Ufee\AmoV4\Models;

class Note extends Model
{
	protected $required = [
		'note_type', 'params'
	];
	
    /**
     * Set note params
	 * @param array $params
	 * @return Note
     */
    public function setParams(array $params)
    {
		$this->params = $params;
		return $this;
	}
}
