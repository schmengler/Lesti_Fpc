<?php
/**
 * Lesti_Fpc
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * http://opensource.org/licenses/OSL-3.0
 *
 * @package      Lesti_Fpc
 * @copyright    Copyright (c) 2013 Gordon Lesti (http://www.gordonlesti.com)
 * @author       Gordon Lesti <info@gordonlesti.com>
 * @license      http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class Lesti_Fpc_Model_Fpc
 */
class Lesti_Fpc_Model_Fpc extends Mage_Core_Model_Cache
{
    const CACHE_TAG = 'FPC';

    /**
     * Default options for default backend
     *
     * @var array
     */
    protected $_defaultBackendOptions = array(
        'hashed_directory_level'    => 3,
        'hashed_directory_perm'    => 0777,
        'file_name_prefix'          => 'fpc',
    );

    /**
     * Default options for default backend used by Zend Framework versions
     * older than 1.12.0
     *
     * @var array
     */
    protected $_legacyDefaultBackendOptions = array(
        'hashed_directory_level'    => 3,
        'hashed_directory_umask'    => 0777,
        'file_name_prefix'          => 'fpc',
    );

    /**
     *
     */
    public function __construct()
    {
        /*
         * If the version of Zend Framework is older than 1.12, fallback to the legacy
         * cache settings. See http://framework.zend.com/issues/browse/ZF-12047
         */
        if (Zend_Version::compareVersion('1.12.0') > 0) {
            $this->_defaultBackendOptions = $this->_legacyDefaultBackendOptions;
        }
        $node = Mage::getConfig()->getNode('global/fpc');
        $options = array();
        if($node) {
            $options = $node->asArray();
        }
        parent::__construct($options);
    }

    /**
     * Save data
     *
     * @param string $data
     * @param string $id
     * @param array $tags
     * @param int $lifeTime
     * @return bool
     */
    public function save($data, $id, $tags=array(), $lifeTime=null)
    {
        /**
         * Add global magento cache tag to all cached data exclude config cache
         */
        if (!in_array(Mage_Core_Model_Config::CACHE_TAG, $tags)) {
            $tags[] = self::CACHE_TAG;
        }
        if ($lifeTime === null) {
            $lifeTime = (int) $this->getFrontend()->getOption('lifetime');
        }
        return $this->_frontend->save((string)$data, $this->_id($id), $this->_tags($tags), $lifeTime);
    }

    /**
     * Clean cached data by specific tag
     *
     * @param   array $tags
     * @return  bool
     */
    public function clean($tags=array())
    {
        $mode = Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
        if (!empty($tags)) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }
            $res = $this->_frontend->clean($mode, $this->_tags($tags));
        } else {
            $res = $this->_frontend->clean($mode, array(self::CACHE_TAG));
            $res = $res && $this->_frontend->clean($mode, array(Mage_Core_Model_Config::CACHE_TAG));
        }
        return $res;
    }

    /**
     * Sets flag to invalidate lazy blocks in sessions
     */
    public function invalidateLazyBlocks()
    {
        Mage::helper('fpc')->getInvalidateLazyBlocksFlag()
        	->setFlagData(time())
            ->setState(1)
            ->save();
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return Mage::app()->useCache('fpc');
    }

}
