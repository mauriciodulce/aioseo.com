<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf3cb38ee1fa2d6d58a2f050363d04d56
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'AIOSEO\\Plugin\\Extend\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'AIOSEO\\Plugin\\Extend\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf3cb38ee1fa2d6d58a2f050363d04d56::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf3cb38ee1fa2d6d58a2f050363d04d56::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf3cb38ee1fa2d6d58a2f050363d04d56::$classMap;

        }, null, ClassLoader::class);
    }
}
