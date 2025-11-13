<?php

namespace Snowdog\Hyva\Checkout\Inpost\Magewire;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Smartmage\Inpost\Model\Checkout\Processor;
use Smartmage\Inpost\Model\Config\Source\DefaultWaySending;
use Smartmage\Inpost\Model\ConfigProvider;

class Locker extends Component implements EvaluationInterface
{
    private const INPOST_METHOD_CODES = [
        DefaultWaySending::INPOST_LOCKER_STANDARD,
        DefaultWaySending::INPOST_LOCKER_STANDARD_COD
    ];

    public ?string $locker = null;

    public ?string $address = null;

    private Session $session;

    private ConfigProvider $configProvider;

    private CartRepositoryInterface $quoteRepository;

    public function __construct(
        Session $session,
        ConfigProvider $configProvider,
        CartRepositoryInterface $quoteRepository,
    ) {
        $this->session = $session;
        $this->configProvider = $configProvider;
        $this->quoteRepository = $quoteRepository;
    }

    public function mount(): void
    {
        $this->locker = $this->session->getQuote()->getData('inpost_locker_id');
        if ($this->locker) {
            $this->address = $this->session->getData('inpost_locker_address_' . $this->locker);
        }
    }

    public function select(string $locker, string $address): void
    {
        $this->locker = $locker;
        $this->address = $address;
        $quote = $this->session->getQuote();

        $this->session->setData('inpost_locker_address_' . $locker, $address);
        $quote->setData('inpost_locker_id', $locker);
        $this->quoteRepository->save($quote);
    }

    public function getGeoToken(): string
    {
        return $this->configProvider->getGeowidgetToken() ?? '';
    }

    public function getGeoJsUrl(): string
    {
        return match ($this->configProvider->getMode()) {
            'test' => 'https://sandbox-easy-geowidget-sdk.easypack24.net/inpost-geowidget.js',
            'prod' => 'https://geowidget.inpost.pl/inpost-geowidget.js',
            default => '',
        };
    }

    public function getGeoCssUrl(): string
    {
        return match ($this->configProvider->getMode()) {
            'test' => 'https://sandbox-easy-geowidget-sdk.easypack24.net/inpost-geowidget.css',
            'prod' => 'https://geowidget.inpost.pl/inpost-geowidget.css',
            default => '',
        };
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if (!in_array($this->session->getQuote()->getShippingAddress()->getShippingMethod(), self::INPOST_METHOD_CODES)) {
            return $resultFactory->createSuccess();
        }

        if ($this->locker === null) {
            $errorMessageEvent = $resultFactory->createErrorMessageEvent(__('Please select locker'))
                ->withCustomEvent('shipping:method:error');
            return $resultFactory->createValidation('validateInpostLocker')
                ->withFailureResult($errorMessageEvent);
        }

        $quote = $this->session->getQuote();
        $quote->getResource()->getConnection()->update(
            $quote->getResource()->getMainTable(),
            [
                'inpost_locker_id' => $this->locker,
            ],
            ['entity_id = ? ' => $quote->getId()]
        );

        return $resultFactory->createSuccess();
    }
}
