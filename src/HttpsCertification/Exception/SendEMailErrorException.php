<?php
namespace Tree\Util\HttpsCertification\Exception;
class SendEMailErrorException extends CommonException {
    public function __construct(string $message = "") {
        parent::__construct("send email error with message :$message");
    }
}