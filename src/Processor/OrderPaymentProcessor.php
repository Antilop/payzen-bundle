<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\Processor;

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

    public function __construct(
        OrderProcessorInterface $baseOrderPaymentProcessor,
        StateMachineFactoryInterface $stateMachineFactory
    ) {
        $this->baseOrderPaymentProcessor = $baseOrderPaymentProcessor;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    private function applyTransition($payment, $transition): void
    {
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);
        $stateMachine->apply($transition);
    }

    public function process(OrderInterface $order): void
    {

        Assert::isInstanceOf($order, \Sylius\Component\Core\Model\OrderInterface::class);
        /** @var PaymentInterface|null $payment */

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
        if ($lastPayment !== null && $this->getFactoryName($lastPayment) === 'payzen') {
            $this->applyTransition($lastPayment, PaymentTransitions::TRANSITION_PROCESS);
            $this->applyTransition($lastPayment, PaymentTransitions::TRANSITION_COMPLETE);
            $order->setPaymentState(OrderPaymentStates::STATE_PAID);
            $order->setState(OrderInterface::STATE_FULFILLED);
        }

        if (null !== $lastPayment) {
            $lastPayment->setCurrencyCode($order->getCurrencyCode());
            $lastPayment->setAmount($order->getTotal());

            return;
        }

        $paymentCart = $order->getLastPayment(PaymentInterface::STATE_CART);
        if (null !== $paymentCart) {
            $details = $paymentCart->getDetails();
            if (!isset($details['success']) && !empty($details['token'])) {
                $success = true;
                if ($details['token'] == 'TOKEN_NOK') {
                    $success = false;
                }

                $paymentCart->setDetails([
                    'token' => $details['token'],
                    'success' => $success
                ]);
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
