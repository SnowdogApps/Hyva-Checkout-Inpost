<?php

namespace Snowdog\Hyva\Checkout\Inpost\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class ConvertQuoteToOrder implements ObserverInterface
{
    private Session $session;
    private LoggerInterface $logger;

    public function __construct(
        Session $session,
        LoggerInterface $logger
    ) {
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getQuote();
        /** @var Order $order */
        $order = $observer->getOrder();

        $methodCode = $order->getShippingMethod();
        if (!str_starts_with($methodCode, 'inpostlocker_')) {
            return;
        }

        $lockerId = $quote->getInpostLockerId();
        if (empty($lockerId)) {
            $this->logger->critical('snow_inpost', ['quote_id' => $quote->getId()]);
            throw new LocalizedException(__('Choose locker again'));
        }
        $pickupDesc = $this->session->getData('inpost_locker_address_' . $lockerId);

        $description = implode(
            PHP_EOL,
            [
                $order->getShippingDescription(),
                $pickupDesc,
                $lockerId,
            ]
        );

        $order->setShippingDescription($description);
    }
}
