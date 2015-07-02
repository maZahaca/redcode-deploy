<?php

namespace RedCode\Deploy\Package;
use RedCode\Deploy\Connection\Connection;

/**
 * @author maZahaca
 */ 
class TarPackage extends AbstractPackage
{
    /**
     * @inheritdoc
     */
    public function pack($versionName, $localPath, $files, $excludes = null)
    {
        $buildPath = $this->getBuildPath($localPath);
        if(!is_dir($buildPath) || !is_writable($buildPath)) {
            throw new \Exception(sprintf('Directory "%s" must be writable.', $buildPath));
        }

        $buildFileName = "build-{$versionName}.tgz";
        $exclude = '--exclude=./build ';
        if (!empty($excludes)) {
            $excludeArr = explode(' ', $excludes);
            $exclude .= implode(' ', array_map(function ($item) {
                return "--exclude={$item}";
            }, $excludeArr));
        }
        $buildFileName = "{$buildPath}/{$buildFileName}";
        `cd {$localPath} & tar -cvzf {$buildFileName} {$exclude} {$files} > /dev/null`;

        return $buildFileName;
    }

    /**
     * @inheritdoc
     */
    public function unpack(Connection $connection, $projectPath, $buildPath)
    {
        $connection->execute(sprintf('tar -C %s -xvzf %s', $projectPath, $buildPath));
    }
}
 