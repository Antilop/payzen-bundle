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

        $params = $this->getParams($order);
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
     * Get parameters
     *
     * @param OrderInterface $order
     *
     * @return array
     */
    protected function getParams(OrderInterface $order)
    {
        $customer = $order->getCustomer();
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        $notifyToken = $this->payum->getTokenFactory()->createNotifyToken('payzen');

        return [
            'amount' => $order->getTotal(),
            'currency' => $order->getCurrencyCode(),
            'orderId' => 'order_' . $order->getId() . '_' . uniqid(),
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
            'ipnTargetUrl' => $notifyToken->getTargetUrl()
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
