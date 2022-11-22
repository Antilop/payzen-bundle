<?php

namespace Antilop\SyliusPayzenBundle\Controller;

use Antilop\SyliusPayzenBundle\Factory\PayzenSdkClientFactory;
use App\Entity\Subscription\Subscription;
use App\Entity\Subscription\SubscriptionDraftOrder;
use App\Entity\Subscription\SubscriptionState;
use App\Service\SubscriptionService;
use App\StateMachine\OrderCheckoutStates;
use Doctrine\ORM\EntityManager;
use Payum\Core\Payum;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Payment\Provider\OrderPaymentProviderInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\StateResolver\StateResolverInterface;
use Sylius\Component\Payment\Model\PaymentInterface as PaymentInterfaceAlias;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Webmozart\Assert\Assert;

final class IpnController
{
    /** @var Payum */
    protected $payum;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var PaymentRepositoryInterface */
    protected $paymentRepository;

    /** @var PayzenSdkClientFactory */
    protected $payzenSdkClientFactory;

    /** @var FactoryInterface */
    protected $factory;

    /** @var SubscriptionService */
    protected $subscriptionService;

    /** @var EntityManager */
    protected $em;

    /** @var OrderPaymentProviderInterface */
    private $orderPaymentProvider;

    /** @var StateResolverInterface */
    private $orderPaymentStateResolver;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(
        Payum $payum,
        OrderRepositoryInterface $orderRepository,
        PaymentRepositoryInterface $paymentRepository,
        PayzenSdkClientFactory $payzenSdkClientFactory,
        FactoryInterface $factory,
        SubscriptionService $subscriptionService,
        EntityManager $em,
        OrderPaymentProviderInterface $orderPaymentProvider,
        StateResolverInterface $orderPaymentStateResolver
    ) {
        $this->payum = $payum;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->payzenSdkClientFactory = $payzenSdkClientFactory;
        $this->factory = $factory;
        $this->subscriptionService = $subscriptionService;
        $this->em = $em;
        $this->orderPaymentProvider = $orderPaymentProvider;
        $this->orderPaymentStateResolver = $orderPaymentStateResolver;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function completeOrderAction(Request $request, string $orderId): Response
    {
        $token = $this->payum->getHttpRequestVerifier()->verify($request);
        if (empty($token)) {
            throw new NotFoundHttpException(sprintf('Invalid security token for order with id "%s".', $orderId));
        }

        $payzenClient = $this->payzenSdkClientFactory->create();
        if (!$payzenClient->checkSignature()) {
            throw new NotFoundHttpException(sprintf('Invalid signature for Order "%s".', $orderId));
        }

        $rawAnswer = $payzenClient->getFormAnswer();
        if (!empty($rawAnswer)) {
            $formAnswer = $rawAnswer['kr-answer'];
            $orderStatus = $formAnswer['orderStatus'];
            Assert::inArray($orderStatus, ['PAID', 'UNPAID']);

            /** @var OrderInterface|null $order */
            $order = $this->orderRepository->find($orderId);
            Assert::notNull($order, sprintf('Order with id "%s" does not exist.', $orderId));

            /** @var PaymentInterface|null $payment */
            $payment = $this->getPayment($rawAnswer, $order);
            Assert::notNull($payment);

            $paymentSM = $this->factory->get($payment, PaymentTransitions::GRAPH);
            if ($paymentSM->can(PaymentTransitions::TRANSITION_CREATE)) {
                $paymentSM->apply(PaymentTransitions::TRANSITION_CREATE);
            }

            $content = null;
            switch ($orderStatus) {
                case 'PAID':
                    $payzenTotal = (int)$formAnswer['orderDetails']['orderTotalAmount'];
                    if ($payzenTotal != $order->getTotal()) {
                        $payment->setAmount($payzenTotal);
                        $this->orderPaymentStateResolver->resolve($order);
                    }

                    $this->markComplete($payment);

                    $stateMachine = $this->factory->get($order, OrderCheckoutTransitions::GRAPH);
                    if ($stateMachine->can(OrderCheckoutTransitions::TRANSITION_COMPLETE)) {
                        $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_COMPLETE);
                    }

                    $paymentDetails = $this->makeUniformPaymentDetails($formAnswer);
                    $payment->setDetails($paymentDetails);

                    $content = 'SUCCESS';
                    break;
                case 'UNPAID':
                    $this->markFailed($payment);

                    $content = 'FAIL';
                    break;
            }

            $this->em->flush();

            return new Response($content);
        }

        $this->payum->getHttpRequestVerifier()->invalidate($token);
        return new Response('Invalid form answer from payzen');
    }

