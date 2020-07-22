<?php

namespace App\Models;

use Polyel\Database\Facade\DB;
use Polyel\Model\User as Model;
use Polyel\Auth\Contracts\EmailVerification;

class User extends Model implements EmailVerification
{
    protected $table = 'users';

    public function create(array $data)
    {
        return DB::table($this->table)->insertAndGetId($data);
    }
}