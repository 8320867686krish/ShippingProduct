<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ["user_id", "enabled", "title", "shipping_rate", "shipping_rate_calculation", "method_name", "product_shipping_cost", "rate_per_item", "handling_fee", "applicable_countries", "countries", "method_if_not_applicable", "displayed_error_message", "show_method_for_admin", "min_order_amount", "max_order_amount"];

    public function setCountriesAttribute($value)
    {
        if (!empty($value)) {
            if (is_array($value) || is_object($value)) {
                $this->attributes['countries'] = json_encode($value);
            } else {
                $this->attributes['countries'] = $value;
            }
        } else {
            $this->attributes['countries'] = null;
        }
    }

    public function getCountriesAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return json_decode($value, true); // true to return as an associative array
    }

}
