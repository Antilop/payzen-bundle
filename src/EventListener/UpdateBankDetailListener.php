<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\EventListener;

use App\Entity\Shipping\Shipment;
use App\Entity\Subscription\SubscriptionDraftOrder;
use App\Entity\Subscription\SubscriptionState;
use App\Event\SubscriptionEvent;
use App\Repository\SubscriptionDraftOrderRepository;
use App\StateMachine\SubscriptionDraftOrderTransitions;
use App\Entity\Subscription\Subscription;
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

        $paymentMethod = $subscription->getMethod();

        if ($paymentMethod->getCode() === 'PAYZEN') {
            /** @var SubscriptionDraftOrder $draftOrder */
            $draftOrder = $subscription->getDraftOrder();
            if ($draftOrder->getState() === SubscriptionDraftOrder::STATE_DRAFT_PAYMENT_FAILED && !$subscription->hasSoldOutVariant()) {
                $stateMachine = $this->stateMachineFactory->get($draftOrder, SubscriptionDraftOrderTransitions::GRAPH);
                $stateMachine->apply(SubscriptionDraftOrderTransitions::TRANSITION_PROCESS_PAYMENT);
            }else if($subscription->hasSoldOutVariant()){
                if(!$subscription->setManual(true)) $subscription->setManual(true);
            }
        }
    }
}
