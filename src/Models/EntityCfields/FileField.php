<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;
use Ufee\AmoV4\Models\File;

class FileField extends EntityField
{
	/**
	 * Whether field has a file uuid
	 * @return bool
	 */
	public function hasFile(): bool
	{
		return $this->getUuid() !== null;
	}

	/**
	 * Get file uuid
	 * @return string|null
	 */
	public function getUuid(): ?string
	{
		$value = $this->getValue();
		if (is_null($value) || $value === '') {
			return null;
		}
		if (is_string($value)) {
			return $value;
		}
		$uuid = $this->getValueProp('file_uuid');
		if ($uuid !== null && $uuid !== '') {
			return $uuid;
		}
		$uuid = $this->getValueProp('uuid');
		return ($uuid !== null && $uuid !== '') ? $uuid : null;
	}

	/**
	 * Get file version uuid
	 * @return string|null
	 */
	public function getVersionUuid(): ?string
	{
		return $this->getValueProp('version_uuid');
	}

	/**
	 * Get file name
	 * @return string|null
	 */
	public function getFileName(): ?string
	{
		$name = $this->getValueProp('file_name');
		if ($name !== null && $name !== '') {
			return $name;
		}
		return $this->getValueProp('name');
	}

	/**
	 * Get file size in bytes
	 * @return int|null
	 */
	public function getFileSize(): ?int
	{
		$size = $this->getValueProp('file_size');
		if ($size === null) {
			$size = $this->getValueProp('size');
		}
		return $size === null ? null : (int) $size;
	}

	/**
	 * Load File model from Drive API
	 * @return File|null
	 */
	public function getFile(): ?File
	{
		if (!$uuid = $this->getUuid()) {
			return null;
		}
		return $this->model->service->instance->files()->find($uuid);
	}

	/**
	 * Set file cf value from File model, array or object
	 * @param File|array|object $file
	 * @return FileField
	 */
	public function setFile($file): self
	{
		return $this->setValue((object) $this->normalizeFileValue($file));
	}

	/**
	 * Reset file cf value.
	 * File fields require exactly one values element; empty array is rejected by API.
	 * @return EntityField
	 */
	public function reset(): self
	{
		$this->data->values = [
			(object)['value' => null]
		];
		$this->model->cfChanged($this->data->field_id);
		return $this;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	protected function getValueProp(string $key): mixed
	{
		$value = $this->getValue();
		if (is_object($value) && property_exists($value, $key)) {
			return $value->{$key};
		}
		if (is_array($value) && array_key_exists($key, $value)) {
			return $value[$key];
		}
		return null;
	}

	/**
	 * @param File|array|object $file
	 * @return array
	 */
	protected function normalizeFileValue($file): array
	{
		if ($file instanceof File) {
			$value = ['file_uuid' => $file->uuid];
			if (!empty($file->version_uuid)) {
				$value['version_uuid'] = $file->version_uuid;
			}
			if (!empty($file->name)) {
				$value['file_name'] = $file->name;
			}
			if (isset($file->size)) {
				$value['file_size'] = (int) $file->size;
			}
			return $value;
		}
		if (is_object($file)) {
			$file = (array) $file;
		}
		if (!is_array($file)) {
			throw new \InvalidArgumentException('setFile() expects File model, array or object');
		}
		$uuid = $file['file_uuid'] ?? $file['uuid'] ?? null;
		if (empty($uuid) || !is_string($uuid)) {
			throw new \InvalidArgumentException('setFile() requires file_uuid or uuid');
		}
		$value = ['file_uuid' => $uuid];
		if (!empty($file['version_uuid'])) {
			$value['version_uuid'] = $file['version_uuid'];
		}
		$name = $file['file_name'] ?? $file['name'] ?? null;
		if (!empty($name)) {
			$value['file_name'] = $name;
		}
		if (isset($file['file_size'])) {
			$value['file_size'] = (int) $file['file_size'];
		} else if (isset($file['size'])) {
			$value['file_size'] = (int) $file['size'];
		}
		return $value;
	}
}
