<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Models\CatalogElement;
use Ufee\AmoV4\Models\Link;
use Ufee\AmoV4\Collections\CatalogElements;
use Ufee\AmoV4\Collections\Entities;
use Ufee\AmoV4\Collections\Links;

trait LinkedCatalogElements
{
    /**
     * Create catalog element link
	 * @param integer|CatalogElement $entity - id or model
	 * @param integer|null $catalog_id - required for element id
	 * @param integer|float $quantity
	 * @return bool|Link;
     */
	public function attachCatalogElement($entity, $catalog_id = null, $quantity = 1)
	{
		return $this->attachEntity($entity, 'catalog_elements', [
			'catalog_id' => $this->linkCatalogId($entity, $catalog_id),
			'quantity' => $quantity
		]);
	}

    /**
     * Create catalog elements links
	 * @param array|CatalogElements $entities - model collection or array of ids
	 * @param integer|null $catalog_id - required for element ids
	 * @param integer|float $quantity
	 * @return bool|Links;
     */
	public function attachCatalogElements($entities, $catalog_id = null, $quantity = 1)
	{
		return $this->attachEntities($entities, 'catalog_elements', [
			'catalog_id' => $this->linkCatalogId($entities, $catalog_id),
			'quantity' => $quantity
		]);
	}

    /**
     * Delete catalog element link
	 * @param integer|CatalogElement $entity - id or model
	 * @param integer|null $catalog_id - required for element id
	 * @return bool;
     */
	public function detachCatalogElement($entity, $catalog_id = null)
	{
		return $this->detachEntity($entity, 'catalog_elements', [
			'catalog_id' => $this->linkCatalogId($entity, $catalog_id)
		]);
	}

    /**
     * Delete catalog elements links
	 * @param array|CatalogElements $entities - model collection or array of ids
	 * @param integer|null $catalog_id - required for element ids
	 * @return bool;
     */
	public function detachCatalogElements($entities, $catalog_id = null)
	{
		return $this->detachEntities($entities, 'catalog_elements', [
			'catalog_id' => $this->linkCatalogId($entities, $catalog_id)
		]);
	}

    /**
     * Get linked catalog elements
	 * @param integer $catalog_id - all catalogs by default
	 * @return CatalogElements|bool
     */
	public function catalogElements(int $catalog_id = 0)
	{
		$filter = ['to_entity_type' => 'catalog_elements'];
		if ($catalog_id) {
			$filter['to_catalog_id'] = $catalog_id;
		}
		return $this->links()->get($filter)->catalogElements();
	}

    /**
     * Get catalog id for element link
	 * @param integer|CatalogElement|array|CatalogElements $entity - id/model/ids/collection
	 * @param integer|null $catalog_id
	 * @return integer
     */
	protected function linkCatalogId($entity, $catalog_id = null)
	{
		if (!is_null($catalog_id)) {
			return (int)$catalog_id;
		}
		if ($entity instanceof Entities) {
			$entity = $entity->first();
		}
		if ($entity instanceof CatalogElement && $entity->catalog_id) {
			return (int)$entity->catalog_id;
		}
		throw new \InvalidArgumentException('Catalog element link required catalog_id argument');
	}
}
