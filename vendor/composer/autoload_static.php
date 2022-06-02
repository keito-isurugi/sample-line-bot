<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb0b35c367b8510ff0f933d2e12fb19dc
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'LINE\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'LINE\\' => 
        array (
            0 => __DIR__ . '/..' . '/linecorp/line-bot-sdk/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb0b35c367b8510ff0f933d2e12fb19dc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb0b35c367b8510ff0f933d2e12fb19dc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb0b35c367b8510ff0f933d2e12fb19dc::$classMap;

        }, null, ClassLoader::class);
    }
}