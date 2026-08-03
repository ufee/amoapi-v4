<?php
/**
 * amoCRM Files (Drive) API service
 */
namespace Ufee\AmoV4\Services;
use Ufee\AmoV4\ApiClient;
use Ufee\AmoV4\Api\Paginate;
use Ufee\AmoV4\Collections;
use Ufee\AmoV4\Exceptions;
use Ufee\AmoV4\Models;

class Files extends Service
{
	protected $api_path = '/v1.0/files';
	protected $entity_key = 'files';

	protected $entity_model = '\Ufee\AmoV4\Models\File';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Files';

	/** @var array */
	protected $allowed_entity_types = ['leads', 'contacts', 'companies', 'customers'];

	/**
	 * Constructor
	 * @param ApiClient $client
	 * @param array $args
	 */
	public function __construct(ApiClient $client, array $args = [])
	{
		parent::__construct($client);
	}

	/**
	 * Service on load
	 * @return void
	 */
	protected function _boot()
	{
		$this->query_args['limit'] = 50;
	}

	/**
	 * Files are created via upload session
	 * @param array|object $data
	 * @return void
	 */
	public function add($data)
	{
		throw new \BadMethodCallException('Use files()->upload() to create files');
	}

	/**
	 * Create Drive API query
	 * @param string $method
	 * @param string $url
	 * @return \Ufee\AmoV4\Api\Query
	 */
	protected function driveQuery(string $method, string $url)
	{
		$query = $this->instance->query($method, $url);
		$query->setHost($this->getDriveHost());
		return $query;
	}

	/**
	 * Get Drive service host
	 * @return string
	 */
	public function getDriveHost()
	{
		$drive_url = $this->instance->getParam('drive_url');
		if (!$drive_url) {
			$account = $this->instance->cache->account();
			$drive_url = $account->drive_url ?? null;
			if (!$drive_url) {
				throw new Exceptions\AmoException('Account drive_url is empty. Request account with with=drive_url');
			}
			$this->instance->setParam('drive_url', $drive_url);
		}
		$host = parse_url($drive_url, PHP_URL_HOST);
		if (!$host) {
			$host = preg_replace('#^https?://#i', '', rtrim($drive_url, '/'));
		}
		return $host;
	}

	/**
	 * Create upload session
	 * @param array $params file_name, file_size, content_type?, file_uuid?, with_preview?
	 * @return object
	 */
	public function createSession(array $params)
	{
		if (empty($params['file_name']) || !isset($params['file_size'])) {
			throw new \InvalidArgumentException('createSession requires file_name and file_size');
		}
		$query = $this->driveQuery('POST', '/v1.0/sessions');
		$query->setJsonData($params);
		$query->execute();
		return $query->response->validated();
	}

	/**
	 * Upload file part by absolute upload URL
	 * @param string $upload_url
	 * @param string $binary
	 * @return object
	 */
	public function uploadPart(string $upload_url, string $binary)
	{
		$query = $this->instance->query('POST', $upload_url);
		$query->setHeader('Content-Type', 'application/octet-stream');
		$query->setRawData($binary);
		$query->execute();
		return $query->response->validated();
	}

