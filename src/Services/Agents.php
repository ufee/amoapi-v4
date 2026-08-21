<?php
/**
 * amoCRM API client Amma Agents service
 */
namespace Ufee\AmoV4\Services;

class Agents extends Service
{
	/**
	 * Компактная модель агента
	 */
	public const MODEL_SIZE_S = 'S';

	/**
	 * Средняя модель агента (значение по умолчанию)
	 */
	public const MODEL_SIZE_M = 'M';

	/**
	 * Крупная модель агента
	 */
	public const MODEL_SIZE_L = 'L';

	/**
	 * Транспорт MCP streamable-http (значение по умолчанию)
	 */
	public const TRANSPORT_STREAMABLE_HTTP = 'streamable-http';

	/**
	 * Транспорт MCP SSE
	 */
	public const TRANSPORT_SSE = 'sse';

	protected $api_path = '/api/v4/amma/agents';
	protected $entity_key = 'agents';

	protected $entity_model = '\Ufee\AmoV4\Models\Agent';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Agents';

	/**
	 * Service on load
	 * @return void
	 */
	protected function _boot()
	{
		$this->query_args['limit'] = 50;
	}

	/**
	 * Допустимые значения model_size
	 * @return string[]
	 */
	public static function modelSizeValues(): array
	{
		return [
			self::MODEL_SIZE_S,
			self::MODEL_SIZE_M,
			self::MODEL_SIZE_L,
		];
	}

	/**
	 * Допустимые значения mcp.transport
	 * @return string[]
	 */
	public static function transportValues(): array
	{
		return [
			self::TRANSPORT_STREAMABLE_HTTP,
			self::TRANSPORT_SSE,
		];
	}

	/**
	 * Find agent by UUID
	 * @param string|array $elem_id
	 * @param array $with
	 * @return \Ufee\AmoV4\Models\Agent|\Ufee\AmoV4\Collections\Agents|null
	 */
	public function find($elem_id, $with = [])
	{
		if (is_array($elem_id)) {
			$models = [];
			foreach ($elem_id as $id) {
				$model = $this->find($id, $with);
				if ($model) {
					$models[] = $model;
				}
			}
			$collection = $this->entity_collection;
			return new $collection($models);
		}
		$this->assertAgentId($elem_id);
		return parent::find($elem_id, $with);
	}

	/**
	 * Update agent by UUID
	 * @param string $elem_id
	 * @param object|array|null $data
	 * @return object
	 */
	public function update($elem_id, $data = null)
	{
		if (is_array($elem_id) && $data === null) {
			throw new \BadMethodCallException('Amma agents do not support batch update');
		}
		if (is_array($data)) {
			$data = (object) $data;
		}
		$this->assertAgentId($elem_id);
		if (!is_object($data)) {
			throw new \InvalidArgumentException('Agent update data must be object or array');
		}
		$data = $this->stripReadOnlyFields($data);
		return parent::update($elem_id, $data);
	}

	/**
	 * Delete agent by UUID
	 * @param string $agent_id
	 * @return bool
	 */
	public function remove($agent_id)
	{
		$this->assertAgentId($agent_id);
		$query = $this->instance->query('DELETE', $this->api_path . '/' . $agent_id);
		$query->execute();
		return $query->response->getCode() === 204;
	}

	/**
	 * Set page rows limit (1..50)
	 * @param int $value
	 * @return static
	 */
	public function maxPageRows(int $value)
	{
		if ($value < 1 || $value > 50) {
			throw new \InvalidArgumentException('Agents page limit must be an integer from 1 to 50');
		}
		return parent::maxPageRows($value);
	}

	/**
	 * Filter is not supported by Amma agents API
	 * @param array $conditions
	 * @param array $with
	 * @return void
	 */
	public function filter(array $conditions, array $with = [])
	{
		throw new \BadMethodCallException('Amma agents API does not support filter');
	}

	/**
	 * Search is not supported by Amma agents API
	 * @param string $phrase
	 * @param array $with
	 * @return void
	 */
	public function search(string $phrase, array $with = [])
	{
		throw new \BadMethodCallException('Amma agents API does not support search');
	}

	/**
	 * Validate agent UUID
	 * @param mixed $agent_id
	 * @return void
	 */
	protected function assertAgentId($agent_id)
	{
		if (!is_string($agent_id) || $agent_id === '') {
			throw new \InvalidArgumentException('Agent ID must be non-empty UUID string');
		}
		if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $agent_id)) {
			throw new \InvalidArgumentException('Agent ID must be a valid UUID');
		}
	}

	/**
	 * Remove read-only fields from update payload
	 * @param object $data
	 * @return object
	 */
	protected function stripReadOnlyFields($data)
	{
		foreach (['id', 'client_uuid', 'created_by', 'created_at', 'updated_at', 'request_id'] as $field) {
			if (property_exists($data, $field)) {
				unset($data->{$field});
			}
		}
		if (isset($data->mcp)) {
			if (is_array($data->mcp)) {
				$mcp = $data->mcp;
				unset($mcp['has_headers']);
				$data->mcp = $mcp;
			} else if (is_object($data->mcp)) {
				$data->mcp = clone $data->mcp;
				if (property_exists($data->mcp, 'has_headers')) {
					unset($data->mcp->has_headers);
				}
			}
		}
		return $data;
	}
}
