<?php
/**
 * This file is part of OXID Module Skeleton Generator module.
 *
 * OXID Module Skeleton Generator module is free software:
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * OXID Module Skeleton Generator module is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID Module Skeleton Generator module.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @category      module
 * @package       ModuleGenerator
 * @author        OXID Professional services
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2017
 */

use OxidEsales\Eshop\Core\Base;

/**
 * Class oxpsModuleGeneratorMetaData is used for Module Generator's
 * Edit Mode as a parser getting info from existing metadata.php file and
 * converting it to Generation Options data structure to show module components.
 */
class oxpsModuleGeneratorMetadata extends Base
{

    /**
     * Components' path patterns to extract file types from metadata 'files' array
     */
    const OXPS_CONTROLLER_PATTERN = '/Application/Controller/';
    const OXPS_MODEL_PATTERN = '/Application/Model/';
    const OXPS_LIST_PATTERN = 'List.php';
    const OXPS_WIDGET_PATTERN = '/Application/Component/Widget/';

    /**
     * Array of methods to parse different metadata settings depending on setting type.
     *
     * @var array
     *
     * @see _parseBoolSettingValue      Parse Checkbox values
     * @see _parseStrSettingValue       Parse String values
     * @see _parseNumSettingValue       Parse Number values
     * @see _parseArrSettingValue       Parse Array values
     * @see _parseAarrSettingValue      Parse Associative Array values
     * @see _parseSelectSettingValue    Parse Dropdown values
     */
    protected $_aMetadataSettingsParse = [
        'bool'   => '_parseBoolSettingValue',
        'str'    => '_parseStrSettingValue',
        'num'    => '_parseNumSettingValue',
        'arr'    => '_parseArrSettingValue',
        'aarr'   => '_parseAarrSettingValue',
        'select' => '_parseSelectSettingValue',
    ];

    /**
     * Existing module metadata from metadata.php file
     *
     * @var array
     */
    protected $_aMetadata = [];

    /**
     * Array to test if required Block keys exists for parsing.
     *
     * @var array
     */
    protected $_aBlockKeys = [
        'block',
        'template',
    ];

    /**
     * Array to test if required Settings keys exists for parsing.
     *
     * @var array
     */
    protected $_aSettingsKeys = [
        'name',
        'type',
        'value',
    ];

    /**
     * Keep instance of Admin_oxpsModuleGenerator controller
     *
     * @var null|oxpsModuleGeneratorValidator
     */
    protected $oValidator;

    /**
     *
     * @return oxpsModuleGeneratorValidator
     */
    protected function _getValidationController()
    {
        if (null === $this->oValidator) {
            /** @var oxpsModuleGeneratorValidator oValidator */
            $this->oValidator = oxNew('oxpsModuleGeneratorValidator');
        }

        return $this->oValidator;
    }

    /**
     * Parse existing metadata to Generation Options array
     *
     * @param array  $aMetadata
     * @param string $sVendorPrefix
     * @param string $sModuleName
     *
     * @return array
     */
    public function parseMetadata(array $aMetadata, $sVendorPrefix, $sModuleName)
    {
        $this->_aMetadata = $aMetadata;

        $aGenerationOptions = [
            'aExtendClasses'  => $this->_parseMetadataExtendClasses('extend'),
            'aNewControllers' => $this->_parseMetadataControllers('files'),
            'aNewModels'      => $this->_parseMetadataModels('files', 'model'),
            'aNewLists'       => $this->_parseMetadataModels('files', 'list'),
            'aNewWidgets'     => $this->_parseMetadataWidgets('files'),
            'aNewBlocks'      => $this->_parseMetadataBlocks('blocks', $sVendorPrefix, $sModuleName),
            'aModuleSettings' => $this->_parseMetadataSettings('settings'),
        ];

        return $aGenerationOptions;
    }

    /**
     * Parse extended classes from existing metadata
     *
     * @param string $sMetadataArrayKey
     *
     * @return array
     */
    protected function _parseMetadataExtendClasses($sMetadataArrayKey)
    {
        $aMetadataExtendClasses = [];
        if ($this->_isValidMetadataKey($sMetadataArrayKey)) {
            $aMetadataExtendClassKeys = array_keys($this->_aMetadata[$sMetadataArrayKey]);
            $aMetadataExtendClasses = $this->_getValidationController()->validateAndLinkClasses(
                implode(PHP_EOL, $aMetadataExtendClassKeys)
            );
        }

        return $aMetadataExtendClasses;
    }

