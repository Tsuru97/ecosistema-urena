<?php

namespace App\Observers;

use App\Models\Attribute;

class AttributeObserver
{
    public function created(Attribute $attribute): void
    {
        Attribute::flushCache();
    }

    public function updated(Attribute $attribute): void
    {
        Attribute::flushCache();
    }

    public function deleted(Attribute $attribute): void
    {
        Attribute::flushCache();
    }

    public function restored(Attribute $attribute): void
    {
        Attribute::flushCache();
    }
}
