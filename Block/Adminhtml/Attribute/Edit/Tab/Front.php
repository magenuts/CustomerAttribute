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

namespace Magenuts\CustomerAttribute\Block\Adminhtml\Attribute\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Front extends Generic
{
    /**
     * @var Yesno
     */
    protected $_yesNo;

    /**
     * @var PropertyLocker
     */
    private $propertyLocker;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Yesno $yesNo
     * @param PropertyLocker $propertyLocker
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Yesno $yesNo,
        PropertyLocker $propertyLocker,
        array $data = []
    ) {
        $this->_yesNo = $yesNo;
        $this->propertyLocker = $propertyLocker;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var Attribute $attributeObject */
        $attributeObject = $this->_coreRegistry->registry('entity_attribute');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $yesnoSource = $this->_yesNo->toOptionArray();

        $fieldset = $form->addFieldset(
            'front_fieldset',
            ['legend' => __('Storefront Properties'), 'collapsable' => $this->getRequest()->has('popup')]
        );
        
        $fieldset->addField(
            'sort_order',
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'class' => 'validate-digits',
                'note' => __('The order to display attribute on the frontend'),
            ]
        );
        
        $usedInForms = $attributeObject->getUsedInForms();
        
        $showOnRegistration = ($attributeObject->getAttributeId()) ? 
                (in_array('customer_account_create', $usedInForms) ? 1 : 0) : 1;
        $fieldset->addField(
            'customer_account_create',
            'select',
            [
                'name' => 'customer_account_create',
                'label' => __('Registration Page'),
                'title' => __('Registration Page'),
                'values' => $yesnoSource,
                'value' => $showOnRegistration,
            ]
        );
        
        $showAccountEdit = ($attributeObject->getAttributeId()) ? 
                (in_array('customer_account_edit', $usedInForms) ? 1 : 0) : 1;
        $fieldset->addField(
            'customer_account_edit',
            'select',
            [
                'name' => 'customer_account_edit',
                'label' => __('Account Edit Page'),
                'title' => __('Account Edit Page'),
                'values' => $yesnoSource,
                'value' => $showAccountEdit,
            ]
        );
        
        $showAdminManageCustomers = ($attributeObject->getAttributeId()) ? 
                (in_array('adminhtml_customer', $usedInForms) ? 1 : 0) : 1;
        $fieldset->addField(
            'adminhtml_customer',
            'select',
            [
                'name' => 'adminhtml_customer',
                'label' => __('Manage Customers'),
                'title' => __('Manage Customers'),
                'values' => $yesnoSource,
                'value' => $showAdminManageCustomers,
                'note'  =>_('Admin Manage Customers'),
            ]
        );
        
        $this->setForm($form);
        $this->propertyLocker->lock($form);
        return parent::_prepareForm();
    }
    
    /**
     * Initialize form fileds values
     *
     * @return $this
     */
    protected function _initFormValues()
    {
        $this->getForm()->addValues($this->getAttributeObject()->getData());
        return parent::_initFormValues();
    }
    
    /**
     * Retrieve attribute object from registry
     *
     * @return mixed
     */
    private function getAttributeObject()
    {
        return $this->_coreRegistry->registry('entity_attribute');
    }
}
