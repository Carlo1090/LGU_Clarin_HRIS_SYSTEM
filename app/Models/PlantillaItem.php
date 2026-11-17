<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaItem extends Model
{
    protected $fillable = ['department_id', 'position_id', 'item_number', 'allocated_count', 'filled_count', 'status', 'fiscal_year'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
