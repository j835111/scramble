<?php

namespace Symfony\Config;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class ScrambleConfig implements \Symfony\Component\Config\Builder\ConfigBuilderInterface
{
    private $apiPath;
    private $docsPath;
    private $_usedProperties = [];

    /**
     * The path where the OpenAPI specification is served.
     * @default '/docs/api.json'
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function apiPath($value): static
    {
        $this->_usedProperties['apiPath'] = true;
        $this->apiPath = $value;

        return $this;
    }

    /**
     * The path where the API documentation UI is served.
     * @default '/docs/api'
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function docsPath($value): static
    {
        $this->_usedProperties['docsPath'] = true;
        $this->docsPath = $value;

        return $this;
    }

    public function getExtensionAlias(): string
    {
        return 'scramble';
    }

    public function __construct(array $value = [])
    {
        if (array_key_exists('api_path', $value)) {
            $this->_usedProperties['apiPath'] = true;
            $this->apiPath = $value['api_path'];
            unset($value['api_path']);
        }

        if (array_key_exists('docs_path', $value)) {
            $this->_usedProperties['docsPath'] = true;
            $this->docsPath = $value['docs_path'];
            unset($value['docs_path']);
        }

        if ([] !== $value) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($value)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['apiPath'])) {
            $output['api_path'] = $this->apiPath;
        }
        if (isset($this->_usedProperties['docsPath'])) {
            $output['docs_path'] = $this->docsPath;
        }

        return $output;
    }

}
