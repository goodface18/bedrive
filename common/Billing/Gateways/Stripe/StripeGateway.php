<?php namespace Common\Billing\Gateways\Stripe;

use Illuminate\Http\Request;
use Omnipay\Omnipay;
use App\User;
use Omnipay\Stripe\Gateway;
use Omnipay\Common\CreditCard;
use Common\Billing\GatewayException;
use Omnipay\Common\Exception\InvalidCreditCardException;
use Common\Billing\Gateways\Contracts\GatewayInterface;

class StripeGateway implements GatewayInterface
{
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var StripePlans
     */
    private $plans;

    /**
     * @var StripeSubscriptions
     */
    private $subscriptions;

    /**
     * StripeGateway constructor.
     */
    public function __construct()
    {
        $this->gateway = Omnipay::create('Stripe');

        $this->gateway->initialize(array(
            'apiKey' => config('services.stripe.secret'),
        ));

        $this->plans = new StripePlans($this->gateway);
        $this->subscriptions = new StripeSubscriptions($this->gateway);
    }

    public function plans()
    {
        return $this->plans;
    }

    public function subscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * Check if specified webhook is valid.
     *
     * @param Request $request
     * @return bool
     */
    public function webhookIsValid(Request $request)
    {
        return ! is_null($this->gateway->fetchEvent(
            ['eventReference' => $request->get('id')]
        )->send()->getEventReference());
    }

    /**
     * Add a new card to customer on stripe.
     *
     * @param User $user
     * @param string $token
     * @return User
     * @throws GatewayException
     * @throws InvalidCreditCardException
     */
    public function addCard(User $user, $token)
    {
        $params['token'] = $token;

        //create new stripe customer or attach to existing one
        if ($user->stripe_id) {
            $params['customerReference'] = $user->stripe_id;
        } else {
            $params['email'] = $user->email;
        }

        $response = $this->gateway->createCard($params)->send();

        if ( ! $response->isSuccessful()) {
            $data = $response->getData();

            //if card validation fails on stripe, throw exception so we can show message to user
            if (isset($data['error']['type']) && $data['error']['type'] === 'card_error') {
                throw new InvalidCreditCardException($data['error']['message']);
            }

            throw new GatewayException($response->getMessage());
        }

        //store stripe id on user model, if needed
        if ($user->stripe_id !== $stripeId = $response->getCustomerReference()) {
            $user->fill(['stripe_id' => $stripeId])->save();
        }

        //TODO: check if user has more then one card
        $this->setDefaultCustomerSource($user, $response->getCardReference());

        return $user;
    }

    /**
     * Change default customer payment source to specified card.
     *
     * @param User $user
     * @param string $cardReference
     * @return null|string
     * @throws GatewayException
     */
    public function setDefaultCustomerSource(User $user, $cardReference)
    {
        $response = $this->gateway->updateCustomer([
            'customerReference' => $user->stripe_id,
        ])->sendData(['default_source' => $cardReference]);

        //default source
        $cardData = array_first($response->getData()['sources']['data'], function($card) use($cardReference) {
            return $card['id'] === $cardReference;
        });

        if ( ! $response->isSuccessful()) {
            throw new GatewayException($response->getMessage());
        }

        $user->fill([
            'card_last_four' => $cardData['last4'],
            'card_brand'     => $cardData['brand'],
        ])->save();

        return $response->getCustomerReference();
    }
}