    public function updateSubscriptionBankDetailsAction(Request $request, string $orderId): Response
    {
        $token = $this->payum->getHttpRequestVerifier()->verify($request);
        if (empty($token)) {
            throw new NotFoundHttpException(sprintf('Invalid security token for order with id "%s".', $orderId));
        }

        $payzenClient = $this->payzenSdkClientFactory->create();
        if (!$payzenClient->checkSignature()) {
            throw new NotFoundHttpException(sprintf('Invalid signature for Order "%s".', $orderId));
        }

        $rawAnswer = $payzenClient->getFormAnswer();
        if (!empty($rawAnswer)) {
            $formAnswer = $rawAnswer['kr-answer'];
            $orderStatus = $formAnswer['orderStatus'];
            Assert::inArray($orderStatus, ['PAID', 'UNPAID']);

            /** @var SubscriptionDraftOrder|null $order */
            $order = $this->orderRepository->find($orderId);
            Assert::notNull($order, sprintf('Order with id "%s" not found', $orderId));

            if ($order->getCheckoutState() === OrderCheckoutStates::STATE_DRAFT) {
                /** @var Subscription|null $subscription */
                $subscription = $order->getSubscription();
                Assert::notNull($subscription, sprintf('Subscription for draftOrder id "%s" not found.', $orderId));

                $content = null;
                switch ($orderStatus) {
                    case 'PAID':
                        $expiryMonth = 0;
                        $expiryYear = 0;
                        $cardToken = '';

                        if (array_key_exists('transactions', $formAnswer)) {
                            $transaction = current($formAnswer['transactions']);
                            $cardToken = $transaction['paymentMethodToken'];

                            if (array_key_exists('transactionDetails', $transaction)) {
                                $transactionDetails = $transaction['transactionDetails'];
                                $expiryMonth = $transactionDetails['cardDetails']['expiryMonth'];
                                $expiryYear = $transactionDetails['cardDetails']['expiryYear'];
                            }
                        }

                        if (!empty($expiryMonth) && !empty($expiryYear) && !empty($cardToken)) {
                            $this->subscriptionService->updateCard(
                                $subscription,
                                intval($expiryMonth),
                                intval($expiryYear),
                                $cardToken
                            );
                        }

                        $content = 'SUCCESS';
                        break;
                    case 'UNPAID':
                        $content = 'FAIL';
                        break;
                }

                $this->em->flush();
            }
            
            return new Response($content);
        }

        $this->payum->getHttpRequestVerifier()->invalidate($token);
        return new Response('Invalid form answer from payzen');
    }

    /**
     * @param array $rawAnswer
     * @param OrderInterface $order
     * @return PaymentInterface|null
     */
    private function getPayment(array $rawAnswer, OrderInterface $order): ?PaymentInterface
    {
        if ($this->propertyAccessor->isReadable($rawAnswer, '[kr-answer][transactions][0][metadata][payment_id]')) {
            $paymentIdMetadata = $this->propertyAccessor->getValue(
                $rawAnswer,
                '[kr-answer][transactions][0][metadata][payment_id]'
            );


            if (!is_null($paymentIdMetadata)) {
                return $this->paymentRepository->findOneBy(['id' => $paymentIdMetadata]);
            }
        }

        // Fallback for previous Payzen Payment created without metadata
        return $order->getLastPayment(PaymentInterfaceAlias::STATE_NEW);
    }

    /**
     * @param PaymentInterface $payment
     * @return void
     * @throws \SM\SMException
     */
    protected function markComplete(PaymentInterface $payment): void
    {
        $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);

        if ($stateMachine->can(PaymentTransitions::TRANSITION_PROCESS)) {
            $stateMachine->apply(PaymentTransitions::TRANSITION_PROCESS);
        }

        if ($stateMachine->can(PaymentTransitions::TRANSITION_COMPLETE)) {
            $stateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE);
        }
    }

    /**
     * @param PaymentInterface $payment
     * @return void
     * @throws \SM\SMException
     */
    protected function markFailed(PaymentInterface $payment): void
    {
        $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);

        if ($stateMachine->can(PaymentTransitions::TRANSITION_PROCESS)) {
            $stateMachine->apply(PaymentTransitions::TRANSITION_PROCESS);
        }

        if ($stateMachine->can(PaymentTransitions::TRANSITION_FAIL)) {
            $stateMachine->apply(PaymentTransitions::TRANSITION_FAIL);
        }
    }

    protected function makeUniformPaymentDetails($formAnswer)
    {
        if (empty($formAnswer) || !is_array($formAnswer)) {
            return [];
        }

        $details = $formAnswer;
        if (array_key_exists('transactions', $formAnswer)) {
            $transaction = current($formAnswer['transactions']);
            $details['vads_trans_uuid'] = $transaction['uuid'];

            if (array_key_exists('paymentMethodToken', $transaction)) {
                $details['vads_identifier'] = $transaction['paymentMethodToken'];
            }

            if (array_key_exists('transactionDetails', $transaction)) {
                $transactionDetails = $transaction['transactionDetails'];
                $details['vads_trans_id'] = $transactionDetails['cardDetails']['legacyTransId'];
                $details['vads_expiry_month'] = $transactionDetails['cardDetails']['expiryMonth'];
                $details['vads_expiry_year'] = $transactionDetails['cardDetails']['expiryYear'];
            }
        }

        return $details;
    }
}
