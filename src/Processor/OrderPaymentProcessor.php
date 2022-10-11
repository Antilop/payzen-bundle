<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\Processor;

use Antilop\SyliusPayzenBundle\Factory\PayzenClientFactory;
use App\Entity\Subscription\SubscriptionDraftOrder;
use App\StateMachine\SubscriptionDraftOrderTransitions;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Model\OrderInterface as OrderInterfaceAlias;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Payment\Model\PaymentInterface as PaymentInterfaceAlias;
use Sylius\Component\Payment\PaymentTransitions;
use Webmozart\Assert\Assert;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;
use Sylius\Component\Core\Payment\Provider\OrderPaymentProviderInterface;

final class OrderPaymentProcessor implements OrderProcessorInterface
{
    /** @var OrderProcessorInterface */
    private $baseOrderPaymentProcessor;

    /** @var StateMachineFactoryInterface */
    private $stateMachineFactory;

    /** @var PayzenClientFactory */
    private $payzenClientFactory;

    /** @var OrderPaymentProviderInterface */
    private $orderPaymentProvider;

    public function __construct(
        OrderProcessorInterface $baseOrderPaymentProcessor,
        StateMachineFactoryInterface $stateMachineFactory,
        PayzenClientFactory $payzenClientFactory,
        OrderPaymentProviderInterface $orderPaymentProvider
    ) {
        $this->baseOrderPaymentProcessor = $baseOrderPaymentProcessor;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->payzenClientFactory = $payzenClientFactory;
        $this->orderPaymentProvider = $orderPaymentProvider;
    }

    private function applyTransition($payment, $transition): void
    {
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);
        $stateMachine->apply($transition);
    }

    public function process(OrderInterfaceAlias $order): void
    {
        Assert::isInstanceOf($order, OrderInterface::class);

        if ($order instanceof SubscriptionDraftOrder) {
            $lastPayment = $order->getLastPayment(PaymentInterfaceAlias::STATE_NEW);
            if ($lastPayment !== null && $lastPayment->getMethod()->getCode() === 'PAYZEN') {
                $payzenClient = $this->payzenClientFactory->create();
                $result = $payzenClient->processPayment($order);
                $lastPayment->setDetails($result);

                if (isset($result['success']) && boolval($result['success'])) {
                    $this->applyTransition($lastPayment, PaymentTransitions::TRANSITION_PROCESS);
                    $this->applyTransition($lastPayment, PaymentTransitions::TRANSITION_COMPLETE);
                } else {
                    $this->applyTransition($lastPayment, PaymentTransitions::TRANSITION_FAIL);
                    $stateMachine = $this->stateMachineFactory->get($order, SubscriptionDraftOrderTransitions::GRAPH);
                    $stateMachine->apply(SubscriptionDraftOrderTransitions::TRANSITION_PAYMENT_FAIL);

                    $newPayment = $this->orderPaymentProvider->provideOrderPayment($order, PaymentInterface::STATE_CART);
                    $order->addPayment($newPayment);
                }
            }
        }

        $this->baseOrderPaymentProcessor->process($order);
    }

    private function getFactoryName(PaymentInterface $payment): string
    {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();
        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        return $gatewayConfig->getFactoryName();
    }
}
