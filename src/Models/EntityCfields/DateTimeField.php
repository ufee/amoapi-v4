<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class DateTimeField extends DateField
{
    /**
     * Set date value, naive value is treated as account local time
     * @param string|int|\DateTimeInterface|null $value
     * @return EntityField
     */
    public function setDate($value)
    {
        return $this->setDateTime($value);
    }

    /**
     * Set datetime value, naive value is treated as account local time
     * @param string|int|\DateTimeInterface|null $value
     * @return EntityField
     */
    public function setDateTime($value)
    {
        if (is_null($value)) {
            return $this->reset();
        }
        if (is_int($value)) {
            return $this->setValue($value);
        }
        return $this->setValue((int) $this->parseDate($value)->format('U'));
    }

    /**
     * Get formatted date
     * @param string $format
     * @return string|null
     */
    public function format(string $format = 'Y-m-d H:i:s')
    {
        return parent::format($format);
    }
}
