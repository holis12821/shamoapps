<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'categories_id',
        'tags'
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    protected $appends = [
        'formatted'
    ];

    /* ============================
     | Accessors
     |============================ */
    public function getFormattedAttribute(): array
    {
        return [
            'price' => $this->rupiah($this->price),
        ];
    }

    private function rupiah($amount): string
    {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }

    public function galleries()
    {
        return $this->hasMany(ProductGallery::class, 'products_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'categories_id', 'id');
    }
}
