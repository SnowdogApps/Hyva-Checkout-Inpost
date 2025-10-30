<?php

namespace Snowdog\Hyva\Checkout\Inpost\Plugin;

use Hyva\Checkout\Model\Magewire\Payment\PlaceOrderServiceInterface;
use Magento\Quote\Model\Quote;

class RemovePickupPoint
{
    public function beforePlaceOrder(PlaceOrderServiceInterface $subject, Quote $quote): array
    {
        $attributes = $quote->getExtensionAttributes();
        $lockerId = $attributes->getInpostLockerId();
        if (!str_starts_with($quote->getShippingAddress()->getShippingMethod(), 'inpostlocker_') && !empty($lockerId)) {
            $attributes->setInpostLockerId(null);
            $quote->setExtensionAttributes($attributes);
            $quote->setInpostLockerId(null);
        }

        return [$quote];
    }
}