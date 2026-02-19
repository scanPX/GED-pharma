<?php

namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'ged_system_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get value, decrypting if necessary.
     */
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && !empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value; // Fallback or empty?
            }
        }
        return $value;
    }

    /**
     * Set value, encrypting if necessary.
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && !empty($value)) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
