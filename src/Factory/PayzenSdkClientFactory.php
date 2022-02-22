<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\Factory;

use Antilop\SyliusPayzenBundle\Api\PayzenSdkClient;
use Payum\Core\Payum;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;

final class PayzenSdkClientFactory
{
    /** @var PaymentMethodRepositoryInterface */
    private $paymentMethodRepository;

    /** @var Payum */
    private $payum;

    /** @var FactoryInterface */
    protected $factory;

    /**
     * Constructor
     *
     * @param PaymentMethodRepositoryInterface $paymentMethodRepository
     * @param Payum                            $payum
     * @param FactoryInterface                 $factory
     */
    public function __construct(PaymentMethodRepositoryInterface $paymentMethodRepository, Payum $payum, FactoryInterface $factory)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->payum = $payum;
        $this->factory = $factory;
    }

    /**
     * Create PayzenSdkClient
     *
     * @return PayzenSdkClient
     */
    public function create()
    {
        $payzenPaymentMethod = $this->paymentMethodRepository->findOneBy(['code' => 'PAYZEN']);

        $config = $payzenPaymentMethod->getGatewayConfig()->getConfig();

        $username = $config['site_id'];
        $password = $config['rest_password'];
        $endpoint = $config['rest_endpoint'];

        $client = new PayzenSdkClient($username, $password, $endpoint, $this->payum, $this->factory);

        $client->init();

        return $client;
    }
}
