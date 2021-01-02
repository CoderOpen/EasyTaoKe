<?php
/**
 * 2021年01月02日 14:33
 */

/*
 * This file is part of the onecoder/easytaoke.
 *
 * (c) LaJun <lajun@onecoder.group>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace  OneCoder\EasyTaoKe;

/**
 * Class Factory.
 */
class Factory
{
    /**
     * @param string $name
     * @param array  $config
     *
     * @return \EasyWeChat\Kernel\ServiceContainer
     */
    public static function make($name, array $config)
    {
        $namespace   = ucwords($name);
        $application = "\\OneCoder\\EasyTaoKe\\{$namespace}\\Application";

        return new $application($config);
    }

    /**
     * Dynamically pass methods to the application.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::make($name, ...$arguments);
    }
}
