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
	
    /**
     * Has note pinned
	 * @return bool
     */
    public function isPinned()
    {
		if ($this->hasField('is_pinned')) {
			return $this->is_pinned;
		}
		return null;
	}
	
    /**
     * Set note pinned
	 * @return Note
     */
    public function pin()
    {
		if ($this->hasField('is_pinned')) {
			$this->is_pinned = true;
		}
		return $this->service->pin($this->id);
	}
	
    /**
     * Unpin note
	 * @return Note
     */
    public function unpin()
    {
		if ($this->hasField('is_pinned')) {
			$this->is_pinned = false;
		}
		return $this->service->unpin($this->id);
	}
}
