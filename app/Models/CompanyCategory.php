<?php

namespace App\Models;

use App\Traits\HasCompany;

class CompanyCategory extends BaseModel
{
    use HasCompany;

    protected $table = 'company_categories';

    protected $guarded = ['id'];
}
