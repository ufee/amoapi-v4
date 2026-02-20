<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Services;
use Ufee\AmoV4\Models\Note;

trait Notes
{
    /**
     * Find entity Note by id
	 * @param int $note_id
	 * @return Note|null;
     */
	public function findNote(int $note_id, $with = ['is_pinned'])
	{
		return $this->notes()->find($note_id, $with);
	}
	
    /**
     * Get entity Notes
	 * @param array $conditions - filter
	 * @return Paginate;
     */
	public function getNotes(array $conditions = [], $with = ['is_pinned'])
	{
		return $this->notes()->filter($conditions, $with);
	}

    /**
     * Create entity Note, lazy
	 * @param string $type - common/service_message/...
	 * @return Note;
     */
	public function createNote(string $type = 'common')
	{
		$service = $this->service;
		return $this->notes()->create(['note_type' => $type, 'entity_type' => $service->entity_key, 'entity_id' => $this->id]);
	}
	
    /**
     * Get entity Notes
	 * @return Services\Notes;
     */
	public function notes()
	{
		$service = $this->service;
		return $service->instance->notes($service->entity_key, $this->id);
	}
}
