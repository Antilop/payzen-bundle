<?php

namespace Antilop\SyliusPayzenBundle\Api;

use Lyra\Client as LyraClient;
use Payum\Core\Payum;
use Sylius\Component\Core\Model\OrderInterface;

class PayzenSdkClient
{
    /** @var LyraClient */
    protected $client;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $endpoint;

    /** @var Payum */
    protected $payum;

    /**
     * Constructor
     *
     * @param string $username
     * @param string $password
     * @param string $endpoint
     * @param Payum $payum
     */
    public function __construct($username, $password, $endpoint, $payum)
    {
        $this->username = $username;
        $this->password = $password;
        $this->endpoint = $endpoint;
        $this->payum = $payum;
    }

    public function checkSignature()
    {
        return $this->client->checkHash($this->password);
    }

    /**
     * Generate form token
     *
     * @param OrderInterface $order
     * @param string         $action
     *
     * @return array
     */
    public function generateFormToken(OrderInterface $order, $action = 'CreatePayment')
    {
        if (empty($action)) {
            $action = 'CreatePayment';
        }

        $params = $this->getParams($order, $action);
        $response = $this->client->post('V4/Charge/' . $action, $params);

        if ($response['status'] !== 'SUCCESS') {
            return [
                'error' => $response['answer']['errorMessage'],
                'formToken' => false,
                'success' => false,
            ];
        }

        return [
            'error' => false,
            'formToken' => $response['answer']['formToken'],
            'success' => true,
        ];
    }

    /**
     * Get form answer
     *
     * @return array
     */
    public function getFormAnswer()
    {
        if (empty($_POST['kr-answer'])) {
            return [];
        }

        return $this->client->getParsedFormAnswer();
    }

    /**
     * Get parameters
     *
     * @param OrderInterface $order
     * @param string         $action
     *
     * @return array
     */
    protected function getParams(OrderInterface $order, $action)
    {
        $customer = $order->getCustomer();
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        $lastPayment = $order->getLastPayment();
        $paymentModel = $this->payum->getStorage('App\Entity\Payment\Payment')->find($lastPayment->getId());
        
        $ipnUrl = 'payzen_ipn_order_url';
        if ($action == 'CreateToken') {
            $ipnUrl = 'payzen_ipn_subscription_url';
        }

        $captureToken = $this->payum->getTokenFactory()->createToken(
            'payzen',
            $paymentModel,
            $ipnUrl,
            ['orderId' => $order->getId()]
        );

        return [
            'amount' => $order->getTotal(),
            'currency' => $order->getCurrencyCode(),
            'orderId' => $order->getNumber(),
            'customer' => [
                'reference' => $customer->getId(),
                'email' => $customer->getEmail(),
                'shippingDetails' => [
                    'firstName' => $shippingAddress->getFirstName(),
                    'lastName' => $shippingAddress->getLastName(),
                    'address' => $shippingAddress->getStreet(),
                    'address2' => $shippingAddress->getCompany(),
                    'zipCode' => $shippingAddress->getPostcode(),
                    'city' => $shippingAddress->getCity(),
                    'country' => $shippingAddress->getCountryCode(),
                    'phoneNumber' => $shippingAddress->getPhoneNumber(),
                ],
                'billingDetails' => [
                    'firstName' => $billingAddress->getFirstName(),
                    'lastName' => $billingAddress->getLastName(),
                    'address' => $billingAddress->getStreet(),
                    'address2' => $billingAddress->getCompany(),
                    'zipCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'country' => $billingAddress->getCountryCode(),
                    'phoneNumber' => $billingAddress->getPhoneNumber(),
                ],
            ],
            'ipnTargetUrl' => $captureToken->getTargetUrl()
        ];
    }

    /**
     * Init keys for SDK
     *
     * @return void
     */
    public function init()
    {
        LyraClient::setDefaultUsername($this->username);
        LyraClient::setDefaultPassword($this->password);
        LyraClient::setDefaultEndpoint($this->endpoint); 
        
        $this->client = new LyraClient();  
    }
}
