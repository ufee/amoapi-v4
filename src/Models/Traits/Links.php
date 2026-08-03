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
	 * @param string $entity_type - leads/contacts/companies/catalog_elements
	 * @param array $metadata - link metadata
	 * @return bool|Link;
     */
	public function attachEntity($entity, $entity_type, array $metadata = [])
	{
		$entity_id = is_object($entity) ? $entity->id : $entity;
		$link = $this->links()->create($this->linkRawData($entity_id, $entity_type, $metadata));
		return $link->save() ? $link : false;
	}

    /**
     * Create entity Links
	 * @param array|Entities $entities - model collection or array of ids
	 * @param string $entity_type - leads/contacts/companies/catalog_elements
	 * @param array $metadata - links metadata
	 * @return bool|Collections\Links;
     */
	public function attachEntities($entities, $entity_type, array $metadata = [])
	{
		$entity_ids = ($entities instanceof Entities) ? $entities->fieldValues('id')->all() : $entities;
		$rows = [];
		foreach($entity_ids as $attach_id) {
			$rows[]= $this->linkRawData($attach_id, $entity_type, $metadata);
		}
		$links = $this->links()->createCollection($rows);
		return $links->save() ? $links : false;
	}
	
    /**
     * Delete entity link
	 * @param integer|Model $entity - id or model
	 * @param string $entity_type - leads/contacts/companies/catalog_elements
	 * @param array $metadata - link metadata
	 * @return bool;
     */
	public function detachEntity($entity, $entity_type, array $metadata = [])
	{
		$entity_id = is_object($entity) ? $entity->id : $entity;
		$link = $this->links()->create($this->linkRawData($entity_id, $entity_type, $metadata));
		return $link->delete();
	}
	
    /**
     * Delete entity Links
	 * @param array|Entities $entities - model collection or array of ids
	 * @param string $entity_type - leads/contacts/companies/catalog_elements
	 * @param array $metadata - links metadata
	 * @return bool;
     */
	public function detachEntities($entities, $entity_type, array $metadata = [])
	{
		$entity_ids = ($entities instanceof Entities) ? $entities->fieldValues('id')->all() : $entities;
		$rows = [];
		foreach($entity_ids as $detach_id) {
			$rows[]= $this->linkRawData($detach_id, $entity_type, $metadata);
		}
		$links = $this->links()->createCollection($rows);
		return $links->delete();
	}
	
    /**
     * Get link raw data
	 * @param integer $entity_id
	 * @param string $entity_type - leads/contacts/companies/catalog_elements
	 * @param array $metadata - link metadata
	 * @return array;
     */
	protected function linkRawData($entity_id, $entity_type, array $metadata = [])
	{
		$data = ['entity_id' => $this->id, 'to_entity_id' => $entity_id, 'to_entity_type' => $entity_type];
		if (!empty($metadata)) {
			$data['metadata'] = $metadata;
		}
		return $data;
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
