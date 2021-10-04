<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\EventListener;

use Antilop\SyliusPayzenBundle\Factory\PayzenClientFactory;
use App\Entity\Shipping\Shipment;
use App\Entity\Subscription\SubscriptionDraftOrder;
use App\Entity\Subscription\SubscriptionState;
use App\Event\SubscriptionEvent;
use App\Repository\SubscriptionDraftOrderRepository;
use App\StateMachine\SubscriptionDraftOrderTransitions;
use Google\Cloud\PubSub\Subscription;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;

class UpdateBankDetailListener
{
    /** @var StateMachineFactoryInterface */
    private $stateMachineFactory;

    public function __construct(
        StateMachineFactoryInterface $stateMachineFactory
    )
    {
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public function updateBankDetail(SubscriptionEvent $subscriptionEvent): void
    {
        /** @var Subscription $subscription */
        $subscription = $subscriptionEvent->getSubscription();

        /** @var SubscriptionDraftOrder $draftOrder */
        $draftOrder = $subscription->getDraftOrder();
        if ($draftOrder->getState() === SubscriptionDraftOrder::STATE_DRAFT_PAYMENT_FAILED) {
            $stateMachine = $this->stateMachineFactory->get($draftOrder, SubscriptionDraftOrderTransitions::GRAPH);
            $stateMachine->apply(SubscriptionDraftOrderTransitions::TRANSITION_PROCESS_PAYMENT);
        }
    }
}
