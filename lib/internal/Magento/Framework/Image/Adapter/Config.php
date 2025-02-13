<?php
/**
 * Copyright 2011 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Framework\Image\Adapter;

/**
 * Image config provider.
 */
class Config implements ConfigInterface, UploadConfigInterface
{
    public const XML_PATH_IMAGE_ADAPTER = 'dev/image/default_adapter';

    public const XML_PATH_IMAGE_ADAPTERS = 'dev/image/adapters';

    /**
     * Config path for the maximal image width value
     * @deprecated Used in a method that is deprecated
     * @see Nothing
     */
    public const XML_PATH_MAX_WIDTH_IMAGE = 'system/upload_configuration/max_width';

    /**
     * Config path for the maximal image height value
     * @deprecated Used in a method that is deprecated
     * @see Nothing
     */
    public const XML_PATH_MAX_HEIGHT_IMAGE = 'system/upload_configuration/max_height';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getAdapterAlias()
    {
        return (string)$this->config->getValue(self::XML_PATH_IMAGE_ADAPTER);
    }

    /**
     * @inheritDoc
     *
     * @return mixed
     */
    public function getAdapters()
    {
        return $this->config->getValue(self::XML_PATH_IMAGE_ADAPTERS);
    }

    /**
     * Get Maximum Image Width resolution in pixels. For image resizing on client side.
     *
     * @return int
     * @deprecated 102.0.1
     * @see \Magento\Backend\Model\Image\UploadResizeConfigInterface::getMaxHeight()
     */
    public function getMaxWidth(): int
    {
        return (int)$this->config->getValue(self::XML_PATH_MAX_WIDTH_IMAGE);
    }

    /**
     * Get Maximum Image Height resolution in pixels. For image resizing on client side.
     *
     * @return int
     * @deprecated 102.0.1
     * @see \Magento\Backend\Model\Image\UploadResizeConfigInterface::getMaxHeight()
     */
    public function getMaxHeight(): int
    {
        return (int)$this->config->getValue(self::XML_PATH_MAX_HEIGHT_IMAGE);
    }
}
