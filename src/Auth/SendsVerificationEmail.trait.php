<?php

namespace Polyel\Auth;

use App\Email\VerificationEmail;
use Polyel\Email\Facade\SendEmail;
use Polyel\Encryption\Facade\Crypt;

trait SendsVerificationEmail
{
    /*
     * Sends a verification email which can be used to
     * validated a user against an active email based on a signed URL.
     */
    public function sendVerificationEmail($name, $to)
    {
        $id = $this->auth->userId();

        if(exists($id))
        {
            $url = $this->verificationUrl($id, $to);

            SendEmail::to($to)->send(new VerificationEmail($name, $url));

            return true;
        }

        return false;
    }

    /*
     * Create a signed URL that will be used for email
     * verification.
     */
    public function verificationUrl($id, $email)
    {
        // The email hash and timestamp are part of the signed URL
        $email = sha1($email);
        $expire = time();

        // Build up the signed URL and its parameters
        $url = "/email/verify/$id/$email/$expire";

        // Get the apps encryption key
        $key = Crypt::getEncryptionKey();

        // Create a signed signature to protect the validity of the email verification URL
        $signature = hash_hmac('sha256', $url, $key);

        // Add the signature to the end of the URL as a query parameter
        $url .= "?sig=$signature";

        // Give back the fully signed URL
        return $url;
    }
}