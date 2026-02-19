<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use App\Models\GED\Entity;
use App\Models\GED\Fonction;

class Departement extends Model
{
    protected $fillable = ['name', 'entitie_id'];

    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entitie_id');
    }

    public function fonctions()
    {
        return $this->hasMany(Fonction::class);
    }
}
