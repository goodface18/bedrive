<?php namespace Common\Billing\Gateways\Paypal;

use Common\Billing\BillingPlan;
use Common\Billing\Subscription;
use Illuminate\Http\Request;
use Common\Core\Controller;

class PaypalController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var BillingPlan
     */
    private $billingPlan;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @var PaypalGateway
     */
    private $paypal;

    /**
     * PaypalController constructor.
     *
     * @param Request $request
     * @param BillingPlan $billingPlan
     * @param Subscription $subscription
     * @param PaypalGateway $paypal
     */
    public function __construct(
        Request $request,
        BillingPlan $billingPlan,
        Subscription $subscription,
        PaypalGateway $paypal
    )
    {
        $this->paypal = $paypal;
        $this->request = $request;
        $this->billingPlan = $billingPlan;
        $this->subscription = $subscription;

        $this->middleware('auth', ['except' => [
            'approvedCallback', 'canceledCallback', 'loadingPopup']
        ]);
    }

    /**
     * Create subscription agreement on paypal.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Common\Billing\GatewayException
     */
    public function createSubscriptionAgreement()
    {
        $this->validate($this->request, [
            'plan_id' => 'required|integer|exists:billing_plans,id',
            'start_date' => 'string'
        ]);

        $urls = $this->paypal->subscriptions()->create(
            $this->billingPlan->findOrFail($this->request->get('plan_id')),
            $this->request->user(),
            $this->request->get('start_date')
        );

        return $this->success(['urls' => $urls]);
    }

    /**
     * Execute subscription agreement on paypal.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Common\Billing\GatewayException
     */
    public function executeSubscriptionAgreement()
    {
        $this->validate($this->request, [
            'agreement_id' => 'required|string|min:1',
            'plan_id' => 'required|integer|exists:billing_plans,id',
        ]);

        $subscriptionId = $this->paypal->subscriptions()->executeAgreement(
            $this->request->get('agreement_id')
        );

        $plan = $this->billingPlan->findOrFail($this->request->get('plan_id'));
        $this->request->user()->subscribe('paypal', $subscriptionId, $plan);

        return $this->success(['user' => $this->request->user()->load('subscriptions')]);
    }

    /**
     * Called after user approves paypal payment.
     */
    public function approvedCallback()
    {
        return view('common::billing/paypal-popup')->with([
            'token' => $this->request->get('token'),
            'status' => 'success',
        ]);
    }

    /**
     * Called after user cancels paypal payment.
     */
    public function canceledCallback()
    {
        return view('common::billing/paypal-popup')->with([
            'token' => $this->request->get('token'),
            'status' => 'cancelled',
        ]);
    }

    /**
     * Show loading view for paypal.
     */
    public function loadingPopup()
    {
        return view('common::billing/loading-popup');
    }
}