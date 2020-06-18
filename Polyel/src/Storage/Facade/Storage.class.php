<?php

namespace Polyel\Storage\Facade;

use Polyel;

/**
 * @method static drive($drive = null)
 * @method static access($driver, $root)
 * @method static write($filePath, $contents = "")
 * @method static size($filePath)
 * @method static read($filePath)
 * @method static prepend($filePath, $contents)
 * @method static append($filePath, $contents)
 * @method static copy($source, $dest)
 * @method static move($oldName, $newName)
 * @method static delete($filePath)
 * @method static makeDir($dirPath, $mode = 0777)
 * @method static removeDir($dirPath)
 */
class Storage
{
    public static function __callStatic($method, $arguments)
    {
        if($method === 'drive' || $method === 'access')
        {
            return Polyel::call(Polyel\Storage\Storage::class)->$method(...$arguments);
        }

        return Polyel::call(Polyel\Storage\Storage::class)->drive(null)->$method(...$arguments);
    }
}