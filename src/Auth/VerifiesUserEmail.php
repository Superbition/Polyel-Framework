<?php

namespace Polyel\Auth;

use Polyel\Database\Facade\DB;

trait VerifiesUserEmail
{
    public function hasVerifiedEmail()
    {
        $userId = $this->auth->userId();

        if(is_null($userId))
        {
            return false;
        }

        $emailVerifiedAt = DB::table('user')->where('id', '=', $userId)->value('email_verified_at');

        if(!is_null($emailVerifiedAt))
        {
            return true;
        }

        return false;
    }

    public function markEmailAsVerified()
    {
        $userId = $this->auth->userId();

        if(is_null($userId))
        {
            return false;
        }

        DB::table('user')->where('id', '=', $userId)->update([
           'email_verified_at' => date("Y-m-d H:i:s"),
        ]);

        return true;
    }
}