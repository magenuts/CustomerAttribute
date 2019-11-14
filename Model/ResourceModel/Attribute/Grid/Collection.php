<?php
/**
 * Magenuts Pvt Ltd.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://magenuts.com/Magenuts-Commerce-License.txt
 *
 * @category   Magenuts
 * @package    Magenuts_CustomerAttribute
 * @author     Magenuts Extension Team
 * @copyright  Copyright (c) 2019 Magenuts Pvt Ltd. ( https://magenuts.com )
 * @license    https://magenuts.com/Magenuts-Commerce-License.txt
 */


namespace Magenuts\CustomerAttribute\Model\ResourceModel\Attribute\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Customer\Model\Attribute;
use Magento\Eav\Model\Entity as Entity;
use Magento\Framework\Api;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * Customer attribute grid collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends SearchResult
{
 
    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        $resourceModel,
        Entity $entityModel
    ) {
        $this->entityModel = $entityModel;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }
    
    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        $entityTypeId = $this->entityModel->setType(
            \Magento\Customer\Model\Customer::ENTITY
        )->getTypeId();
        
        parent::_initSelect();
        //Join eav attribute table
        $this->getSelect()->joinLeft(
            ['eav_attribute' => $this->getTable('eav_attribute')],
            'eav_attribute.attribute_id = main_table.attribute_id'
        );
        $this->getSelect()->where('eav_attribute.entity_type_id=?',$entityTypeId);
        //echo  $this->getSelect();
    }
}