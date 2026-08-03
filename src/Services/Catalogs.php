<?php
/**
 * amoCRM API client Catalogs service
 */
namespace Ufee\AmoV4\Services;

class Catalogs extends Service
{
	const TYPE_REGULAR = 'regular';
	const TYPE_INVOICES = 'invoices';
	const TYPE_PRODUCTS = 'products';
	// system catalog, created by account with invoices, can not be created via API
	const TYPE_SUPPLIERS = 'suppliers';

	protected $api_path = '/api/v4/catalogs';
	protected $entity_key = 'catalogs';

	protected $entity_model = '\Ufee\AmoV4\Models\Catalog';
	protected $entity_collection = '\Ufee\AmoV4\Collections\Catalogs';
	protected $cache_keys = ['catalogs'];

	/**
	 * Get catalog elements service
	 * @param int $catalog_id
	 * @return CatalogElements
	 */
	public function elements(int $catalog_id)
	{
		return $this->instance->catalogElements($catalog_id);
	}
}
