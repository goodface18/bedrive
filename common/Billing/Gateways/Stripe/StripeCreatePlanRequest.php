<?php namespace Common\Billing\Gateways\Stripe;

use Omnipay\Stripe\Message\CreatePlanRequest;

class StripeCreatePlanRequest extends CreatePlanRequest
{
    /**
     * Set the plan name
     *
     * @param string $planNickname
     * @return CreatePlanRequest provides a fluent interface.
     */
    public function setNickname($planNickname)
    {
        return $this->setParameter('nickname', $planNickname);
    }

    /**
     * Get the plan name
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->getParameter('nickname');
    }

    /**
     * Set the plan name
     *
     * @param $product
     * @return CreatePlanRequest provides a fluent interface.
     */
    public function setProduct($product)
    {
        return $this->setParameter('product', $product);
    }

    /**
     * Get the plan name
     *
     * @return string
     */
    public function getProduct()
    {
        return $this->getParameter('product');
    }

    public function getData()
    {
        $data = parent::getData();

        unset($data['name']);
        $data['product'] = $this->getProduct();
        $data['nickname'] = $this->getNickname();

        return $data;
    }
}