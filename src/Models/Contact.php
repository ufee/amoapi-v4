<?php
/**
 * amoCRM Contact model
 */
namespace Ufee\AmoV4\Models;
use Ufee\AmoV4\Models\Traits;

class Contact extends WithCfield
{
	use Traits\Tags;
	use Traits\Tasks;
	use Traits\Notes;
	use Traits\Links;
	use Traits\LinkedLeads;
	use Traits\LinkedCompanies;
}
