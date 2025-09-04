<?php
/**
 * amoCRM API client Links Collection
 */
namespace Ufee\AmoV4\Collections;
use Ufee\AmoV4\Models\Company;

class Links extends Entities
{
    /**
     * Get linked leads
	 * @return Leads|bool
     */
    public function leads()
    {
		return $this->entities('leads');
	}
	
    /**
     * Get linked contacts
	 * @return Contacts|bool
     */
    public function contacts()
    {
		return $this->entities('contacts');
	}
	
    /**
     * Get linked company
	 * @return Company|bool
     */
    public function company()
    {
		if (!$entities = $this->entities('companies')) {
			return false;
		}
		return $entities->first();
	}
	
    /**
     * Get linked entities
	 * @return string $entity_type - leads/contacts/companies
	 * @return Entities|bool
     */
    public function entities(string $entity_type)
    {
		$links = $this->find('to_entity_type', $entity_type);
		if (!$first = $links->first()) {
			return false;
		}
		$entity_ids = [];
		foreach($links as $link) {
			if ($link->to_entity_type == $entity_type) {
				$entity_ids[]= $link->to_entity_id;
			}
		}
		$instance = $first->service->instance;
		$service = $instance->$entity_type();
		
		$chunk_limit = $service->getQueryArg('limit');
		$chunks = array_chunk($entity_ids, $chunk_limit);
		$entities = $service->createCollection();
		
		foreach($chunks as $chunk_ids) {
			$entities->merge($service->find($chunk_ids));
		}
		return $entities;
	}
	
    /**
     * Only add links support
	 * @return bool
     */
    public function save()
    {
		if (!$first = $this->first()) {
			return false;
		}
		$service = $first->service;
		$raw = [];
		foreach($this->items as $k=>$item) {
			$row = $item->getChangedRawData();
			$row->request_id = $item->request_id = $item->to_entity_id.'_'.$item->to_entity_type;
			$raw[]= $row;
		}
		if (!$result = $service->add($raw)) {
			return false;
		}
		foreach($result as $row) {
			$key = $row->to_entity_id.'_'.$row->to_entity_type;
			if ($model = $this->find('request_id', $key)->first()) {
				foreach($row as $field=>$val) {
					if (in_array($field, ['request_id','_links'])) {
						continue;
					}
					$model->setSilent($field, $val);
				}
				$model->_saved();
			}
		}
		return true;
	}
	
    /**
     * Detach entity links
	 * @return bool
     */
    public function delete()
    {
		if (!$first = $this->first()) {
			return false;
		}
		$service = $first->service;
		$raw = [];
		foreach($this->items as $k=>$item) {
			$raw[]= $item->toArray();
		}
		if (!$result = $service->delete($raw)) {
			return false;
		}
		return true;
	}
}