	/**
	 * Upload file (path, binary string or resource)
	 * @param string|resource $source
	 * @param array $options file_name?, content_type?, file_uuid?, with_preview?, file_size?
	 * @return Models\File
	 */
	public function upload($source, array $options = [])
	{
		$close_handle = false;
		$handle = null;
		$binary = null;

		if (is_resource($source)) {
			$handle = $source;
			$file_name = $options['file_name'] ?? null;
			$file_size = $options['file_size'] ?? null;
			if (!$file_name || !$file_size) {
				$meta = stream_get_meta_data($handle);
				if (!$file_name && !empty($meta['uri']) && is_file($meta['uri'])) {
					$file_name = basename($meta['uri']);
				}
				if (!$file_size && !empty($meta['uri']) && is_file($meta['uri'])) {
					$file_size = filesize($meta['uri']);
				}
			}
			$content_type = $options['content_type'] ?? 'application/octet-stream';
		} else if (is_string($source) && is_file($source)) {
			$file_name = $options['file_name'] ?? basename($source);
			$file_size = filesize($source);
			$content_type = $options['content_type'] ?? (function_exists('mime_content_type') ? (mime_content_type($source) ?: 'application/octet-stream') : 'application/octet-stream');
			$handle = fopen($source, 'rb');
			if ($handle === false) {
				throw new \RuntimeException('Unable to open file: ' . $source);
			}
			$close_handle = true;
		} else if (is_string($source)) {
			$binary = $source;
			$file_name = $options['file_name'] ?? null;
			$file_size = $options['file_size'] ?? strlen($binary);
			$content_type = $options['content_type'] ?? 'application/octet-stream';
		} else {
			throw new \InvalidArgumentException('upload() expects file path, binary string or resource');
		}

		if (!$file_name || !$file_size) {
			if ($close_handle && is_resource($handle)) {
				fclose($handle);
			}
			throw new \InvalidArgumentException('upload() requires file_name and file_size');
		}

		$session_params = [
			'file_name' => $file_name,
			'file_size' => (int) $file_size,
			'content_type' => $content_type,
		];
		if (!empty($options['file_uuid'])) {
			$session_params['file_uuid'] = $options['file_uuid'];
		}
		if (array_key_exists('with_preview', $options)) {
			$session_params['with_preview'] = (bool) $options['with_preview'];
		}

		try {
			$session = $this->createSession($session_params);
			$upload_url = $session->upload_url;
			$max_part_size = (int) $session->max_part_size;
			if ($max_part_size <= 0) {
				throw new Exceptions\AmoException('Invalid max_part_size in upload session');
			}

			$result = null;
			if (!is_null($binary)) {
				$offset = 0;
				$length = strlen($binary);
				while ($offset < $length) {
					$chunk = substr($binary, $offset, $max_part_size);
					$result = $this->uploadPart($upload_url, $chunk);
					$offset += $max_part_size;
					if (!empty($result->next_url)) {
						$upload_url = $result->next_url;
					}
				}
			} else {
				while (!feof($handle)) {
					$chunk = fread($handle, $max_part_size);
					if ($chunk === false || $chunk === '') {
						break;
					}
					$result = $this->uploadPart($upload_url, $chunk);
					if (!empty($result->next_url)) {
						$upload_url = $result->next_url;
					}
				}
			}
		} finally {
			if ($close_handle && is_resource($handle)) {
				fclose($handle);
			}
		}

		if (!$result || empty($result->uuid)) {
			throw new Exceptions\AmoException('File upload failed: file model not returned');
		}
		return new Models\File((array) $result, $this);
	}

	/**
	 * Find file by UUID
	 * @param string|array $elem_id
	 * @param array $with
	 * @return Models\File|Collections\Files|null
	 */
	public function find($elem_id, $with = [])
	{
		if (is_array($elem_id)) {
			return $this->filter(['uuid' => implode(',', $elem_id)], $with)->fetchAll();
		}
		if (!is_string($elem_id) || $elem_id === '') {
			throw new \InvalidArgumentException('File UUID must be non-empty string or array of strings');
		}
		$query = $this->driveQuery('GET', $this->api_path . '/' . $elem_id);
		$query->execute();
		if (in_array($query->response->getCode(), [204, 404])) {
			return null;
		}
		$row = $query->response->validated();
		return new Models\File((array) $row, $this);
	}

	/**
	 * Get files by pages
	 * @param array $with
	 * @return Paginate
	 */
	public function paginate(array $with = [])
	{
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->driveQuery('GET', $this->api_path);
		$query->setArgs($query_args);
		return new Paginate($query, $this);
	}

