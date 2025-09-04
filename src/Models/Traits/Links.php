<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Services;
use Ufee\AmoV4\Models\Model;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Collections\Entities;
use Ufee\AmoV4\Collections;

trait Links
{
    /**
     * Create entity link
	 * @param integer|Model $entity - id or model
	 * @param string $entity_type - leads/contacts/companies
	 * @return bool|Link;
     */
	public function attachEntity($entity, $entity_type)
	{
		$entity_id = is_object($entity) ? $entity->id : $entity;
		$link = $this->links()->create(['entity_id' => $this->id, 'to_entity_id' => $entity_id, 'to_entity_type' => $entity_type]);
		return $link->save() ? $link : false;
	}

    /**
     * Create entity Links
	 * @param array|Entities $entities - model collection or array of ids
	 * @param string $entity_type - leads/contacts/companies
	 * @return bool|Collections\Links;
     */
	public function attachEntities($entities, $entity_type)
	{
		$entity_ids = ($entities instanceof Entities) ? $entities->fieldValues('id')->all() : $entities;
		$rows = [];
		foreach($entity_ids as $attach_id) {
			$rows[]= ['entity_id' => $this->id, 'to_entity_id' => $attach_id, 'to_entity_type' => $entity_type];
		}
		$links = $this->links()->createCollection($rows);
		return $links->save() ? $links : false;
	}
	
    /**
     * Delete entity link
	 * @param integer|Model $entity - id or model
	 * @param string $entity_type - leads/contacts/companies
	 * @return bool;
     */
	public function detachEntity($entity, $entity_type)
	{
		$entity_id = is_object($entity) ? $entity->id : $entity;
		$link = $this->links()->create(['entity_id' => $this->id, 'to_entity_id' => $entity_id, 'to_entity_type' => $entity_type]);
		return $link->delete();
	}
	
    /**
     * Delete entity Links
	 * @param array|Entities $entities - model collection or array of ids
	 * @param string $entity_type - leads/contacts/companies
	 * @return bool;
     */
	public function detachEntities($entities, $entity_type)
	{
		$entity_ids = ($entities instanceof Entities) ? $entities->fieldValues('id')->all() : $entities;
		$rows = [];
		foreach($entity_ids as $detach_id) {
			$rows[]= ['entity_id' => $this->id, 'to_entity_id' => $detach_id, 'to_entity_type' => $entity_type];
		}
		$links = $this->links()->createCollection($rows);
		return $links->delete();
	}
	
    /**
     * Get entity Links
	 * @return Services\Links;
     */
	public function links()
	{
		$service = $this->service;
		return $service->instance->links($service->entity_key, $this->id);
	}
}
