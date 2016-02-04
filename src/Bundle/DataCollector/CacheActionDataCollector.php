<?php
namespace M6Web\Bundle\CacheExtraBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle datacollector for action cache
 */
class CacheActionDataCollector extends DataCollector
{
    private $cacheActionListener = null;

    /**
     * Construct the data collector, with the cacheActionListener
     * @param CacheActionListener $cacheActionListener The cacheActionListener to retrieve data from
     */
    public function __construct($cacheActionListener)
    {
        $this->cacheActionListener = $cacheActionListener;
    }

    /**
     * Collect the data
     * @param Request    $request   The request object
     * @param Response   $response  The response object
     * @param \Exception $exception An exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'blocks' => null,
            'total'  => null,
            'hits'   => null,
            'miss'   => null,
        ];

        if (!is_null($this->cacheActionListener)) {
            $blocks = $this->cacheActionListener->getCachedBlocks();
            $total  = 0;
            $nbHits = 0;
            $nbMiss = 0;

            foreach ($blocks as $cached) {
                $total ++;
                $cached ? $nbHits++ : $nbMiss++;
            }

            $this->data = [
                'blocks' => $blocks,
                'total'  => $total,
                'hits'   => $nbHits,
                'miss'   => $nbMiss
            ];
        }
    }

    /**
     * Return the blocks list
     * @return array List of blocks, with controller name as key and cached as value
     */
    public function getBlocks()
    {
        return $this->data['blocks'];
    }

    /**
     * Return the number of cache hits
     * @return integer Number of cache hits
     */
    public function getHits()
    {
        return $this->data['hits'];
    }

    /**
     * Return the number of cache miss
     * @return integer Number of cache miss
     */
    public function getMiss()
    {
        return $this->data['miss'];
    }

    /**
     * Return the number total number of cached blocks
     * @return integer Number of blocks
     */
    public function getTotal()
    {
        return $this->data['total'];
    }

    /**
     * Return the name of the collector
     * @return string data collector name
     */
    public function getName()
    {
        return 'cache_action';
    }
}
