<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewrite\Model\Products;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogUrlRewrite\Model\Product\GetProductUrlRewriteDataByStore;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Service\V1\StoreViewService;
use Magento\Store\Model\Store;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\UrlPersistInterface;

/**
 * Update existing url rewrites or create new ones if needed
 */
class AppendUrlRewritesToProducts
{
    /**
     * @var ProductUrlRewriteGenerator
     */
    private $productUrlRewriteGenerator;

    /**
     * @var StoreViewService
     */
    private $storeViewService;

    /**
     * @var ProductUrlPathGenerator
     */
    private $productUrlPathGenerator;

    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * @var GetProductUrlRewriteDataByStore
     */
    private $getDataByStore;

    /**
     * @param ProductUrlRewriteGenerator $urlRewriteGenerator
     * @param StoreViewService $storeViewService
     * @param ProductUrlPathGenerator $urlPathGenerator
     * @param UrlPersistInterface $urlPersist
     * @param GetProductUrlRewriteDataByStore $getDataByStore
     */
    public function __construct(
        ProductUrlRewriteGenerator $urlRewriteGenerator,
        StoreViewService $storeViewService,
        ProductUrlPathGenerator $urlPathGenerator,
        UrlPersistInterface $urlPersist,
        GetProductUrlRewriteDataByStore $getDataByStore
    ) {
        $this->productUrlRewriteGenerator = $urlRewriteGenerator;
        $this->storeViewService = $storeViewService;
        $this->productUrlPathGenerator = $urlPathGenerator;
        $this->urlPersist = $urlPersist;
        $this->getDataByStore = $getDataByStore;
    }

    /**
     * Update existing rewrites and add for specific stores websites
     *
     * @param ProductInterface[] $products
     * @param array $storesToAdd
     * @throws UrlAlreadyExistsException
     */
    public function execute(array $products, array $storesToAdd): void
    {
        foreach ($products as $product) {
            $urls = $this->getProductUrlRewrites($product, $storesToAdd);
            $this->getDataByStore->clearProductUrlRewriteDataCache($product);
        }
        if (!empty($urls)) {
            $this->urlPersist->replace(array_merge(...$urls));
        }
    }

    /**
     * Generate store product URLs
     *
     * @param ProductInterface $product
     * @param array $stores
     * @return array
     */
    public function getProductUrlRewrites(ProductInterface $product, array $stores): array
    {
        $urls = [];
        $forceGenerateDefault = false;
        foreach ($stores as $storeId) {
            if ($this->needGenerateUrlForStore($product, (int)$storeId)) {
                $urls[] = $this->generateProductStoreUrls($product, (int)$storeId);
            } elseif ((int)$product->getStoreId() !== Store::DEFAULT_STORE_ID) {
                $forceGenerateDefault = true;
            }
        }
        if ($product->getStoreId() === Store::DEFAULT_STORE_ID
            || $this->isProductAssignedToStore($product)) {
            $product->unsUrlPath();
            $product->setUrlPath($this->productUrlPathGenerator->getUrlPath($product));
            $urls[] = $this->productUrlRewriteGenerator->generate($product);
        }
        if ($forceGenerateDefault && $product->getStoreId() !== Store::DEFAULT_STORE_ID) {
            $urls[] = $this->generateProductStoreUrls($product, Store::DEFAULT_STORE_ID);
        }

        return $urls;
    }

    /**
     * Replaces given product URL rewrites
     *
     * @param array $rewrites
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     * @throws UrlAlreadyExistsException
     */
    public function saveProductUrlRewrites(array $rewrites)
    {
        return $this->urlPersist->replace($rewrites);
    }

    /**
     * Generate urls for specific store
     *
     * @param ProductInterface $product
     * @param int $storeId
     * @return array
     */
    private function generateProductStoreUrls(ProductInterface $product, int $storeId): array
    {
        $storeData = $this->getDataByStore->execute($product, $storeId);
        $origStoreId = $product->getStoreId();
        $origVisibility = $product->getVisibility();
        $origUrlKey = $product->getUrlKey();
        $product->setStoreId($storeId);
        $product->setVisibility($storeData['visibility'] ?? Visibility::VISIBILITY_NOT_VISIBLE);
        $product->setUrlKey($storeData['url_key'] ?? '');
        $product->unsUrlPath();
        $product->setUrlPath($this->productUrlPathGenerator->getUrlPath($product));
        $urls = $this->productUrlRewriteGenerator->generate($product);
        $product->setStoreId($origStoreId);
        $product->setVisibility($origVisibility);
        $product->setUrlKey($origUrlKey);

        return $urls;
    }

    /**
     * Does product has scope overridden url key value
     *
     * @param ProductInterface $product
     * @param int $storeId
     * @return bool
     */
    private function needGenerateUrlForStore(ProductInterface $product, int $storeId): bool
    {
        return (int)$product->getStoreId() !== $storeId
            && $this->storeViewService->doesEntityHaveOverriddenUrlKeyForStore(
                $storeId,
                $product->getId(),
                Product::ENTITY
            );
    }

    /**
     * Is product still assigned to store which request is performed from
     *
     * @param ProductInterface $product
     * @return bool
     */
    private function isProductAssignedToStore(ProductInterface $product): bool
    {
        return in_array($product->getStoreId(), $product->getStoreIds());
    }
}
