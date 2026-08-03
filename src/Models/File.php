<?php
/**
 * amoCRM File model (Drive API)
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Collections;
use Ufee\AmoV4\Exceptions;
use Ufee\AmoV4\Services\Service;

class File extends Model
{
	protected $links = [];

	/**
	 * Constructor
	 * @param array $data
	 * @param Service $service
	 */
	public function __construct(array $data, Service $service)
	{
		$this->links = isset($data['_links']) ? (array) $data['_links'] : [];
		parent::__construct($data, $service);
	}

	/**
	 * Get changed raw model api data
	 * @param array $required
	 * @return object
	 */
	public function getChangedRawData(array $required = [])
	{
		$data = $this->temporary;
		$changed_fields = array_unique(array_merge($this->changed_fields, $this->required, $required));
		foreach ($changed_fields as $field) {
			if (!array_key_exists($field, $this->fields)) {
				throw new Exceptions\AmoException(static::getBasename() . ' required field value not found: ' . $field);
			}
			$data[$field] = $this->fields[$field];
		}
		return (object) $data;
	}

	/**
	 * Save file changes (name / active version)
	 * @return bool
	 */
	public function save()
	{
		if (empty($this->fields['uuid'])) {
			throw new Exceptions\AmoException('File cannot be created via save(), use files()->upload()');
		}
		if (!$result = $this->service->update($this->uuid, $this->getChangedRawData())) {
			return false;
		}
		foreach ($result as $field => $val) {
			if (in_array($field, ['request_id', '_links'], true)) {
				continue;
			}
			$this->setSilent($field, $val);
		}
		if (isset($result->_links)) {
			$this->links = (array) $result->_links;
		}
		$this->_saved();
		return true;
	}

	/**
	 * Delete file
	 * @return bool
	 */
	public function delete()
	{
		if (empty($this->fields['uuid'])) {
			return false;
		}
		return $this->service->delete($this->uuid);
	}

	/**
	 * Restore file
	 * @return bool
	 */
	public function restore()
	{
		if (empty($this->fields['uuid'])) {
			return false;
		}
		$files = $this->service->restore($this->uuid);
		if (!$file = $files->first()) {
			return false;
		}
		foreach ($file->toArray() as $field => $val) {
			$this->setSilent($field, $val);
		}
		$this->links = $file->getLinksRaw();
		$this->_saved();
		return true;
	}

	/**
	 * Get file versions
	 * @return Collections\FileVersions
	 */
	public function versions()
	{
		return $this->service->versions($this->uuid);
	}

	/**
	 * Get entities linked to this file
	 * @return object
	 */
	public function getEntityLinks()
	{
		return $this->service->getLinks($this->uuid);
	}

	/**
	 * Get download URL
	 * @return string|null
	 */
	public function getDownloadUrl()
	{
		return $this->linkHref('download');
	}

	/**
	 * Get version download URL
	 * @return string|null
	 */
	public function getDownloadVersionUrl()
	{
		return $this->linkHref('download_version');
	}

	/**
	 * Get raw _links
	 * @return array
	 */
	public function getLinksRaw()
	{
		return $this->links;
	}

	/**
	 * @param string $key
	 * @return string|null
	 */
	protected function linkHref(string $key)
	{
		if (!isset($this->links[$key])) {
			return null;
		}
		$link = $this->links[$key];
		if (is_object($link)) {
			return $link->href ?? null;
		}
		if (is_array($link)) {
			return $link['href'] ?? null;
		}
		return null;
	}
}
