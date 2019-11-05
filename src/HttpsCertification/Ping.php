<?php
namespace Tree\Util\HttpsCertification;
class Ping {
    private $domain, $port;
    public function __construct(string $domain, ?int $port = 443) {
        $this->domain = $domain;
        $this->port = $port;
    }
    public function isAvailable(): bool {
        $fp = @fsockopen($this->domain, $this->port, $errno, $errstr, 30);
        if (! $fp) {
            return false;
        } else {
            fclose($fp);
            return true;
        }
    }
}