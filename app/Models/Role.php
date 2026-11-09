<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Auditable;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Cacheable, Auditable;
}
