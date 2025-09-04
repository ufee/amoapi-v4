<?php
/**
 * amoCRM Custom Field model
 */
namespace Ufee\AmoV4\Models;

class AccountCfield extends Model
{
    /**
     * Get CF enums
	 * @return array
     */
    public function getEnums()
    {
		return $this->fields['enums'] ?? [];
	}
	
    /**
     * Check CF enum id
	 * @param int $enum_id
	 * @return bool
     */
    public function hasEnum(int $enum_id)
    {
		return in_array($enum_id, $this->getEnumIds());
	}
	
    /**
     * Check CF enum value
	 * @param string $name
	 * @return bool
     */
    public function hasValue(string $name)
    {
		return in_array($name, $this->getValues());
	}
	
    /**
     * Get CF enum ids
	 * @return array
     */
    public function getEnumIds()
    {
		$ids = [];
		$enums = $this->getEnums();
		foreach($enums as $enum) {
			$ids[]= $enum->id;
		}
		return $ids;
	}
	
    /**
     * Get CF enum values
	 * @return array
     */
    public function getValues()
    {
		$values = [];
		$enums = $this->getEnums();
		foreach($enums as $enum) {
			$values[]= $enum->value;
		}
		return $values;
	}
}
