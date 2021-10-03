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

        $wsdl = $config['webservice_endpoint'];
        $siteId = $config['site_id'];
        $certificate = $config['certificate'];
        $ctxMode = $config['ctx_mode'];

        $client = new PayzenClient($wsdl, $siteId, $certificate, $ctxMode);

        $client->init();

        return $client;
    }
}
