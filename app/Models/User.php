<?php

namespace App\Models;

use Polyel\Model\Model;
use Polyel\Database\Facade\DB;

class User extends Model
{
    protected $table = 'users';

    public function create(array $data)
    {
        return DB::table($this->table)->insertAndGetId($data);
    }
}