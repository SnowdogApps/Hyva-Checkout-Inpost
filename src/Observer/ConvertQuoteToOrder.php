<?php

namespace Snowdog\Hyva\Checkout\Inpost\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class ConvertQuoteToOrder implements ObserverInterface
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getQuote();
        /** @var Order $order */
        $order = $observer->getOrder();

        $methodCode = $order->getShippingMethod();
        if (str_starts_with($methodCode, 'inpostlocker_')) {
            $lockerId = $quote->getInpostLockerId();
            $pickupDesc = $this->session->getData('inpost_locker_address_' . $lockerId);

            $description = implode(
                PHP_EOL,
                [
                    $order->getShippingDescription(),
                    $pickupDesc,
                ]
            );

            $order->setShippingDescription($description);
        }
    }
}