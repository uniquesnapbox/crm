<?php

namespace App\Models;

use App\Traits\HasCompany;

class CompanyArea extends BaseModel
{
    use HasCompany;

    protected $table = 'company_areas';

    protected $guarded = ['id'];
}
