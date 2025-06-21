<?php
namespace Vendor\CustomOrderProcessing\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class RateLimiter
{
    const CACHE_PREFIX = 'customorderprocessing_rate_limit_';
    const REQUEST_LIMIT = 10; // Number of requests allowed
    const PERIOD = 60;         // In seconds (1 minute)

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        CacheInterface $cache,
        ScopeConfigInterface $scopeConfig
        )
    {
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns true if request is rate limited for the given identifier
     * @return bool
     */
    public function checkRateLimit()
    {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheKey = self::CACHE_PREFIX . md5($identifier);
        $raw = $this->cache->load($cacheKey);
        $data = $raw ? json_decode($raw, true) : ['count' => 0, 'start' => time()];

        $period = $this->scopeConfig->getValue(
            'customorderprocessing_api/general/period',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$period) {
            $period = self::PERIOD;
        }
        $requestLimit = $this->scopeConfig->getValue(
            'customorderprocessing_api/general/request_limit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$requestLimit) {
            $requestLimit = self::REQUEST_LIMIT;
        }

        $now = time();

        if ($now - $data['start'] > $period) {
            // Reset window
            $data = ['count' => 1, 'start' => $now];
        } else {
            $data['count']++;
        }

        $this->cache->save(json_encode($data), $cacheKey, [], $period + 5);

        return $data['count'] > $requestLimit;
    }
}
