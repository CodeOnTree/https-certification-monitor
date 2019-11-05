<?php
namespace Tree\Util\HttpsCertification\Exception;
class InvalidDomainForHTTPSException extends CommonException {
    public function __construct(string $domain) {
        parent::__construct(sprintf('invalid domain [%s] for HTTPS', $domain));
    }
}