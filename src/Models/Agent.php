<?php
/**
 * amoCRM Amma Agent model
 */
namespace Ufee\AmoV4\Models;

class Agent extends Model
{
	protected $required = [
		'name',
		'description',
		'system_prompt',
		'mcp'
	];

	/**
	 * Get changed raw model api data
	 * @param array $required
	 * @return object
	 */
	public function getChangedRawData(array $required = [])
	{
		$is_new = empty($this->fields['id']);
		$saved_required = $this->required;
		if (!$is_new) {
			$required = array_values(array_diff($required, ['id']));
			$this->required = [];
		}
		try {
			$data = parent::getChangedRawData($required);
		} finally {
			$this->required = $saved_required;
		}
		unset($data->id, $data->client_uuid, $data->created_by, $data->created_at, $data->updated_at);
		if (isset($data->mcp)) {
			$data->mcp = $this->sanitizeMcp($data->mcp);
		}
		return $data;
	}

	/**
	 * Set MCP server params
	 * @param string $url
	 * @param string|null $transport
	 * @param array $headers
	 * @return static
	 */
	public function setMcp(string $url, ?string $transport = null, array $headers = [])
	{
		$mcp = ['url' => $url];
		if ($transport !== null) {
			$mcp['transport'] = $transport;
		}
		if ($headers !== []) {
			$mcp['headers'] = $headers;
		}
		$this->mcp = $mcp;
		return $this;
	}

	/**
	 * Delete this agent
	 * @return bool
	 */
	public function delete()
	{
		if (empty($this->fields['id'])) {
			return false;
		}
		return $this->service->remove($this->id);
	}

	/**
	 * Drop read-only has_headers from MCP payload
	 * @param mixed $mcp
	 * @return mixed
	 */
	protected function sanitizeMcp($mcp)
	{
		if (is_array($mcp)) {
			unset($mcp['has_headers']);
			return $mcp;
		}
		if (is_object($mcp)) {
			$mcp = clone $mcp;
			if (property_exists($mcp, 'has_headers')) {
				unset($mcp->has_headers);
			}
			return $mcp;
		}
		return $mcp;
	}
}
