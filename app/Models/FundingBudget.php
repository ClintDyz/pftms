<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Uuid\Uuid;

class FundingBudget extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'funding_budgets';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'date_from',
        'date_to',
        'approved_budget',
        'is_active',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public static function boot() {
        parent::boot();
        self::creating(function($model) {
            $model->id = self::generateUuid();
        });
    }

    public static function generateUuid() {
        return Uuid::generate();
    }
}