    /**
     * Parse Controllers from existing metadata
     *
     * @param string $sMetadataArrayKey
     *
     * @return array
     */
    protected function _parseMetadataControllers($sMetadataArrayKey)
    {
        $aMetadataControllers = [];
        if ($this->_isValidMetadataKey($sMetadataArrayKey)) {
            foreach ($this->_aMetadata[$sMetadataArrayKey] as $aMetadataKey => $aMetadataValue) {
                if (stripos($aMetadataValue, self::OXPS_CONTROLLER_PATTERN) !== false) {
                    $aMetadataControllers[] = $this->_stripModuleId($aMetadataKey);
                }
            }
        }

        return $aMetadataControllers;
    }

    /**
     * Parse Models (or Lists) from existing Metadata
     *
     * @param string $sMetadataArrayKey
     * @param string $sFileType
     *
     * @return array
     */
    protected function _parseMetadataModels($sMetadataArrayKey, $sFileType = '')
    {
        $aMetadataModels = [];
        if ($this->_isValidMetadataKey($sMetadataArrayKey)) {
            foreach ($this->_aMetadata[$sMetadataArrayKey] as $aMetadataKey => $aMetadataValue) {
                if (stripos($aMetadataValue, self::OXPS_MODEL_PATTERN) !== false) {
                    $aExplodedModelPath = explode("/", $aMetadataValue);
                    if ('model' === $sFileType) {
                        if (stripos(end($aExplodedModelPath), self::OXPS_LIST_PATTERN) === false) {
                            $aMetadataModels[] = $this->_stripModuleId($aMetadataKey);
                        }
                    } elseif ('list' === $sFileType) {
                        if (stripos(end($aExplodedModelPath), self::OXPS_LIST_PATTERN) !== false) {
                            $aMetadataModels[] = $this->_stripModuleId($aMetadataKey);
                        }
                    }
                }
            }
        }

        return $aMetadataModels;
    }

    /**
     * Parse Widgets from existing Metadata
     *
     * @param string $sMetadataArrayKey
     *
     * @return array
     */
    protected function _parseMetadataWidgets($sMetadataArrayKey)
    {
        $aMetadataWidgets = [];
        if ($this->_isValidMetadataKey($sMetadataArrayKey)) {
            foreach ($this->_aMetadata[$sMetadataArrayKey] as $aMetadataKey => $aMetadataValue) {
                if (stripos($aMetadataValue, self::OXPS_WIDGET_PATTERN) !== false) {
                    $aMetadataWidgets[] = $this->_stripModuleId($aMetadataKey);
                }
            }
        }

        return $aMetadataWidgets;
    }

    /**
     * Parse Metadata blocks from existing Metadata and check if they are unique.
     *
     * @param string $sMetadataArrayKey
     * @param string $sVendorPrefix
     * @param string $sModuleName
     *
     * @return array
     */
    protected function _parseMetadataBlocks($sMetadataArrayKey, $sVendorPrefix, $sModuleName)
    {
        $aMetadataBlocks = [];
        $aParsedBlocks = [];
        if ($this->_isValidMetadataKey($sMetadataArrayKey)) {

            foreach ($this->_aMetadata[$sMetadataArrayKey] as $aMetadataBlockArray) {
                if ($this->_hasRequiredArrayKeys($aMetadataBlockArray, $this->_aBlockKeys)) {
                    $sBlockPath = $aMetadataBlockArray['block'] . "@" . $aMetadataBlockArray['template'];
                    if (!in_array($sBlockPath, $aMetadataBlocks)) {
                        $aMetadataBlocks[] = $sBlockPath;
                    }
                }
            }

            $aParsedBlocks = $this->_getValidationController()->parseBlocksData(
                implode(PHP_EOL, $aMetadataBlocks),
                $sVendorPrefix,
                $sModuleName
            );
        }

        return $aParsedBlocks;
    }

