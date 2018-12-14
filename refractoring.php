<?php


use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class DataProvider
{
    //TODO:
    // по принципу solid нужен protected
    private $host;
    private $user;
    private $password;

    //TODO:
    // тут не указаны типы в phpdoc
    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        // returns a response from external service
        return [
            'content' => 'hello text ' . implode(' ', $request),
            'html'    => 'hello html ' . implode(' ', $request),
        ];
    }
}

//TODO:
// из этого кода не понятен "потаенный" смысл декоратора,
// тут просто происходит расширение класса наследованием
// по принципу "разделяй и властвуй" необходимо убрать наименование DecoratorManager
// и разделить его 2 отдельных класса LogManager и CacheManager

class DecoratorManager extends DataProvider
{
    //TODO:
    // нет phpdoc и раз уж тут зависимость передается с наружи, не создается, что уже не плохо,
    // через параметр метода или конструктора, то сделать эти пля private
    public $cache;
    public $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        //TODO:
        // лучше вынести в setCache(CacheItemPoolInterface $cache)
        $this->cache = $cache;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $input
     * @return array|mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );

            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error');
            //TODO:
            // тут нужно пробросить ошибку дальше
        }

        return [];
    }

    /**
     * @param array $input
     * @return false|string
     */
    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }

}