	/**
	 * Filter files
	 * @param array $conditions
	 * @param array $with
	 * @return Paginate
	 */
	public function filter(array $conditions, array $with = [])
	{
		$query_args = $this->query_args;
		if (!empty($with)) {
			$query_args['with'] = join(',', $with);
		}
		$query = $this->driveQuery('GET', $this->api_path);
		$query->setArgs($query_args);
		$query->setArgs([
			'filter' => $conditions
		]);
		return new Paginate($query, $this);
	}

	/**
	 * Search files by term
	 * @param string $phrase
	 * @param array $with
	 * @return Paginate
	 */
	public function search(string $phrase, array $with = [])
	{
		return $this->filter(['term' => $phrase], $with);
	}

	/**
	 * Update file by UUID
	 * @param string|array $elem_id
	 * @param object|array|null $data
	 * @return object|null
	 */
	public function update($elem_id, $data = null)
	{
		if (!is_string($elem_id) || $elem_id === '') {
			throw new \InvalidArgumentException('File UUID must be non-empty string');
		}
		if (is_array($data)) {
			$data = (object) $data;
		}
		if (!is_object($data)) {
			throw new \InvalidArgumentException('File update data must be object or array');
		}
		if (property_exists($data, 'name') && property_exists($data, 'version_uuid')) {
			throw new \InvalidArgumentException('Fields name and version_uuid cannot be set simultaneously');
		}
		$query = $this->driveQuery('PATCH', $this->api_path . '/' . $elem_id);
		$query->setJsonData($data);
		$query->execute();
		return $query->response->validated();
	}

	/**
	 * Delete files by UUID
	 * @param string|array|object $uuids
	 * @return bool
	 */
	public function delete($uuids)
	{
		$payload = $this->normalizeUuidPayload($uuids);
		$query = $this->driveQuery('DELETE', $this->api_path);
		$query->setJsonData($payload);
		$query->execute();
		return $query->response->getCode() === 204;
	}

	/**
	 * Restore files by UUID
	 * @param string|array|object $uuids
	 * @return Collections\Files
	 */
	public function restore($uuids)
	{
		$payload = $this->normalizeUuidPayload($uuids);
		$query = $this->driveQuery('POST', $this->api_path . '/restore');
		$query->setJsonData($payload);
		$query->execute();
		if ($query->response->getCode() === 204) {
			return $this->createCollection();
		}
		$rows = $query->response->validatedEntities($this->entity_key);
		return $this->createCollection($rows);
	}

	/**
	 * Get file versions
	 * @param string $uuid
	 * @return Collections\FileVersions
	 */
	public function versions(string $uuid)
	{
		$query = $this->driveQuery('GET', $this->api_path . '/' . $uuid . '/versions');
		$query->execute();
		if ($query->response->getCode() === 204) {
			return new Collections\FileVersions();
		}
		$validated = $query->response->validated();
		$rows = $validated->_embedded->versions ?? [];
		$models = [];
		foreach ($rows as $row) {
			$models[] = new Models\FileVersion((array) $row, $this);
		}
		return new Collections\FileVersions($models);
	}

	/**
	 * Get entities linked to file (CRM API)
	 * @param string $uuid
	 * @return object
	 */
	public function getLinks(string $uuid)
	{
		$query = $this->instance->query('GET', '/api/v4/files/' . $uuid . '/links');
		$query->execute();
		return $query->response->validated();
	}

	/**
	 * Get files linked to entity (CRM API)
	 * @param string $entity_type
	 * @param int $entity_id
	 * @param array $args
	 * @return Collections\Collection
	 */
	public function getByEntity(string $entity_type, int $entity_id, array $args = [])
	{
		$this->assertEntityType($entity_type);
		$query = $this->instance->query('GET', '/api/v4/' . $entity_type . '/' . $entity_id . '/files');
		if (!empty($args)) {
			$query->setArgs($args);
		}
		$query->execute();
		if ($query->response->getCode() === 204) {
			return new Collections\Collection();
		}
		$validated = $query->response->validated();
		$rows = $validated->_embedded->files ?? [];
		$items = [];
		foreach ($rows as $row) {
			$items[] = (array) $row;
		}
		return new Collections\Collection($items);
	}

