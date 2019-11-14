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

namespace Magenuts\CustomerAttribute\Helper;


class Customerattribute extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Attribute factory
     *
     * @var \Magento\Customer\Model\AttributeFactory
     */
    protected $_attributeFactory;
    
    /**
     * Customer Entity Factory
     *
     * @var Magento\Eav\Model\EntityFactory
     */
    protected $_customerEntityFactory;
    /**
     * Customer Factory
     *
     * @var Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
   
    /**
     * Eav attribute factory
     * 
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavAttribute;
    
    /**
     * Store factory
     * 
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
      * @param \Magento\Framework\App\Helper\Context $context
      * @param \Magento\Customer\Model\AttributeFactory $attributeFactory
      * @param \Magento\Eav\Model\EntityFactory $customerEntityFactory
      * @param \Magento\Eav\Model\ConfigFactory $eavAttributeFactory
      * @param \Magento\Customer\Model\CustomerFactory $customerFactory
      * @param \Magento\Store\Model\StoreManagerInterface $storeManager 
      */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\AttributeFactory $attributeFactory,            
        \Magento\Eav\Model\EntityFactory $customerEntityFactory,         
        \Magento\Eav\Model\ConfigFactory $eavAttributeFactory,         
        \Magento\Customer\Model\CustomerFactory $customerFactory,         
        \Magento\Store\Model\StoreManagerInterface $storeManager        
    ) {
        parent::__construct($context);
        $this->_attributeFactory = $attributeFactory;
        $this->_customerEntityFactory = $customerEntityFactory;
        $this->_eavAttribute = $eavAttributeFactory;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * Return user defined attributes attributs
     *
     * @return $collection
     */
    public function getUserDefinedAttribures()
    {
        $entityTypeId = $this->_customerEntityFactory->create()
                ->setType(\Magento\Customer\Model\Customer::ENTITY)
                ->getTypeId();
        $attribute = $this->_attributeFactory->create()
                ->setEntityTypeId($entityTypeId);
        $collection = $attribute->getCollection()
                ->addVisibleFilter()
                ->addFieldToFilter('is_user_defined', 1)
                ->setOrder('sort_order', 'ASC'); 
        return $collection;
    }

    /**
     * Check is attribute is for customer account create
     *
     * @return boolean 
     */
    public function isAttribureForCustomerAccountCreate($attributeCode)
    {
        $attribute   = $this->_eavAttribute->create()
                ->getAttribute('customer', $attributeCode);
        $usedInForms = $attribute->getUsedInForms();
        
        if (in_array('customer_account_create', $usedInForms)) {
            return true;
        }
         return false;
    }
    
    /**
     * Check is attribute is for customer account create
     *
     * @return boolean 
     */
    public function isAttribureForCustomerAccountEdit($attributeCode)
    {
        $attribute   = $this->_eavAttribute->create()
                ->getAttribute('customer', $attributeCode);
        $usedInForms = $attribute->getUsedInForms();
        
        if (in_array('customer_account_edit', $usedInForms)) {
            return true;
        }
         return false;
    }
    
    /**
     * Get store id
     * 
     * @return int Store id
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getStoreId(); 
    }
    
    /**
     * Return attribute options
     *
     * @return $array
     */
    public function getAttributeOptions($attributeCode)
    {
       $customerEntity = \Magento\Customer\Model\Customer::ENTITY;
        $options = $this->_eavAttribute->create()->getAttribute($customerEntity, $attributeCode)
                ->getSource()->getAllOptions();
         return $options;
    }
    
    /**
     * Get loged in customer data
     *
     * @return $array
     */
    public function getCustomer($customerId)
    {
       $customer = $this->_customerFactory->create()->load($customerId);
       return $customer;
    }
    
}
