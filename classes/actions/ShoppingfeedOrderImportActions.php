<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from 202 ecommerce is strictly forbidden.
 *
 * @author    202 ecommerce <contact@202-ecommerce.com>
 * @copyright Copyright (c) 202 ecommerce 2017
 * @license   Commercial license
 *
 * Support <support@202-ecommerce.com>
 */

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedCarrier.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedOrder.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedPaymentModule.php');

class ShoppingfeedOrderImportActions extends DefaultActions
{
    /** @var ShoppingfeedOrderImportSpecificRulesManager $specificRulesManager */
    protected $specificRulesManager;
    
    protected $logPrefix = '';
    
    public function setLogPrefix($id_internal_shoppingfeed = '', $shoppingfeed_order_reference = '')
    {
        $this->logPrefix = '';
        if ($id_internal_shoppingfeed) {
            $this->logPrefix .= sprintf(
                $this->l('[Order: %s]', 'ShoppingfeedOrderImportActions'),
                $id_internal_shoppingfeed
            );
        }
        if ($shoppingfeed_order_reference) {
            $this->logPrefix .= '[' . $shoppingfeed_order_reference . ']';
        }
        if ($this->logPrefix) {
            $this->logPrefix .= ' ';
        }
    }
    
    /**
     * Sets generic variables in the action class
     *
     * @param ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder
     */
    protected function initProcess(ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder)
    {
        if (empty($this->conveyor['orderData'])) {
            $this->conveyor['orderData'] = new OrderData($apiOrder);
        }
        $this->setLogPrefix(
            $apiOrder->getId(),
            $apiOrder->getReference()
        );
    }
    
    // TODO : we'll need to handle specific marketplaces / carriers too
    // We may want to call some hooks during this process...
    // Should we delete what we created if the order creation fails ?
    
