<?php
namespace Tree\Util\HttpsCertification\Exception;
class ConfigFileNotExistsException extends CommonException {
    public function __construct() {
        parent::__construct('config file not exists');
    }
}