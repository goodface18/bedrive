<?php

namespace Common\Billing\Gateways\Paypal;

use Omnipay\PayPal\Message\AbstractRestRequest;

/**
 * PayPal REST Fetch Plan Request
 *
 * To get details about PayPal plans
 *
 * Example
 *
 * <code>
 *   // Fetch PayPal Plan
 *   $transaction = $this->gateway->fetchPlan([
 *      'planId' => 'P-000000000000000000000000',
 *   ]);
 *   $response = $transaction->send();
 *   $data = $response->getData();
 *   echo "Gateway getPlan response data == " . print_r($data, true) . "\n";
 * </code>
 *
 * @link https://developer.paypal.com/docs/api/payments.billing-plans/#billing-plans_get
 */
class PaypalFetchPlanRequest extends AbstractRestRequest
{
    /**
     *
     * Get the plan ID
     *
     * @return string
     */
    public function getPlanId()
    {
        return $this->getParameter('planId');
    }
    /**
     * Set the plan ID
     *
     * @param string $value
     * @return PaypalFetchPlanRequest
     */
    public function setPlanId($value)
    {
        return $this->setParameter('planId', $value);
    }

    public function getData()
    {
        $this->validate('planId');
        return array();
    }
    /**
     * Get HTTP Method.
     *
     * The HTTP method for list plans requests must be GET.
     *
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'GET';
    }
    public function getEndpoint()
    {
        return parent::getEndpoint() . '/payments/billing-plans/' . $this->getPlanId();
    }
}

