<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public function invoices(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
