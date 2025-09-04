<?php
/**
 * Amoapi Collection class
 * @author Vlad Ionov <vlad@f5.com.ru>
 */
namespace Ufee\AmoV4\Collections;
use \Ufee\AmoV4\Services\Service;

class Entities extends Collection
{
    /**
     * Save model in CRM
	 * @return bool
     */
    public function save()
    {
		if (!$first = $this->first()) {
			return false;
		}
		$service = $first->service;
		$chunk_limit = $service->getQueryArg('limit');
		$raw = [];
		foreach($this->items as $k=>$item) {
			$row = $item->getChangedRawData($first->id ? ['id'] : []);
			$row->request_id = $item->request_id = (string)$k;
			$raw[]= $row;
		}
		$chunks = array_chunk($raw, $chunk_limit);
		$operation = $first->id ? 'update' : 'add';
		$link = $operation === 'update' ? 'id' : 'request_id';
		
		foreach($chunks as $chunk_rows) {
			if (!$result = $service->$operation($chunk_rows)) {
				return false;
			}
			foreach($result as $row) {
				if ($model = $this->find($link, $row->$link)->first()) {
					foreach($row as $field=>$val) {
						if (in_array($field, ['request_id','_links'])) {
							continue;
						}
						$model->setSilent($field, $val);
					}
					$model->_saved();
				}
			}
		}
		return true;
	}
}
