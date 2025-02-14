<?php
/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Carrier;
use Cart;
use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;

class MonechelleColissimo extends AbstractColissimo implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'MonechelleColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        if ('monechelle' == Tools::strtolower($apiOrder->getChannel()->getName())
            && $this->isModuleColissimoEnabled()
            && !empty($this->getRelayId($apiOrder))
        ) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Rule triggered.', 'MonechelleColissimo'),
                'Order'
            );

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Monechelle and has non-empty "other" or "relayID" field.', 'MonechelleColissimo');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', 'MonechelleColissimo');
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        return 'A2P';
    }

    protected function getRelayId(OrderResource $apiOrder)
    {
        if ($relayId = parent::getRelayId($apiOrder)) {
            return $relayId;
        }

        $address = $apiOrder->getShippingAddress();

        if (false == empty($address['other'])) {
            return $address['other'];
        }

        if (false == empty($address['relayID'])) {
            return $address['relayID'];
        }

        return '';
    }

    public function afterCartCreation($params)
    {
        $cart = $params['cart'];

        if (false == $cart instanceof Cart) {
            return false;
        }

        $carrier = new Carrier($cart->id_carrier);

        if ($carrier->external_module_name != self::COLISSIMO_MODULE_NAME) {
            return false;
        }

        return parent::afterCartCreation($params);
    }
}
