<?php

namespace Denkwerk\DwContentElements\Utility;

use \TYPO3\CMS\Core\Utility\GeneralUtility as GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 André Laugks <andre.laugks@denkwerk.com>, denkwerk GmbH
 *  (c) 2015 Sascha Zander <sascha.zander@denkwerk.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * **************************************************************/


/**
 * ToDo: Wie mit Pfaden unter Windows umgehen, c:/, d:/?
 *
 * Class Pathes
 * @package dw_content_elements
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Pathes
{

    /**
     *
     * @param $path
     * @return string
     */
    private function leadingSlash($path)
    {
        $slash = '/';
        /**
         * Workaround for Windows.
         */
        if (preg_match('/^[a-z]\:/i', $path)) {
            $slash = '';
        }
        return $slash . $path;
    }

    /**
     *
     * @param array $list
     * @return string
     */
    public static function convertFolderArrayToString(array $list)
    {
        $result = '';

        $list[0] = self::replaceBackSlashToSlash($list[0]);
        $list[1] = self::replaceBackSlashToSlash($list[1]);
        if (strpos($list[1], $list[0]) !== false) {
            $result = $list[1];
        } else {
            $result = implode('/', $list);
        }

        return $result;
    }

    /**
     *
     * @param $path
     * @return mixed
     */
    private static function replaceBackSlashToSlash($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     *
     * @param array|string array or string
     * @return string
     */
    public static function concat()
    {
        $pathes = array();
        foreach (func_get_args() as $arg) {
            if (is_array($arg) === true) {
                $pathes = array_merge($pathes, $arg);
            }
            if (is_string($arg) === true) {
                array_push($pathes, $arg);
            }
        }

        /** @var \Denkwerk\DwContentElements\Utility\Pathes $pathesUtility */
        $pathesUtility = GeneralUtility::makeInstance(
            'Denkwerk\\DwContentElements\\Utility\\Pathes'
        );

        return $pathesUtility->leadingSlash(
            implode(
                '/',
                GeneralUtility::trimExplode(
                    '/',
                    self::convertFolderArrayToString($pathes),
                    true
                )
            )
        );
    }

    /**
     * Return a files recursive as array
     *
     * @param $dir
     * @param array $results
     * @return array
     */
    public static function getAllDirFiles($dir, &$results = array())
    {

        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);

            if (is_file($path)) {
                //Set the filename without extension as key
                $results[str_replace('.' . pathinfo($path, PATHINFO_EXTENSION), '', $value)] = $path;
            } elseif (is_dir($path) &&
                !in_array($value, array(".", ".."))
            ) {
                self::getAllDirFiles($path, $results);
            }
        }
        return $results;
    }
}
