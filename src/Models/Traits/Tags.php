<?php
/**
 * amoCRM Model Trait
 */
namespace Ufee\AmoV4\Models\Traits;
use Ufee\AmoV4\Models\Model;

trait Tags
{
	/**
     * Get entity tags
	 * @return array
     */
	public function getTags()
	{
		return $this->embedded['tags'] ?? [];
	}
	
	/**
     * Set tags (will replace all existing tags)
	 * @param array $tags - ids or names
	 * @return Model
     */
    public function setTags(array $tags)
    {
		$first = reset($tags);
		if ($first === false) {
			return $this;
		}
		if (is_int($first)) {
			$tags = array_map(function($tag) {
				return ['id' => $tag];
			}, $tags);
		}
		else if (is_string($first)) {
			$tags = array_map(function($tag) {
				return ['name' => $tag];
			}, $tags);
		}
		$this->embedded['tags'] = $tags;
		$this->changed_embedded[]= 'tags';
		return $this;
	}
	
	/**
     * Reset tags (will replace all existing tags with none)
	 * @return Model
     */
    public function resetTags()
    {
		$this->embedded['tags'] = null;
		$this->changed_embedded[]= 'tags';
		return $this;
	}
	
	/**
     * Attach tag
	 * @param mixed $tag - id or name
	 * @return Model
     */
    public function attachTag($tag)
    {
		if (is_int($tag)) {
			$tag = ['id' => $tag];
		}
		else if (is_string($tag)) {
			$tag = ['name' => $tag];
		}
		$this->temporary['tags_to_add'][] = $tag;
		return $this;
	}
	
	/**
     * Detach tag
	 * @param mixed $tag - id or name
	 * @return Model
     */
    public function detachTag($tag)
    {
		if (is_int($tag)) {
			$tag = ['id' => $tag];
		}
		else if (is_string($tag)) {
			$tag = ['name' => $tag];
		}
		$this->temporary['tags_to_delete'][] = $tag;
		return $this;
	}
	
	/**
     * Attach tags
	 * @param array $tags
	 * @return Model
     */
    public function attachTags(array $tags)
    {
		foreach($tags as $tag) {
			$this->attachTag($tag);
		}
		return $this;
	}
	
	/**
     * Detach tags
	 * @param array $tags
	 * @return Model
     */
    public function detachTags(array $tags)
    {
		foreach($tags as $tag) {
			$this->detachTag($tag);
		}
		return $this;
	}
}
