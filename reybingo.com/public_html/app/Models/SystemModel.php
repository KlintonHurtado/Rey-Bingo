<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemModel extends Model {
    protected $table = 'system';
    protected $primaryKey = 'id';

    protected $allowedFields = ['key', 'value'];

    public function updateValue(string $key, string $value): bool
    {
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            return $this->where('key', $key)->set(['value' => $value])->update();
        }

        return (bool) $this->insert(['key' => $key, 'value' => $value]);
    }
}