	/**
	 * Attach files to entity (CRM API)
	 * @param string $entity_type
	 * @param int $entity_id
	 * @param string|array $uuids
	 * @return bool
	 */
	public function attachToEntity(string $entity_type, int $entity_id, $uuids)
	{
		$this->assertEntityType($entity_type);
		$payload = $this->normalizeFileUuidPayload($uuids);
		$query = $this->instance->query('PUT', '/api/v4/' . $entity_type . '/' . $entity_id . '/files');
		$query->setJsonData($payload);
		$query->execute();
		return $query->response->getCode() === 202;
	}

	/**
	 * Detach files from entity (CRM API)
	 * @param string $entity_type
	 * @param int $entity_id
	 * @param string|array $uuids
	 * @return bool
	 */
	public function detachFromEntity(string $entity_type, int $entity_id, $uuids)
	{
		$this->assertEntityType($entity_type);
		$payload = $this->normalizeFileUuidPayload($uuids);
		$query = $this->instance->query('DELETE', '/api/v4/' . $entity_type . '/' . $entity_id . '/files');
		$query->setJsonData($payload);
		$query->execute();
		return $query->response->getCode() === 202;
	}

	/**
	 * @param string $entity_type
	 * @return void
	 */
	protected function assertEntityType(string $entity_type)
	{
		if (!in_array($entity_type, $this->allowed_entity_types, true)) {
			throw new \InvalidArgumentException('Entity type must be one of: ' . implode(', ', $this->allowed_entity_types));
		}
	}

	/**
	 * Normalize [{uuid: ...}] payload
	 * @param string|array|object $uuids
	 * @return array
	 */
	protected function normalizeUuidPayload($uuids)
	{
		if (is_string($uuids)) {
			return [['uuid' => $uuids]];
		}
		if (is_object($uuids)) {
			$uuids = [$uuids];
		}
		if (!is_array($uuids) || empty($uuids)) {
			throw new \InvalidArgumentException('UUIDs payload can not be empty');
		}
		$payload = [];
		foreach ($uuids as $item) {
			if (is_string($item)) {
				$payload[] = ['uuid' => $item];
			} else if (is_object($item) && !empty($item->uuid)) {
				$payload[] = ['uuid' => $item->uuid];
			} else if (is_array($item) && !empty($item['uuid'])) {
				$payload[] = ['uuid' => $item['uuid']];
			} else {
				throw new \InvalidArgumentException('Each item must contain uuid');
			}
		}
		return $payload;
	}

	/**
	 * Normalize [{file_uuid: ...}] payload
	 * @param string|array|object $uuids
	 * @return array
	 */
	protected function normalizeFileUuidPayload($uuids)
	{
		if (is_string($uuids)) {
			return [['file_uuid' => $uuids]];
		}
		if (is_object($uuids)) {
			$uuids = [$uuids];
		}
		if (!is_array($uuids) || empty($uuids)) {
			throw new \InvalidArgumentException('File UUIDs payload can not be empty');
		}
		$payload = [];
		foreach ($uuids as $item) {
			if (is_string($item)) {
				$payload[] = ['file_uuid' => $item];
			} else if (is_object($item) && !empty($item->file_uuid)) {
				$payload[] = ['file_uuid' => $item->file_uuid];
			} else if (is_object($item) && !empty($item->uuid)) {
				$payload[] = ['file_uuid' => $item->uuid];
			} else if (is_array($item) && !empty($item['file_uuid'])) {
				$payload[] = ['file_uuid' => $item['file_uuid']];
			} else if (is_array($item) && !empty($item['uuid'])) {
				$payload[] = ['file_uuid' => $item['uuid']];
			} else {
				throw new \InvalidArgumentException('Each item must contain file_uuid or uuid');
			}
		}
		return $payload;
	}
}
