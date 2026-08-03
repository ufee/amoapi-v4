<?php
/**
 * amoCRM Catalog model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Services;

class Catalog extends Model
{
	protected $required = [
		'name'
	];

	/**
	 * Get catalog elements service
	 * @return Services\CatalogElements
	 */
	public function elements()
	{
		return $this->service->instance->catalogElements($this->id);
	}

	/**
	 * Create catalog element
	 * @param array $fields
	 * @return CatalogElement
	 */
	public function createElement(array $fields = [])
	{
		return $this->elements()->create($fields);
	}

	/**
	 * Get catalog Custom Fields service
	 * @return Services\CustomFields
	 */
	public function customFields()
	{
		return $this->service->instance->customFields('catalogs', $this->id);
	}
}
