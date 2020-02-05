<?php namespace Common\Billing\Subscriptions;

use Closure;
use Common\Billing\BillingPlan;
use Common\Billing\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Common\Database\Paginator;

class SubscriptionsController extends Controller
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
     * @param Request $request
     * @param BillingPlan $billingPlan
     * @param Subscription $subscription
     */
    public function __construct(
        Request $request,
        BillingPlan $billingPlan,
        Subscription $subscription
    )
    {
        $this->request = $request;
        $this->billingPlan = $billingPlan;
        $this->subscription = $subscription;

        $this->middleware('auth');
    }

    /**
     * Paginate all existing subscriptions.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        $this->authorize('index', Subscription::class);

        $paginator = (new Paginator($this->subscription))
            ->with('user');

        $paginator->searchCallback = function(Builder $query, $searchTerm) {
            $query->whereHas('user', function(Builder $query) use($searchTerm) {
                $query->where('email', 'like', "$searchTerm%");
            })->orWhere('gateway', 'like', "$searchTerm%");
        };

        return $paginator->paginate($this->request->all());
    }

    /**
     * Create a new subscription.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store()
    {
        $this->authorize('update', Subscription::class);

        $this->validate($this->request, [
            'user_id' => 'required|exists:users,id|unique:subscriptions',
            'renews_at' => 'required_without:ends_at|date|nullable',
            'ends_at' => 'required_without:renews_at|date|nullable',
            'plan_id' => 'required|integer|exists:billing_plans,id',
            'description' => 'string|nullable',
        ]);

        $subscription = $this->subscription->create($this->request->all());

        return $this->success(['subscription' => $subscription]);
    }

    /**
     * Update existing subscription.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update($id)
    {
        $this->authorize('update', Subscription::class);

        $this->validate($this->request, [
            'user_id' => 'exists:users,id|unique:subscriptions',
            'renews_at' => 'date|nullable',
            'ends_at' => 'date|nullable',
            'plan_id' => 'integer|exists:billing_plans,id',
            'description' => 'string|nullable'
        ]);

        $subscription = $this->subscription->findOrFail($id);

        $subscription->fill($this->request->all())->save();

        return $this->success(['subscription' => $subscription]);
    }

    /**
     * Change plan of specified subscription.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePlan($id)
    {
        $this->validate($this->request, [
            'newPlanId' => 'required|integer|exists:billing_plans,id'
        ]);

        /** @var Subscription $subscription */
        $subscription = $this->subscription->findOrFail($id);
        $plan = $this->billingPlan->findOrfail($this->request->get('newPlanId'));

        $subscription->changePlan($plan);

        return $this->success(['user' => $subscription->user()->first()]);
    }

    /**
     * Cancel specified subscription.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function cancel($id)
    {
        $this->validate($this->request, [
            'delete' => 'boolean'
        ]);

        /** @var Subscription $subscription */
        $subscription = $this->subscription->findOrFail($id);

        if ($this->request->get('delete')) {
            $subscription->cancelAndDelete();
        } else {
            $subscription->cancel();
        }

        return $this->success(['user' => $subscription->user()->first()]);
    }

    /**
     * Resume specified subscription.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Common\Billing\GatewayException
     */
    public function resume($id)
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscription->with('plan')->findOrFail($id);
        $subscription->resume();

        return $this->success(['subscription' => $subscription]);
    }
}