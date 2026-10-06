<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara mass assignment.
     */
    protected $fillable = [
        'kode_kategori',
        'nama_kategori',
    ];

    /**
     * Hubungan One-to-Many: Satu kategori mempunyai banyak buku.
     */
    public function bukus(): HasMany
    {
        return $this->hasMany(Buku::class);
    }
}
