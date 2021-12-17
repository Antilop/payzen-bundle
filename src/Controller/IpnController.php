<?php

namespace Antilop\SyliusPayzenBundle\Controller;

use Antilop\SyliusPayzenBundle\Factory\PayzenSdkClientFactory;
use App\Entity\Subscription\SubscriptionState;
use App\Service\SubscriptionService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Payum\Core\Payum;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class IpnController
{
    /** @var Payum */
    protected $payum;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var PayzenSdkClient */
    protected $payzenSdkClientFactory;

    /** @var FactoryInterface */
    protected $factory;

    /** @var SubscriptionService */
    protected $subscriptionService;

    /** @var EntityManager */
    protected $em;

    public function __construct(
        Payum $payum,
        OrderRepositoryInterface $orderRepository,
        PayzenSdkClientFactory $payzenSdkClientFactory,
        FactoryInterface $factory,
        SubscriptionService $subscriptionService,
        EntityManager $em
    ) {
        $this->payum = $payum;
        $this->orderRepository = $orderRepository;
        $this->payzenSdkClientFactory = $payzenSdkClientFactory;
        $this->factory = $factory;
        $this->subscriptionService = $subscriptionService;
        $this->em = $em;
    }

    public function completeOrderAction(Request $request, $orderId): Response
    {
        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findCartById($orderId);

        if (null === $order) {
            throw new NotFoundHttpException(sprintf('Order with id "%s" does not exist.', $orderId));
        }

        $payzenClient = $this->payzenSdkClientFactory->create();
        if (!$payzenClient->checkSignature()) {
            throw new NotFoundHttpException(sprintf('Invalid signature for Order "%s".', $order->getId()));
        }

        $rawAnswer = $payzenClient->getFormAnswer();
        if (!empty($rawAnswer)) {
            $formAnswer = $rawAnswer['kr-answer'];
            $orderStatus = $formAnswer['orderStatus'];

            if ($orderStatus === 'PAID') {
                $payment = $order->getLastPayment(PaymentInterface::STATE_CART);
                if (!empty($payment)) {
                    $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);
                    $stateMachine->apply(PaymentTransitions::TRANSITION_CREATE);

                    $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);
                    $stateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE);
    
                    $stateMachine = $this->factory->get($order, OrderCheckoutTransitions::GRAPH);
                    $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_COMPLETE);

                    $payment->setDetails($formAnswer);

                    $this->em->persist($payment);
                    $this->em->persist($order);
                    $this->em->flush();
                }
            }
        }

        return new Response('OK');
    }

    public function updateSubscriptionBankDetailsAction(Request $request, $orderId): Response
    {
        /** @var SubscriptionDraftOrder|null $order */
        $order = $this->orderRepository->findCartById($orderId);

        if (null === $order) {
            throw new NotFoundHttpException(sprintf('Order with id "%s" does not exist.', $orderId));
        }

        $payzenClient = $this->payzenSdkClientFactory->create();
        if (!$payzenClient->checkSignature()) {
            throw new NotFoundHttpException(sprintf('Invalid signature for Order "%s".', $order->getId()));
        }

        $rawAnswer = $payzenClient->getFormAnswer();
        if (!empty($rawAnswer)) {
            $formAnswer = $rawAnswer['kr-answer'];
            $orderStatus = $formAnswer['orderStatus'];

            if ($orderStatus === 'PAID') {
                $subscription = $order->getSubscription();
                $month = $formAnswer['transactions'][0]['transactionDetails']['cardDetails']['expiryMonth'];
                $year = $formAnswer['transactions'][0]['transactionDetails']['cardDetails']['expiryYear'];
                $date = $year . '-' . str_pad($month, 2, '0',  STR_PAD_LEFT) . '-01';
                $subscription->setCardExpiration(new DateTime($date));

                $payment = $order->getLastPayment(PaymentInterface::STATE_NEW);
                $payment->setDetails($formAnswer);

                if (SubscriptionState::STATE_PAYMENT_FAILED === $subscription->getState()) {
                    $this->subscriptionService->paymentRetryOnFail($subscription);
                }

                $this->em->persist($payment);
                $this->em->persist($order);
                $this->em->persist($subscription);
                $this->em->flush();
            }
        }

        return new Response('OK');
    }
}
