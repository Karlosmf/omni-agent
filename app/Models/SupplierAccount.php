<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'bank_name',
        'account_number',
        'cbu',
        'alias',
        'currency',
        'notes',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
