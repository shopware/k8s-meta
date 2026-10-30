<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Configuration;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

final class ShopwareVersion
{
    public static function isV6_6(): bool
    {
        return InstalledVersions::isInstalled('shopware/core')
            && InstalledVersions::satisfies(new VersionParser(), 'shopware/core', '~6.6.0');
    }
}
