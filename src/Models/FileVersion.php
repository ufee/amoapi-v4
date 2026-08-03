<?php
/**
 * amoCRM File version model (Drive API)
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Services\Service;

class FileVersion extends Model
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
	 * Get download URL
	 * @return string|null
	 */
	public function getDownloadUrl()
	{
		if (!isset($this->links['download'])) {
			return null;
		}
		$link = $this->links['download'];
		if (is_object($link)) {
			return $link->href ?? null;
		}
		if (is_array($link)) {
			return $link['href'] ?? null;
		}
		return null;
	}
}
