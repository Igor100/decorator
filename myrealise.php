<?php


use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

// раз уж программист упомянул, а может хотел сделать в виде декоратора, то вот мой примерный вариант
// это для примера, в одном файле не следует мешать все классы
interface Provider
{
    public function getResponse(): string;
}

abstract class ProviderDecorator implements Provider
{
    protected $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }
}

class SomeProvider implements Provider
{
    private function getDataFromProvider()
    {
        // returns a response from external service
        return '';
    }

    public function getResponse(): string
    {
        try {
            $result = $this->getDataFromProvider();
        } catch (Throwable $e) {
            throw $e;
        }
        return $result;
    }
}


class WithLogger extends ProviderDecorator
{
    private $logger;

    public function setLogger( $logger) //LoggerInterface
    {
        $this->logger = $logger;
    }

    public function getResponse(): string
    {
        try {
            return $this->provider->getResponse();
        } catch (Throwable $e) {
            if (isset($this->logger)) {
                $this->logger->critical('Error');
            }
            throw $e;
        }
    }
}

class WithCacher extends ProviderDecorator
{
    public $input;

    private $cacher;

    public function setCacher( $cacher) //CacheItemPoolInterface
    {
        $this->cacher = $cacher;
    }

    public function getResponse(): string
    {
        if (!isset($this->cacher)) {
            return $this->provider->getResponse();
        }
        try {
            $cacheKey = $this->getCacheKey($this->input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
            $result = $this->provider->getResponse();
            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );
            return $result;
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
        //testing
        $cacher = null;
        $logger = null;
        $provider = new SomeProvider();

        $provider = new WithCacher($provider);
        $provider->setCacher($cacher);

        $provider = new WithLogger($provider);
        $provider->setLogger($logger);

        $provider->getResponse();
