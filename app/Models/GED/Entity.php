<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $fillable = ['name', 'description', 'image'];

    public function departements()
    {
        return $this->hasMany(Departement::class, 'entitie_id');
    }
}
