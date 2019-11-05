<?php
namespace Tree\Util\HttpsCertification;
class Monitor {
    private $certification_params, $domain;
    public function __construct(string $domain, ?int $port = 443) {
        $context = stream_context_create([
            "ssl" => [
                "capture_peer_cert_chain" => true
            ]
        ]);
        $socket = @stream_socket_client("ssl://$domain:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (! $socket) {
            if (! (new Ping($domain, $port))->isAvailable()) {
                throw new Exception\InvalidDomainForPingException($domain);
            }
            throw new Exception\InvalidDomainForHTTPSException($domain);
        }
        $this->certification_params = @stream_context_get_params($socket);
        if (! $this->certification_params) {
            if (! (new Ping($domain, $port))->isAvailable()) {
                throw new Exception\InvalidDomainForPingException($domain);
            }
            throw new Exception\InvalidDomainForHTTPSException($domain);
        }
        $this->domain = $domain;
    }
    public function getValiditySpan() {
        foreach ($this->certification_params["options"]["ssl"]["peer_certificate_chain"] as $source) {
            $info = openssl_x509_parse($source);
            if (strpos($info['name'], $this->domain)) {
                return [
                    'from' => $info['validFrom_time_t'],
                    'to' => $info['validTo_time_t']
                ];
            }
        }
    }
}