<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Model;
use App\Models\GED\Departement;

class Fonction extends Model
{
    protected $fillable = ['name', 'departement_id'];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
}
