<?php

namespace RedCode\Deploy\Package;

/**
 * @author maZahaca
 */ 
abstract class AbstractPackage implements PackageInterface
{
    public function getBuildPath($projectLocalPath)
    {
        return $projectLocalPath . 'build/';
    }
}
 