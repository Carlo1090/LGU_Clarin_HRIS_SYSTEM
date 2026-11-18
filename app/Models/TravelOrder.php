<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrder extends Model
{
    protected $table = 'travel_orders';

    protected $fillable = ['employee_id', 'travel_order_no', 'destination', 'departure_date', 'return_date', 'purpose', 'status'];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
