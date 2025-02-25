<?php

namespace App\SnowflakeBundle;

use App\SnowflakeBundle\DependencyInjection\SnowflakeExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Snowflake Bundle for Symfony
 *
 * This bundle provides the Snowflake ID generator service and related
 * functionality, making it easier to use distributed IDs in a Symfony application.
 */
class SnowflakeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SnowflakeExtension();
        }

        return $this->extension;
    }
}