    /**
     * Parse Metadata settings arrays of existing Metadata using different methods depending on type.
     *
     * @param string $sMetadataArrayKey
     *
     * @return array
     */
    protected function _parseMetadataSettings($sMetadataArrayKey)
    {
        $aMetadataSettings = [];

        if ($this->_isValidMetadataKey($sMetadataArrayKey)) {
            $iArrayKey = 0;

            foreach ($this->_aMetadata[$sMetadataArrayKey] as $aMetadataSettingsArray) {

                if ($this->_hasRequiredArrayKeys($aMetadataSettingsArray, $this->_aSettingsKeys)) {
                    $aMetadataSettings[$iArrayKey]['name'] = $this->_stripModuleId($aMetadataSettingsArray['name']);

                    $sType = array_key_exists($aMetadataSettingsArray['type'], $this->_aMetadataSettingsParse)
                        ? $aMetadataSettingsArray['type']
                        : 'str';

                    $sMethod = $this->_aMetadataSettingsParse[$sType];

                    $aMetadataSettings[$iArrayKey]['type'] = $sType;
                    $aMetadataSettings[$iArrayKey]['value'] = $this->$sMethod($aMetadataSettingsArray);

                    $iArrayKey++;
                }
            }
        }

        return $aMetadataSettings;
    }

    /**
     * Strip module ID (vendor and module names) from module components names.
     *
     * @param $sFullName
     *
     * @return string
     */
    protected function _stripModuleId($sFullName)
    {
        return (string) array_key_exists('id', $this->_aMetadata)
            ? str_ireplace($this->_aMetadata['id'], '', $sFullName)
            : '';
    }

    /**
     * @param array $aMetadataSettingsArray
     *
     * @return string
     */
    protected function _parseBoolSettingValue(array $aMetadataSettingsArray)
    {
        return (string) array_key_exists('value', $aMetadataSettingsArray) ? $aMetadataSettingsArray['value'] : '';
    }

    /**
     * @param array $aMetadataSettingsArray
     *
     * @return string
     */
    protected function _parseStrSettingValue(array $aMetadataSettingsArray)
    {
        return (string) array_key_exists('value', $aMetadataSettingsArray) ? $aMetadataSettingsArray['value'] : '';
    }

    /**
     * @param array $aMetadataSettingsArray
     *
     * @return string
     */
    protected function _parseNumSettingValue(array $aMetadataSettingsArray)
    {
        return (string) array_key_exists('value', $aMetadataSettingsArray) ? $aMetadataSettingsArray['value'] : '';
    }

    /**
     * @param array $aMetadataSettingsArray
     *
     * @return string
     */
    protected function _parseArrSettingValue(array $aMetadataSettingsArray)
    {
        return (string) array_key_exists('value', $aMetadataSettingsArray) ? $aMetadataSettingsArray['value'] : '';
    }

    /**
     * @param array $aMetadataSettingsArray
     *
     * @return string
     */
    protected function _parseAarrSettingValue(array $aMetadataSettingsArray)
    {
        $sArray = '';
        if (array_key_exists('value', $aMetadataSettingsArray)) {
            foreach ($aMetadataSettingsArray['value'] as $index => $item) {
                $sArray .= $index . ' => ' . $item . PHP_EOL;
            }
        }

        return $sArray;
    }

    /**
     * @param array $aMetadataSettingsArray
     *
     * @return string
     */
    protected function _parseSelectSettingValue(array $aMetadataSettingsArray)
    {
        $sConstrains = (string) array_key_exists('constrains', $aMetadataSettingsArray)
            ? $aMetadataSettingsArray['constrains']
            : '';

        return (string) str_replace("|", PHP_EOL, $sConstrains);
    }

    /**
     * Check the type and availability of provided metadata array key
     *
     * @param string $sArrayKey
     *
     * @return bool
     */
    protected function _isValidMetadataKey($sArrayKey)
    {
        return array_key_exists($sArrayKey, $this->_aMetadata)
               && is_array($this->_aMetadata[$sArrayKey]);
    }

    /**
     * Check if array exists and has required array keys for parsing process.
     *
     * @param array $aMetadataArray
     * @param array $aRequiredKeys
     *
     * @return bool
     */
    protected function _hasRequiredArrayKeys(array $aMetadataArray, array $aRequiredKeys)
    {
        return is_array($aMetadataArray)
               && count(
                      array_intersect_key(array_flip($aRequiredKeys), $aMetadataArray)
                  ) === count($aRequiredKeys);
    }
}
