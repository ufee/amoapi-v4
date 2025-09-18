<?php
/**
 * amoCRM API client PipelineStatuses service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;

class PipelineStatuses extends Service
{
	protected $api_path = '/api/v4/leads/pipelines/{pipeline_id}/statuses';
	protected $entity_key = 'statuses';
	
	protected $entity_model = '\Ufee\AmoV4\Models\PipelineStatus';
	protected $entity_collection = '\Ufee\AmoV4\Collections\PipelineStatuses';
	
    /**
     * Constructor
	 * @param ApiClient $client
	 * @param array $args
     */
    public function __construct(ApiClient $client, array $args)
    {
        if (!$pipeline_id = (int)current($args)) {
			throw new \InvalidArgumentException('PipelineStatuses Service required pipeline_id argument');
		}
		$this->client_id = $client->client_id;
		$this->api_path = str_replace('{pipeline_id}', $pipeline_id, $this->api_path);
		$this->_boot();
	}
	
    /**
     * Delete status
	 * @param integer $status_id
	 * @return bool
     */
	public function delete($status_id)
	{
		$query = $this->instance->query('DELETE', $this->api_path.'/'.$status_id);
		$query->execute();
		return $query->response->getCode() === 204;
	}
}
