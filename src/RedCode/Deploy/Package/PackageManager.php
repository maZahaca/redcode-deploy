<?php

namespace RedCode\Deploy\Package;

/**
 * @author maZahaca
 */ 
class PackageManager
{
    private static $allowedPackages = [
        'tar' => 'RedCode\Deploy\Package\TarPackage'
    ];


    public static function getAllowedPackages()
    {
        return array_keys(self::$allowedPackages);
    }


    /**
     * Get package maker
     *
     * @param $packageType
     * @return PackageInterface
     * @throws \Exception
     */
    public function getPacker($packageType)
    {
        if(!isset(self::$allowedPackages[$packageType])) {
            throw new \Exception('Unsupported package type');
        }

        return new self::$allowedPackages[$packageType];
    }
}
 