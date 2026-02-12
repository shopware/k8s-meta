<?php

declare(strict_types=1);

namespace Shopware\K8sMeta;

use Composer\InstalledVersions;

final class VersionChecker
{
    public static function isPackageVersionLessThan(string $packageName, string $targetVersion): bool
    {
        if (!InstalledVersions::isInstalled($packageName)) {
            return false;
        }

        $version = InstalledVersions::getPrettyVersion($packageName) ?? InstalledVersions::getVersion($packageName);
        if ($version === null) {
            return false;
        }

        return version_compare($version, $targetVersion, '<');
    }
}
