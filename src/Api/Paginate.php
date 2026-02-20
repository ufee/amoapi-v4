<?php
/**
 * Paginate API response
 */
namespace Ufee\AmoV4\Api;
use Ufee\AmoV4\Services\Service;
use Ufee\AmoV4\Collections\Entities;
use Ufee\AmoV4\Api\Query;
use Ufee\AmoV4\Exceptions;

class Paginate implements \Iterator
{
	protected $service;
	protected $query;
	
	protected $page = 1;
	protected $links;
	protected $models;
	
	protected $page_limit = 0;
	protected $page_loaded = 0;
	protected $total_items = 0;
	protected $models_loaded = 0;
		
    /**
     * Constructor
	 * @param Query $query
	 * @param ApiClient $client
     */
    public function __construct(Query $query, Service $service)
    {
        $this->query = $query;
        $this->service = $service;
		$this->query->setArgs(['page' => $this->page]);
	}
	
    /**
     * Get current page number
	 * @return integer
     */
	public function pageNum()
	{
		return $this->page;
	}
	
    /**
     * Set current page number
	 * @param int $page
	 * @return Paginate
     */
	public function setPageNum(int $page)
	{
		if ($this->page !== $page) {
			$this->links = null;
			$this->models = null;
		}
		$this->page = $page;
		$this->query->setArgs(['page' => $this->page]);
		return $this;
	}
	
    /**
     * Set limit for pages
	 * @param int $value numer or 0
	 * @return Paginate
     */
	public function maxPages(int $value)
	{
		$this->page_limit = $value;
		$this->page_loaded = 0;
		return $this;
	}
	
	/**
	 * Set page rows limit
	 * @param string $value
	 * @return Paginate
	 */
	public function maxRows(int $value)
	{
		$this->query->setArgs(['limit' => $value]);
		return $this;
	}
	
    /**
     * Get current page models collection
	 * @return Entities
     */
	public function fetchPage()
	{
		if (!$this->models) {
			$this->load();
		}
		return $this->models;
	}
	
    /**
     * Get all page models collection
	 * @return int $max_pages
	 * @return Entities
     */
	public function fetchAll(int $max_pages = 0)
	{
		$models = $this->service->createCollection();
		while (
			$this->valid() && ($new = $this->fetchPage()) && $new->count() && (!$max_pages || $this->page_loaded <= $max_pages)
		) {
			$models->merge($new);
			$this->setPageNum($this->page+1);
		}
		return $models;
	}
	
    /**
     * Check next page
	 * @return bool
     */
	public function hasNext()
	{
		if ($this->page_limit > 0 && $this->page_loaded >= $this->page_limit) {
			return false;
		}
		if (!$this->links) {
			$this->load();
		}
		if (!isset($this->links->next) || !isset($this->links->next->href)) {
			return false;
		}
		return true;
	}

    /**
     * Check next page, data will be loaded lazily
	 * @return void
     */
	public function next(): void
	{
		if (!isset($this->links->next) || !isset($this->links->next->href)) {
			return;
		}
		$this->page++;
		$this->links = null;
		$this->models = null;
		
		$this->query->setArgs(['page' => $this->page]);
	}
	
    /**
     * Switch to next page
	 * @return bool
     */
	public function nextPage()
	{
		if (!$this->hasNext()) {
			return false;
		}
		$this->next();
		return true;
	}
	
    /**
     * Check valid next page
	 * @return bool
     */
    public function valid(): bool
    {
        if ($this->page_limit > 0 && $this->page_loaded >= $this->page_limit) {
            return false;
        }
        if ($this->total_items && $this->models_loaded >= $this->total_items) {
            return false;
        }
        if ($this->page === 1 && !$this->models) {
            return true;
        }
		$has_next = $this->page > $this->page_loaded;
        return $has_next;
    }
	
    /**
     * Rewind pages
	 * @return void
     */
    public function rewind(): void
    {		
        $this->setPageNum(1);
		$this->page_loaded = 0;
		$this->models_loaded = 0;
		$this->links = null;
		$this->models = null;
    }
	
    /**
     * Get current page models
	 * @return Entities
     */
    public function current(): mixed
    {		
        return $this->fetchPage();
    }

    /**
     * Get current page
	 * @return integer
     */
    public function key(): mixed
    {
        return $this->page;
    }
	
    /**
     * Parse API raw response
	 * @return Paginate
     */
	protected function load()
	{
		if ($this->models) {
			return $this;
		}
		$this->query->prepare(true)->execute();
		$response = $this->query->response;

		if ($response->getCode() === 204) {
			$this->links = (object)[];
			$this->models = $this->service->createCollection();
			$this->page_loaded++;
			return $this;
		}
		$data = $response->validated();
		$entity_key = $this->service->entity_key;
		
		if (!isset($data->_embedded->{$entity_key})) {
			throw new Exceptions\AmoException('Invalid API response (no ' . $entity_key . '), code: ' . $response->getCode(), $response->getCode());
		}
		$this->page = $data->_page ?? 1;
		$this->links = $data->_links ?? (object)[];
		$this->models = $this->service->createCollection($data->_embedded->{$entity_key});
		$this->models_loaded += $this->models->count();
		$this->total_items = $data->_total_items ?? 0;
		$this->page_loaded++;
		return $this;
	}
	
    /**
     * Get api method
	 * @param string $target
	 * @return mixed
     */
	public function __get($target)
	{
		if (!in_array($target, ['query','page'])) {
			throw new \Exception('Invalid Paginate field: '.$target);
		}
		return $this->{$target};
	}
}
