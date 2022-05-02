<?php

declare(strict_types=1);

namespace Antilop\SyliusPayzenBundle\Factory;

use Antilop\SyliusPayzenBundle\Api\PayzenClient;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;

final class PayzenClientFactory
{
    private $paymentMethodRepository;

    public function __construct(
        PaymentMethodRepositoryInterface $paymentMethodRepository
    )
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function create()
    {
        $payzenPaymentMethod = $this->paymentMethodRepository->findOneBy(['code' => 'PAYZEN']);

        $config = $payzenPaymentMethod->getGatewayConfig()->getConfig();

        $username = $config['site_id'];
        $password = $config['rest_password'];
        $endpoint = $config['rest_endpoint'];

        $client = new PayzenClient($username, $password, $endpoint);

        $client->init();

        return $client;
    }
}
