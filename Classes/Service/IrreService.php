<?php
namespace Denkwerk\DwContentElements\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Sascha Zander <sascha.zander@denkwerk.com>, denkwerk
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
 ***************************************************************/

/**
 * IrreService
 *
 * @package dw_content_elements
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class IrreService
{

    /**
     *  Set data for Inline Relational Record Editing entry
     *  If set the repositoryName the function will call the magic function "findByForeignUid"
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObj
     * @param string $tableName Name of the table
     * @param string $repositoryName Name of the repository if any repository exist. (Optional)
     * @return array
     */
    public static function getRelations($contentObj, $tableName, $repositoryName = '')
    {
        $result = array();

        // Check if field "foreign_uid" exists on table
        $fieldsInDatabase = $GLOBALS['TYPO3_DB']->admin_get_fields($tableName);
        if (empty($fieldsInDatabase) === false &&
            array_key_exists("foreign_uid", $fieldsInDatabase)
        ) {
            // If "$repositoryName" is not set. Get the table data by single select
            if ($contentObj->data[$tableName] > 0 &&
                empty($repositoryName)
            ) {
                $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    '*',
                    $tableName,
                    'foreign_uid = ' . $contentObj->data['uid'] .
                    (TYPO3_MODE == 'BE' ?
                        \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableName)
                        . ' AND ' . $tableName . '.deleted=0' :
                        $contentObj->enableFields($tableName)
                    ),
                    '',
                    'sorting'
                );

                foreach ($rows as $row) {
                    // Get "tt_content" content elements of the relations if it exist a row "content_elements"
                    array_push($result, self::getContentElements($contentObj, $row, $tableName));
                }
            }


            // Get the IRRE data by the repository magic function "findByForeignUid"
            if ($contentObj->data[$tableName] > 0 &&
                empty($repositoryName) === false
            ) {
                /*** @var $extbaseObjectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
                $extbaseObjectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
                );
                /*** @var $repository \TYPO3\CMS\Extbase\Persistence\Repository */
                $repository = $extbaseObjectManager->get($repositoryName);

                // Get the table data by the given repository
                $rows = $repository->findByForeignUid($contentObj->data['uid']);

                if (empty($rows) === false) {
                    $result = $rows;
                }
            }
        } else {
            // Write into the sys_log about the missing field
            \Denkwerk\DwContentElements\Utility\Logger::simpleErrorLog(
                'DWC: IRRE Service: Column "foreign_uid" not found on table "' . $tableName . '"',
                $tableName,
                $contentObj->data['uid'],
                $contentObj->data['pid']
            );
        }

        return $result;
    }

    /**
     * Get "tt_content" content elements of the relations if it exist a row "content_elements"
     * @deprecated since 1.2 will be removed in 2.0
     * Please use the "getRelations($repositoryName)" function
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObj
     * @param array $data
     * @param string $parentTable
     * @return array
     */
    public static function getContentElements($contentObj, $data, $parentTable)
    {
        if (is_array($data)
        ) {
            if ($data['content_elements'] != null && empty($data['content_elements']) === false) {
                $elementRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    'uid',
                    'tt_content',
                    'foreign_uid = ' . $data['uid'] .
                    (TYPO3_MODE == 'BE' ?
                        \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content')
                        . ' AND tt_content.deleted=0' :
                        $contentObj->enableFields('tt_content')
                    ) . 'AND parent_table = "' . $parentTable . '"',
                    '',
                    'sorting'
                );
                $contentElements = array();
                foreach ($elementRows as $elementRow) {
                    array_push($contentElements, $elementRow['uid']);
                }
                $data['content_elements'] = $contentElements;
            }
        }

        return $data;
    }
}
