<?php

namespace KeGi\NetscapeCookieFileHandler\Configuration;

use KeGi\NetscapeCookieFileHandler\Cookie\CookieCollectionInterface;

trait MandatoryConfigurationTrait
{

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration() : ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @param ConfigurationInterface $configuration
     *
     * @return $this
     */
    public function setConfiguration(ConfigurationInterface $configuration
    ) {
        $this->configuration = $configuration;

        return $this;
    }
}
