<?php

namespace RedCode\Deploy\Package;
use RedCode\Deploy\Connection\Connection;

/**
 * @author maZahaca
 */
interface PackageInterface
{
    /**
     * Packing files to the package
     *
     * @param string $versionName
     * @param string $localPath
     * @param string $files
     * @param string|null $excludes
     * @return string Package file path
     */
    public function pack($versionName, $localPath, $files, $excludes = null);

    /**
     * Extracting files
     *
     * @param Connection $connection
     * @param string $projectPath
     * @param string $buildPath
     * @return mixed
     */
    public function unpack(Connection $connection, $projectPath, $buildPath);
} 