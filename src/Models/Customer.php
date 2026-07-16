<?php
/**
 * amoCRM Customer model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Models\Traits;

class Customer extends WithCfield
{
	use Traits\Tags;
	use Traits\Tasks;
	use Traits\Notes;
	use Traits\Links;
	use Traits\LinkedContacts;
	use Traits\LinkedCompanies;

	public function setSegments(array $segments)
    {
		$first = reset($segments);
		if ($first === false) {
			return $this;
		}
		if (is_int($first)) {
			$segments = array_map(function($tag) {
				return ['id' => $tag];
			}, $segments);
		}

		$this->embedded['segments'] = $segments;
		$this->changed_embedded[]= 'segments';
		return $this;
	}
}
