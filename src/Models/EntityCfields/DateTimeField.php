<?php
/**
 * amoCRM Custom Entity Custom Field model
 */
namespace Ufee\AmoV4\Models\EntityCfields;

class DateTimeField extends DateField
{
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