    public function registerSpecificRules()
    {
        if (empty($this->conveyor['apiOrder'])) {
            ProcessLoggerHandler::logError(
                $this->l('No apiOrder found', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $this->initProcess($apiOrder);
        
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Loading specific rules...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        $this->specificRulesManager = new ShoppingfeedAddon\OrderImport\RulesManager($this->conveyor['id_shop'], $apiOrder);
            
        // Specific rule : give a chance to tweak general data before using them
        $this->specificRulesManager->applyRules(
            'onPreProcess',
            array(
                'apiOrder' => $this->conveyor['apiOrder'],
                'orderData' => $this->conveyor['orderData'],
            )
        );
        
        return true;
    }
    
    public function verifyOrder()
    {
        if (empty($this->conveyor['apiOrder'])) {
            ProcessLoggerHandler::logError(
                $this->l('No apiOrder found', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $this->initProcess($apiOrder);
        
        // See old module checkData()
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Checking order...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // Check if order already exists
        if (ShoppingfeedOrder::existsInternalId($apiOrder->getId())) {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Order not imported; already present.', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        
        // Check products
        $this->conveyor['prestashopProducts'] = array();
        $sfModule = Module::getInstanceByName('shoppingfeed');
        
        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            $psProduct = $sfModule->mapPrestashopProduct(
                $apiProduct->reference,
                $this->conveyor['id_shop']
            );
            
            // Does the product exist ?
            if (!Validate::isLoadedObject($psProduct)) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Product reference %s does not match a product.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference
                    ),
                    'Order'
                );
                return false;
            }
            
            // Is the product active ?
            if (!$psProduct->active) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Product %s is inactive.', 'ShoppingfeedOrderImportActions'),
                        $psProduct->reference
                    ),
                    'Order'
                );
                return false;
            }
            
            // Can the product be ordered ?
            if (!$psProduct->available_for_order) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Product %s is not available for order.', 'ShoppingfeedOrderImportActions'),
                        $psProduct->reference
                    ),
                    'Order'
                );
                return false;
            }
            
            $this->conveyor['prestashopProducts'][$apiProduct->reference] = $psProduct;
        }
        
        // Check carrier
        $apiOrderShipment = &$this->conveyor['orderData']->shipment;
        $carrier = null;
        $skipSfCarrierCreation = false;
        
        // Specific rules to get a carrier
        $this->specificRulesManager->applyRules(
            'onCarrierRetrieval',
            array(
                'apiOrder' => $this->conveyor['apiOrder'],
                'apiOrderShipment' => &$apiOrderShipment,
                // Note : if you choose to set a carrier directly, you may want
                // to either set the $apiOrderShipment data accordingly, or skip
                // the ShoppingfeedCarrier creation step
                'carrier' => &$carrier,
                'skipSfCarrierCreation' => &$skipSfCarrierCreation
            )
        );
        
        $sfCarrier = ShoppingfeedCarrier::getByMarketplaceAndName(
            $apiOrder->getChannel()->getName(),
            $apiOrderShipment['carrier']
        );
        if (Validate::isLoadedObject($sfCarrier) && !Validate::isLoadedObject($carrier)) {
            $carrier = Carrier::getCarrierByReference($sfCarrier->id_carrier_reference);
        }
        
        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE))) {
            $carrier = Carrier::getCarrierByReference(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }
        
        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get('PS_CARRIER_DEFAULT'))) {
            $carrier = new Carrier(Configuration::get('PS_CARRIER_DEFAULT'));
        }
        
        if (!Validate::isLoadedObject($carrier)) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                    $this->l('Could not find a valid carrier for order.', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        
        $this->conveyor['carrier'] = $carrier;
        
        if (!$skipSfCarrierCreation && !Validate::isLoadedObject($sfCarrier)) {
            $sfCarrier = new ShoppingfeedCarrier();
            $sfCarrier->name_marketplace = $apiOrder->getChannel()->getName();
            $sfCarrier->name_carrier = $apiOrderShipment['carrier'];
            $sfCarrier->id_carrier_reference = $carrier->id;
            $sfCarrier->is_new = true;
            $sfCarrier->save();
        }
        
        // Specific rules validation
        $this->specificRulesManager->applyRules(
            'onVerifyOrder',
            array(
                'apiOrder' => $this->conveyor['apiOrder']
            )
        );
        
        return true;
    }
    
    public function createOrderCart()
    {
        if (empty($this->conveyor['apiOrder'])) {
            ProcessLoggerHandler::logError(
                $this->l('No apiOrder found', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $this->initProcess($apiOrder);
        
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Start cart creation...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // TODO : We might want to split this in functions
        // Try to retrieve customer using the billing address mail
        $apiBillingAddress = &$this->conveyor['orderData']->billingAddress;
        if (Validate::isEmail($apiBillingAddress['email'])) {
            $customerEmail = $apiBillingAddress['email'];
        } else {
            $customerEmail = $apiOrder->getId()
                . '@' . $apiOrder->getChannel()->getName()
                . '.sf';
        }
        
        // see old module _getCustomer()
        $customer = new Customer();
        $customer->getByEmail($customerEmail);
        
        // Create customer if it doesn't exist
        if (!Validate::isLoadedObject($customer)) {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Creating customer...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
        
            $customer = new Customer();
            $customer->lastname = !empty($apiBillingAddress['lastName']) ? Tools::substr($apiBillingAddress['lastName'], 0, 32) : '-';
            $customer->firstname = !empty($apiBillingAddress['firstName']) ? Tools::substr($apiBillingAddress['firstName'], 0, 32) : '-';
            $customer->passwd = md5(pSQL(_COOKIE_KEY_.rand()));
            $customer->id_default_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
            $customer->email = $customerEmail;
            $customer->newsletter = 0;
        
            // Specific rules
            $this->specificRulesManager->applyRules(
                'onCustomerCreation',
                array(
                    'apiOrder' => $apiOrder,
                    'customer' => $customer
                )
            );
        
            $customer->add();
        } else {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Retrieved customer from billing address...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
        
            // Specific rules
            $this->specificRulesManager->applyRules(
                'onCustomerRetrieval',
                array(
                    'apiOrder' => $apiOrder,
                    'customer' => $customer
                )
            );
        }
        $this->conveyor['customer'] = $customer;
        
        // Create or update addresses
        try {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Creating/updating billing address...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
        
            // Specific rules
            $this->specificRulesManager->applyRules(
                'beforeBillingAddressCreation',
                array(
                    'apiBillingAddress' => &$apiBillingAddress,
                    'apiOrder' => $apiOrder,
                )
            );
            
            $billingAddress = $this->getOrderAddress(
                $apiBillingAddress,
                $customer,
                'Billing-'.$apiOrder->getId()
            );
            
            try {
                // Specific rules
                $this->specificRulesManager->applyRules(
                    'beforeBillingAddressSave',
                    array(
                        'apiBillingAddress' => &$apiBillingAddress,
                        'billingAddress' => $billingAddress,
                        'apiOrder' => $apiOrder,
                    )
                );
                $billingAddress->save();
            } catch (Exception $e) {
                throw new Exception(sprintf(
                    $this->l('Address %s could not be created : %s', 'ShoppingfeedOrderImportActions'),
                    'Billing-'.$apiOrder->getId(),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ));
            }
            $this->conveyor['id_billing_address'] = $billingAddress->id;
            
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Creating/updating shipping address...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            $apiShippingAddress = &$this->conveyor['orderData']->shippingAddress;
        
            // Specific rules
            $this->specificRulesManager->applyRules(
                'beforeShippingAddressCreation',
                array(
                    'apiShippingAddress' => &$apiShippingAddress,
                    'apiOrder' => $apiOrder,
                )
            );
            
            $shippingAddress = $this->getOrderAddress(
                $apiShippingAddress,
                $customer,
                'Shipping-'.$apiOrder->getId()
            );
            
            try {
                // Specific rules
                $this->specificRulesManager->applyRules(
                    'beforeShippingAddressSave',
                    array(
                        'apiShippingAddress' => &$apiShippingAddress,
                        'shippingAddress' => $shippingAddress,
                        'apiOrder' => $apiOrder,
                    )
                );
                $shippingAddress->save();
            } catch (Exception $e) {
                throw new Exception(sprintf(
                    $this->l('Address %s could not be created : %s', 'ShoppingfeedOrderImportActions'),
                    'Shipping-'.$apiOrder->getId(),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ));
            }
            $this->conveyor['id_shipping_address'] = $shippingAddress->id;
        } catch (Exception $ex) {
            ProcessLoggerHandler::logError(
                $this->logPrefix . $ex->getMessage(),
                'Order'
            );
            return false;
        }
        
        /* Check products quantities
         *
         * Check if there is enough stock associated to the products before
         * creating the order. If the stock is not sufficient, it will be
         * increased just enough to avoid an error during the creation of the
         * order.
         *
         * For orders managed directly by a marketplace, the product quantity
         * should not be impacted on Prestashop.
         * Therefore, we'll increase the stock so that it won't be decreased
         * after validating the order.
         */
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Checking products quantities...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        $isMarketplaceManagedQuantities = ShoppingfeedOrder::isMarketplaceManagedQuantities($apiOrder->getChannel()->getName());
        $isAdvancedStockEnabled = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 1 ? true : false;
        
        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->reference];
            $useAdvancedStock = $isAdvancedStockEnabled && $psProduct->advanced_stock_management;
            
            // If the stock is managed by the marketplace
            if ($isMarketplaceManagedQuantities) {
                // We directly add the ordered quantity; it will be deduced when
                // the order is validated
                $currentStock = StockAvailable::getQuantityAvailableByProduct(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $this->conveyor['id_shop']
                );
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Order is managed by marketplace %s, increase product %s stock : original %d, add %d.', 'ShoppingfeedOrderImportActions'),
                        $apiOrder->getChannel()->getName(),
                        $apiProduct->reference,
                        (int)$currentStock,
                        (int)$apiProduct->quantity
                    ),
                    'Order'
                );
                StockAvailable::updateQuantity(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    (int)$apiProduct->quantity,
                    $this->conveyor['id_shop']
                );
                continue;
            }
            
            // If the product uses advanced stock management
            if ($useAdvancedStock) {
                // If there's not enough stock to place the order
                if (!$this->checkAdvancedStockQty($psProduct, $apiProduct->quantity)) {
                    ProcessLoggerHandler::logError(
                        sprintf(
                            $this->logPrefix .
                                $this->l('Not enough stock for product %s.', 'ShoppingfeedOrderImportActions'),
                            $apiProduct->reference
                        ),
                        'Order'
                    );
                    return false;
                }
                continue;
            }
            
            // If the product doesn't use advanced stock management
            // If there's not enough stock to place the order
            if (!$psProduct->checkQty($apiProduct->quantity)) {
                // Add just enough stock
                $currentStock = StockAvailable::getQuantityAvailableByProduct(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $this->conveyor['id_shop']
                );
                $stockToAdd = (int)$apiProduct->quantity - (int)$currentStock;
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Not enough stock for product %s: original %d, required %d, add %d.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference,
                        (int)$currentStock,
                        (int)$apiProduct->quantity,
                        (int)$stockToAdd
                    ),
                    'Order'
                );
                StockAvailable::updateQuantity(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $stockToAdd,
                    $this->conveyor['id_shop']
                );
            }
        }
        
        // See old module _getCart()
        $paymentInformation = &$this->conveyor['orderData']->payment;
        
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Creating cart...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        // Create cart
        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = $billingAddress->id;
        $cart->id_address_delivery = $shippingAddress->id;
        $cart->id_currency = Currency::getIdByIsoCode(
            (string)$paymentInformation['currency'] == '' ?
                'EUR' : (string)$paymentInformation['currency']
        );
        $cart->id_lang = Configuration::get('PS_LANG_DEFAULT');
        
        $cart->recyclable = 0;
        $cart->secure_key = md5(uniqid(rand(), true));
        
        $cart->id_carrier = $this->conveyor['carrier']->id;
        $cart->add();

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Adding products to cart...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // Add products to cart
        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->reference];
            try {
                $addToCartResult = $cart->updateQty(
                    $apiProduct->quantity,
                    $psProduct->id,
                    $psProduct->id_product_attribute
                );
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Could not add product %s to cart : %s', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference,
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    'Order'
                );
                return false;
            }
            
            if ($addToCartResult < 0 || $addToCartResult === false) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Could not add product %s to cart.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference
                    ),
                    'Order'
                );
                return false;
            }
        }
        
        $this->specificRulesManager->applyRules(
            'onCartCreation',
            array(
                'apiOrder' => $apiOrder,
                'orderData' => &$this->conveyor['orderData'],
                'cart' => &$cart,
            )
        );

        $cart->update();
        $this->conveyor['cart'] = $cart;
        
        $this->specificRulesManager->applyRules(
            'afterCartCreation',
            array(
                'apiOrder' => $apiOrder,
                'orderData' => &$this->conveyor['orderData'],
                'cart' => &$cart,
            )
        );
        
        return true;
    }
    
    public function validateOrder()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        /** @var Cart $apiOrder */
        $cart = $this->conveyor['cart'];
        
        $this->initProcess($apiOrder);
        
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Validate order...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        // See old module _validateOrder()
        $paymentModule = new ShoppingfeedPaymentModule();
        $paymentModule->name = 'sfpayment';
        $paymentModule->active = true;

        //we need to flush the cart because of cache problems
        $cart->getPackageList(true);
        $cart->getDeliveryOptionList(null, true);
        $cart->getDeliveryOption(null, false, false);
        
        Context::getContext()->currency = new Currency((int)$cart->id_currency);
        
        if (!Context::getContext()->country->active) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->logPrefix .
                        $this->l('Current context country %d is not active.', 'ShoppingfeedOrderImportActions'),
                    Context::getContext()->country->id
                ),
                'Order'
            );
            $addressDelivery = new Address($cart->id_address_delivery);
            if (Validate::isLoadedObject($addressDelivery)) {
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Setting context country to %d.', 'ShoppingfeedOrderImportActions'),
                        $addressDelivery->id_country
                    ),
                    'Order'
                );
                Context::getContext()->country = new Country($addressDelivery->id_country);
            }
        }
        
        // Validate order with payment module
        // TODO : we should change the customer mail before this, otherwise he'll get a confirmation mail
        $amount_paid = (float)Tools::ps_round((float)$cart->getOrderTotal(true, Cart::BOTH), 2);
        $paymentModule->validateOrder(
            (int)$cart->id,
            2,
            $amount_paid,
            Tools::strtolower($apiOrder->getChannel()->getName()),
            null,
            array(),
            $cart->id_currency,
            false,
            $cart->secure_key
        );
        $this->conveyor['id_order'] = $paymentModule->currentOrder;
        $this->conveyor['order_reference'] = $paymentModule->currentOrderReference;

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Creating module sfOrder...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        // Create the ShoppingfeedOrder here; we need to know if it's been created
        // after this point
        $sfOrder = new ShoppingfeedOrder();
        $sfOrder->id_order = $this->conveyor['id_order'];
        $sfOrder->id_internal_shoppingfeed = (string)$apiOrder->getId();
        $sfOrder->id_order_marketplace = $apiOrder->getReference();
        $sfOrder->name_marketplace = $apiOrder->getChannel()->getName();
        
        $paymentInformation = $this->conveyor['orderData']->payment;
        $sfOrder->payment_method = $paymentInformation['method'];
        
        if ($this->conveyor['orderData']->createdAt->getTimestamp() != 0) {
            $sfOrder->date_marketplace_creation = $this->conveyor['orderData']->createdAt->format('Y-m-d H:i:s');
        }
        
        $sfOrder->save();
        $this->conveyor['sfOrder'] = $sfOrder;
        
        // Specific rules
        $this->specificRulesManager->applyRules(
            'afterOrderCreation',
            array(
                'apiOrder' => $apiOrder,
                'id_order' => $this->conveyor['id_order'],
                'order_reference' => $this->conveyor['order_reference']
            )
        );
        
        return true;
    }
    
    /**
     * We'll assume that this action should not prevent the following actions
     * from executing
     */
    public function acknowledgeOrder()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $this->initProcess($apiOrder);
        
        if (empty($this->conveyor['sfOrder'])) {
            $this->conveyor['sfOrder'] = ShoppingfeedOrder::getByShoppingfeedInternalId($apiOrder->getId());
        }
        
        if (empty($this->conveyor['sfOrder'])) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                    $this->l('Could not retrieve associated sfOrder', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return true;
        }
        
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Acknowledging order import...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                    $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return true;
        }
        
        $result = $shoppingfeedApi->acknowledgeOrder(
            $this->conveyor['sfOrder']->id_order_marketplace,
            $this->conveyor['sfOrder']->name_marketplace,
            $this->conveyor['sfOrder']->id_order
        );
        if (!$result || !iterator_count($result->getTickets())) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                    $this->l('Failed to acknowledge order on Shoppingfeed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return true;
        }
        
        
        return true;
    }
    
    public function recalculateOrderPrices()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $psOrder = new Order((int)$this->conveyor['id_order']);
        
        $this->initProcess($apiOrder);
        
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Recalculating order prices...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // We may have multiple orders created for the same reference, (advanced stock management)
        // therefore the total price of each orders needs to be calculated separately
        $ordersList = array();
        
        // See old module _updatePrices()
        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            /** @var Product $psProduct */
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->reference];
            
            $query = new DbQuery();
            $query->select('tax.rate AS tax_rate, od.id_order_detail, od.id_order')
                ->from('order_detail', 'od')
                ->leftJoin('orders', 'o', 'o.id_order = od.id_order')
                ->leftJoin('order_detail_tax', 'odt', 'odt.id_order_detail = od.id_order_detail')
                ->leftJoin('tax', 'tax', 'tax.id_tax = odt.id_tax')
                ->where('o.reference LIKE "' . pSQL($this->conveyor['order_reference']). '"')
                ->where('product_id = ' . (int)$psProduct->id)
            ;
            if ($psProduct->id_product_attribute) {
                $query->where('product_attribute_id = ' . (int)$psProduct->id_product_attribute);
            }
            $productOrderDetail = Db::getInstance()->getRow($query);
            
            // TODO : check if we actually got a row ?
            
            // The tax may not be defined for the country (linked to the invoice address)
            // Eg: Switzerland invoice address received in french shop (will depends of PS configuration)
            $tax_rate = $productOrderDetail['tax_rate'] === null ? 0 : $productOrderDetail['tax_rate'];

            // Retrieve the id_order linked to the order_reference (as there might be multiple orders created
            // from the same reference)
            if (!isset($ordersList[(int)$productOrderDetail['id_order']])) {
                // We populate the list of orders with the same reference
                $ordersList[(int)$productOrderDetail['id_order']] = array(
                    'total_products_tax_excl' => 0,
                    'total_products_tax_incl' => 0,
                );
            }
            
            $orderDetailPrice_tax_incl = (float)$apiProduct->getTotalPrice();
            $orderDetailPrice_tax_excl = (float)($orderDetailPrice_tax_incl / (1 + ($tax_rate / 100)));
            
            $ordersList[(int)$productOrderDetail['id_order']]['total_products_tax_incl'] += $orderDetailPrice_tax_incl;
            $ordersList[(int)$productOrderDetail['id_order']]['total_products_tax_excl'] += $orderDetailPrice_tax_excl;
            
            
            $original_product_price = Product::getPriceStatic(
                $psProduct->id,
                false,
                $psProduct->id_product_attribute,
                6
            );
            $updateOrderDetail = array(
                'product_price'        => (float)((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100))),
                'reduction_percent'    => 0,
                'reduction_amount'     => 0,
                'ecotax'               => 0,
                'total_price_tax_incl' => $orderDetailPrice_tax_incl,
                'total_price_tax_excl' => $orderDetailPrice_tax_excl,
                'unit_price_tax_incl'  => (float)$apiProduct->unitPrice,
                'unit_price_tax_excl'  => (float)((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100))),
                'original_product_price' => $original_product_price,
            );
            Db::getInstance()->update(
                'order_detail',
                $updateOrderDetail,
                '`id_order` = '.(int)$productOrderDetail['id_order'] .
                    ' AND `product_id` = ' . (int)$psProduct->id .
                    ' AND `product_attribute_id` = ' . (int)$psProduct->id_product_attribute
            );
            
            if ($tax_rate > 0) {
                $updateOrderDetailTax = array(
                    'unit_amount'  => Tools::ps_round((float)((float)$apiProduct->unitPrice - ((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100)))), 2),
                    'total_amount' => Tools::ps_round((float)(((float)$apiProduct->unitPrice - ((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100)))) * $apiProduct->quantity), 2),
                );
                Db::getInstance()->update(
                    'order_detail_tax',
                    $updateOrderDetailTax,
                    '`id_order_detail` = ' . (int)$productOrderDetail['id_order_detail']
                );
            } else {
                // delete tax so that it does not appear in tax details block in invoice
                Db::getInstance()->delete(
                    'order_detail_tax',
                    '`id_order_detail` = ' . (int)$productOrderDetail['id_order_detail']
                );
            }
        }
        
        $carrier = $this->conveyor['carrier'];
        $paymentInformation = &$this->conveyor['orderData']->payment;
        
        // Carrier tax calculation START
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address = new Address($psOrder->id_address_invoice);
        } else {
            $address = new Address($psOrder->id_address_delivery);
        }
        $carrier_tax_rate = $carrier->getTaxesRate($address);
        $total_shipping_tax_excl = Tools::ps_round((float)((float)$paymentInformation['shippingAmount'] / (1 + ($carrier_tax_rate / 100))), 2);
        
        foreach ($ordersList as $id_order => $orderPrices) {
            $total_paid = (float)$orderPrices['total_products_tax_incl'];
            $total_paid_tax_excl = (float)$orderPrices['total_products_tax_excl'];
            // Only on main order
            if ($psOrder->id == (int)$id_order) {
                // main order
                $total_paid = (float)($total_paid + (float)$paymentInformation['shippingAmount']);
                $total_paid_tax_excl = (float)($orderPrices['total_products_tax_excl'] + (float)$total_shipping_tax_excl);
                $orderPrices['total_shipping'] = (float)($paymentInformation['shippingAmount']);
                $orderPrices['total_shipping_tax_incl'] = (float)($paymentInformation['shippingAmount']);
                $orderPrices['total_shipping_tax_excl'] = $total_shipping_tax_excl;
            } else {
                $orderPrices['total_shipping'] = 0;
                $orderPrices['total_shipping_tax_incl'] = 0;
                $orderPrices['total_shipping_tax_excl'] = 0;
            }

            $orderPrices['total_paid'] = (float)$total_paid;
            $orderPrices['total_paid_tax_incl'] = (float)$total_paid;
            $orderPrices['total_paid_tax_excl'] = (float)$total_paid_tax_excl;
        
            $updateOrder = array(
                'total_paid'              => (float)$orderPrices['total_paid'],
                'total_paid_tax_incl'     => (float)$orderPrices['total_paid_tax_incl'],
                'total_paid_tax_excl'     => (float)$orderPrices['total_paid_tax_excl'],
                'total_paid_real'         => (float)$orderPrices['total_paid'],
                'total_products'          => (float)$orderPrices['total_products_tax_excl'],
                'total_products_wt'       => (float)$orderPrices['total_products_tax_incl'],
                'total_shipping'          => (float)$orderPrices['total_shipping'],
                'total_shipping_tax_incl' => (float)$orderPrices['total_shipping_tax_incl'],
                'total_shipping_tax_excl' => (float)$orderPrices['total_shipping_tax_excl'],
                'carrier_tax_rate'        => $carrier_tax_rate,
                'id_carrier'              => $carrier->id
            );

            $updateOrderInvoice = array(
                'total_paid_tax_incl'     => (float)$orderPrices['total_paid_tax_incl'],
                'total_paid_tax_excl'     => (float)$orderPrices['total_paid_tax_excl'],
                'total_products'          => (float)$orderPrices['total_products_tax_excl'],
                'total_products_wt'       => (float)$orderPrices['total_products_tax_incl'],
                'total_shipping_tax_incl' => (float)$orderPrices['total_shipping_tax_incl'],
                'total_shipping_tax_excl' => (float)$orderPrices['total_shipping_tax_excl'],
            );

            $updateOrderTracking = array(
                'shipping_cost_tax_incl' => (float)$orderPrices['total_shipping'],
                'shipping_cost_tax_excl' => (float)$orderPrices['total_shipping_tax_excl'],
                'id_carrier' => $carrier->id
            );
        
            Db::getInstance()->update('orders', $updateOrder, '`id_order` = '.(int)$id_order);
            Db::getInstance()->update('order_invoice', $updateOrderInvoice, '`id_order` = '.(int)$id_order);
            Db::getInstance()->update('order_carrier', $updateOrderTracking, '`id_order` = '.(int)$id_order);
        }

        $updatePayment = array('amount' => (float)$paymentInformation['totalAmount']);
        Db::getInstance()->update('order_payment', $updatePayment, '`order_reference` = "'.pSQL($this->conveyor['order_reference']).'"');
        
        return true;
    }
    
    /**
     * Retrieves an address using it's alias, and creates or rewrites it
     *
     * @param array $apiAddress
     * @param \Customer $customer
     * @param string $addressAlias
     * @param string $marketPlace TODO unused for now; see old module _getAddress
     * @param string $shippingMethod TODO unused for now; see old module _getAddress
     */
    protected function getOrderAddress($apiAddress, $customer, $addressAlias)
    {
        $addressAlias = Tools::substr($addressAlias, 0, 32);
        
        $addressQuery = new DbQuery();
        $addressQuery->select('id_address')
            ->from('address')
            ->where('id_customer = ' . (int)$customer->id)
            ->where("alias = '" . pSQL($addressAlias) . "'");
        $id_address = Db::getInstance()->getValue($addressQuery);
        
        if ($id_address) {
            $address = new Address((int)$id_address);
        } else {
            $address = new Address();
        }
        
        $address->alias = $addressAlias;
        $address->id_customer = (int)$customer->id;
        $address->firstname = Tools::substr(
            !empty($apiAddress['firstName']) ? $apiAddress['firstName'] : $customer->firstname,
            0,
            32
        );
        $address->lastname = Tools::substr(
            !empty($apiAddress['lastName']) ? $apiAddress['lastName'] : $customer->lastname,
            0,
            32
        );
        $address->company = !empty($apiAddress['company']) ? Tools::substr($apiAddress['company'], 0, 255) : null;
        $address->address1 = Tools::substr($apiAddress['street'], 0, 128);
        $address->address2 = Tools::substr($apiAddress['street2'], 0, 128);
        $address->other = $apiAddress['other'];
        $address->postcode = Tools::substr($apiAddress['postalCode'], 0, 12);
        $address->city = Tools::substr($apiAddress['city'], 0, 64);
        
        // We'll always fill both phone fields
        $address->phone = Tools::substr($apiAddress['phone'], 0, 32);
        $address->phone_mobile = Tools::substr($apiAddress['mobilePhone'], 0, 32);
        
        if (empty($address->phone) && empty($address->phone_mobile)) {
            throw new Exception(sprintf(
                $this->l('Address %s has no phone number.', 'ShoppingfeedOrderImportActions'),
                $addressAlias
            ));
        } elseif (empty($address->phone)) {
            $address->phone = $address->phone_mobile;
        } elseif (empty($address->phone_mobile)) {
            $address->phone_mobile = $address->phone;
        }
        
        // Check if country is valid and active
        $id_country = (int)Country::getByIso($apiAddress['country']);
        if (!$id_country) {
            throw new Exception(sprintf(
                $this->l('Country %s was not found for address %s.', 'ShoppingfeedOrderImportActions'),
                $apiAddress['country'],
                $addressAlias
            ));
        }
        
        $country = new Country($id_country);
        if (!$country->active) {
            throw new Exception(sprintf(
                $this->l('Address %s has country %s, but it is not active.', 'ShoppingfeedOrderImportActions'),
                $addressAlias,
                $apiAddress['country']
            ));
        }
        $address->id_country = $id_country;

        // Update state (needed for US)
        $state_iso_code = Tools::strtoupper(trim($apiAddress['street2']));
        $id_state = State::getIdByIso($state_iso_code, $address->id_country);
        if ($id_state) {
            $address->id_state = $id_state;
        }

        return $address;
    }
    
    /**
     * Checks available quantity for a given product when using advanced stock
     * management.
     * We'll check if the total quantity is available in a single warehouse,
     * to avoid splitting the order.
     *
     * @param Product $product
     * @param int $quantityOrdered
     * @return boolean true if the order can be placed; false otherwise
     */
    protected function checkAdvancedStockQty($product, $quantityOrdered)
    {
        if (Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($product->id))) {
            return true;
        }
        
        $stockManager = StockManagerFactory::getManager();
        $productWarehouses = Warehouse::getWarehousesByProductId($product->id, $product->id_product_attribute);

        if (count($productWarehouses) == 0) {
            // No warehouses associated to this product
            return false;
        }
        
        foreach ($productWarehouses as $warehouse) {
            $id_warehouse = (int)$warehouse['id_warehouse'];

            $physicalQuantity = $stockManager->getProductPhysicalQuantities(
                $product->id,
                $product->id_product_attribute,
                $id_warehouse
            );

            if ($physicalQuantity - $quantityOrdered >= 0) {
                return true;
            }
        }

        return false;
    }
}
