<?php

namespace Common\Billing\Gateways\Paypal;

use Omnipay\PayPal\Message\AbstractRestRequest;

class PaypalFetchBillingAgreementRequest extends AbstractRestRequest
{
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $this->validate('transactionReference');

        return [];
    }

    /**
     * Get HTTP Method.
     *
     * This is nearly always POST but can be over-ridden in sub classes.
     *
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'GET';
    }

    /**
     * Get transaction endpoint.
     *
     * Subscriptions are executed using the /billing-agreements resource.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return parent::getEndpoint() . '/payments/billing-agreements/' .
            $this->getTransactionReference();
    }
}
