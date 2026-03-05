<?php
/**
 * amoCRM API client Pipelines service
 */
namespace Ufee\AmoV4\Services;

class Pipelines extends Service
{
	protected $api_path = '/api/v4/leads/pipelines';
	protected $entity_key = 'pipelines';
	
	protected $entity_model = '\Ufee\AmoV4\Models\Pipeline';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Pipelines';
	protected $cache_keys = ['pipelines'];
	
    /**
     * Delete pipeline
	 * @param integer $pipeline_id
	 * @return bool
     */
	public function delete($pipeline_id)
	{
		$query = $this->instance->query('DELETE', $this->api_path.'/'.$pipeline_id);
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
