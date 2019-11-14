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

namespace Magenuts\CustomerAttribute\Controller\Adminhtml\Attribute;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magenuts\CustomerAttribute\Controller\Adminhtml\Customer\Attribute
{
    /**
     * @var \Magenuts\CustomerAttribute\Helper\Data
     */
    protected $customerAttributeHelper;

    /**
     * @var \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Attribute $customerAttribute
     * @param \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory $validatorFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
     * @param \Magenuts\CustomerAttribute\Helper\Data $customerAttributeHelper
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory $validatorFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        \Magenuts\CustomerAttribute\Helper\Data $customerAttributeHelper,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory);
        $this->customerAttributeHelper = $customerAttributeHelper;
        $this->validatorFactory = $validatorFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $attributeId = $this->getRequest()->getParam('attribute_id');
            $attributeCode = $this->getRequest()->getParam('attribute_code')
                ?: $this->generateCode($this->getRequest()->getParam('frontend_label')[0]);
            
            //Validate attribute code
            if (strlen($attributeCode) > 0) {
                $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,30}$/']);
                if (!$validatorAttrCode->isValid($attributeCode)) {
                    $this->messageManager->addError(
                        __(
                            'Attribute code "%1" is invalid. Please use only letters (a-z), ' .
                            'numbers (0-9) or underscore(_) in this field, first character should be a letter.',
                            $attributeCode
                        )
                    );
                    return $this->returnResult(
                        'customerattribute/*/edit',
                        ['attribute_id' => $attributeId, '_current' => true],
                        ['error' => true]
                    );
                }
            }
             
            $data['attribute_code'] = $attributeCode;
            
            //validate frontend_input
            if (isset($data['frontend_input'])) {
                /** @var $inputType \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\Validator */
                $inputType = $this->validatorFactory->create();
                if (!$inputType->isValid($data['frontend_input'])) {
                    foreach ($inputType->getMessages() as $message) {
                        $this->messageManager->addError($message);
                    }
                    return $this->returnResult(
                        'customerattribute/*/edit',
                        ['attribute_id' => $attributeId, '_current' => true],
                        ['error' => true]
                    );
                }
            }

            /* @var $model \Magento\Customer\Model\Attribute */
            $model = $this->_objectManager->create(
                'Magento\Customer\Model\Attribute'
                );

            if ($attributeId) {
                $model->load($attributeId);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This attribute no longer exists.'));
                    return $this->returnResult('customerattribute/*/', [], ['error' => true]);
                }
               
                // entity type check
                if ($model->getEntityTypeId() != $this->_entityTypeId) {
                    $this->messageManager->addError(__('We can\'t update the attribute.'));
                    $this->_session->setAttributeData($data);
                    return $this->returnResult('customerattribute/*/', [], ['error' => true]);
                }

                $data['attribute_code'] = $model->getAttributeCode();
                $data['is_user_defined'] = $model->getIsUserDefined();
                $data['frontend_input'] = $model->getFrontendInput();
                
            } else {
                $data['source_model'] = $this->customerAttributeHelper->getAttributeSourceModelByInputType(
                    $data['frontend_input']
                );
                $data['backend_model'] = $this->customerAttributeHelper->getAttributeBackendModelByInputType(
                    $data['frontend_input']
                );
            }

            if (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0) {
                $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
            }
            
            $defaultValueField = $model->getDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $data['default_value'] = $this->getRequest()->getParam($defaultValueField);
            }
            
            $usedInForms = array();
            
            if (isset($data['customer_account_create'])&& $data['customer_account_create'] == 1) {
                $usedInForms[] = 'customer_account_create';
            }

            if (isset($data['customer_account_edit'])&& $data['customer_account_edit'] == 1) {
                $usedInForms[] = 'customer_account_edit';
            }

            if (isset($data['adminhtml_customer'])&& $data['adminhtml_customer'] == 1) {
                $usedInForms[] = 'adminhtml_customer';
            }

            $data['used_in_forms'] = $usedInForms;
            
            //Get default attribute set id
            $defaultAttributeSetId = $this->_objectManager->get('Magento\Eav\Model\Config')
                    ->getEntityType(\Magento\Customer\Model\Customer::ENTITY)
                    ->getDefaultAttributeSetId();
            $data['attribute_set_id'] = $defaultAttributeSetId;
            
            //Get default attribute group id
            $defaultAttributeGroupId = $this->_objectManager->get('Magento\Eav\Model\Entity\Attribute\Set')
                    ->getDefaultGroupId($defaultAttributeSetId);
            $data['attribute_group_id'] = $defaultAttributeGroupId;
           
            $model->addData($data);
            
            if (!$attributeId) {
                $model->setEntityTypeId($this->_entityTypeId);
                $model->setIsUserDefined(1);
            }
           
            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved the customer attribute.'));

                $this->_session->setAttributeData(false);
                if ($this->getRequest()->getParam('back', false)) {
                    return $this->returnResult(
                        'customerattribute/*/edit',
                        ['attribute_id' => $model->getId(), '_current' => true],
                        ['error' => false]
                    );
                }
                return $this->returnResult('customerattribute/*/', [], ['error' => false]);
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setAttributeData($data);
                return $this->returnResult(
                    'customerattribute/*/edit',
                    ['attribute_id' => $attributeId, '_current' => true],
                    ['error' => true]
                );
            }
        }
        return $this->returnResult('customerattribute/*/', [], ['error' => true]);
    }

    /**
     * @param string $path
     * @param array $params
     * @param array $response
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Backend\Model\View\Result\Redirect
     */
    private function returnResult($path = '', array $params = [], array $response = [])
    {
        if ($this->isAjax()) {
            $layout = $this->layoutFactory->create();
            $layout->initMessages();

            $response['messages'] = [$layout->getMessagesBlock()->getGroupedHtml()];
            $response['params'] = $params;
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath($path, $params);

    }

    /**
     * Define whether request is Ajax
     *
     * @return boolean
     */
    private function isAjax()
    {
        return $this->getRequest()->getParam('isAjax');
    }
}
