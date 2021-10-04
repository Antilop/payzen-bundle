<?php

namespace Antilop\SyliusPayzenBundle;

use Antilop\SyliusPayzenBundle\DependencyInjection\AntilopSyliusPayzenExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AntilopSyliusPayzenBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new AntilopSyliusPayzenExtension();
        }

        return $this->extension;
    }
}
