<?php

namespace Snowdog\Hyva\Checkout\Inpost\Magewire;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session;
use Magewirephp\Magewire\Component;
use Smartmage\Inpost\Model\Checkout\Processor;
use Smartmage\Inpost\Model\ConfigProvider;

class Locker extends Component implements EvaluationInterface
{
    public ?string $locker = null;

    private Session $session;

    private ConfigProvider $configProvider;

    public function __construct(
        Session $session,
        ConfigProvider $configProvider,
    ) {
        $this->session = $session;
        $this->configProvider = $configProvider;
    }

    public function mount(): void
    {
        $this->locker = $this->session->getQuote()->getData('inpost_locker_id');
    }

    public function getGeoToken(): string
    {
        return $this->configProvider->getShippingConfigData('geowidget_token') ?? '';
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
        if ($this->session->getQuote()->getShippingAddress()->getShippingMethod() != 'inpostlocker_standard') {
            return $resultFactory->createSuccess();
        }

        if ($this->locker === null) {
            return $resultFactory->createBlocking(__('Please select locker'));
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