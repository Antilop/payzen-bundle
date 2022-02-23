<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\Processor;

use Antilop\SyliusPayzenBundle\Factory\PayzenClientFactory;
use App\Entity\Subscription\SubscriptionDraftOrder;
use App\StateMachine\SubscriptionDraftOrderTransitions;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Webmozart\Assert\Assert;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;

final class OrderPaymentProcessor implements OrderProcessorInterface
{
    /** @var OrderProcessorInterface */
    private $baseOrderPaymentProcessor;

    /** @var StateMachineFactoryInterface */
    private $stateMachineFactory;

    /** @var PayzenClientFactory */
    private $payzenClientFactory;

    public function __construct(
        OrderProcessorInterface $baseOrderPaymentProcessor,
        StateMachineFactoryInterface $stateMachineFactory,
        PayzenClientFactory $payzenClientFactory
    ) {
        $this->baseOrderPaymentProcessor = $baseOrderPaymentProcessor;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->payzenClientFactory = $payzenClientFactory;
    }

    private function applyTransition($payment, $transition): void
    {
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);
        $stateMachine->apply($transition);
    }

    public function process(OrderInterface $order): void
    {
        Assert::isInstanceOf($order, \Sylius\Component\Core\Model\OrderInterface::class);

        if ($order instanceof SubscriptionDraftOrder) {
            if (0 === $order->getTotal()) {
                $removablePayments = $order->getPayments()->filter(function (PaymentInterface $payment): bool {
                    return $payment->getState() === OrderPaymentStates::STATE_CART;
                });

                foreach ($removablePayments as $payment) {
                    $order->removePayment($payment);
                }

                return;
            }

            $lastPayment = $order->getLastPayment(PaymentInterface::STATE_NEW);
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
                }
            }

            if ($lastPayment !== null) {
                $lastPayment->setCurrencyCode($order->getCurrencyCode());
                $lastPayment->setAmount($order->getTotal());

                return;
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

        return (string) $gatewayConfig->getFactoryName();
    }
}
