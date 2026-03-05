<?php
/**
 * amoCRM API client Sources service
 */
namespace Ufee\AmoV4\Services;

class Sources extends Service
{
	protected $api_path = '/api/v4/sources';
	protected $entity_key = 'sources';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Source';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Sources';
	protected $cache_keys = ['sources'];
	
    /**
     * Delete source by id or batch
	 * @param int|array $source_ids
	 * @return bool
     */
    public function remove($source_ids)
    {
		if (is_int($source_ids) || (is_string($source_ids) && ctype_digit($source_ids))) {
			$query = $this->instance->query('DELETE', $this->api_path . '/' . $source_ids);
			$query->execute();
			return $query->response->getCode() === 204;
		}
		if (!is_array($source_ids) || empty($source_ids)) {
			throw new \InvalidArgumentException('Source IDs must be integer/string or non empty array');
		}
		$payload = [];
		foreach ($source_ids as $id) {
			if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
				throw new \InvalidArgumentException('Each source ID must be integer/string');
			}
			$payload[] = ['id' => (int)$id];
		}
		$query = $this->instance->query('DELETE', $this->api_path);
		$query->setJsonData($payload);
		$query->execute();
		if ($query->response->getCode() === 204) {
			foreach($this->cache_keys as $cache_key) {
				$this->instance->cache->clear($cache_key);
			}
			return true;
		}
		return false;
	}
}
