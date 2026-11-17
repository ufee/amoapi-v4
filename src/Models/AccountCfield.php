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
     * Find CF enum by id
	 * @param int $enum_id
	 * @return object|null
     */
    public function findEnumById(int $enum_id)
    {
		foreach ($this->getEnums() as $enum) {
			if ((int)$enum->id === $enum_id) {
				return $enum;
			}
		}
		return null;
	}

    /**
     * Find CF enum by code; enums without code are matched by value (WORK, MOB у телефонов)
	 * @param string $code
	 * @return object|null
     */
    public function findEnumByCode(string $code)
    {
		foreach ($this->getEnums() as $enum) {
			if (($enum->code ?? $enum->value ?? null) === $code) {
				return $enum;
			}
		}
		return null;
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
