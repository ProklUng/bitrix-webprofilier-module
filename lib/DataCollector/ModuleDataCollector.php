<?php

namespace Proklung\Profilier\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

/**
 * Class ModuleDataCollector
 *
 * @since 19.08.2021
 */
class ModuleDataCollector extends DataCollector implements LateDataCollectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
       $this->data = [
            'method' => $request->getMethod(),
       ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return 'module_collector';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    /**
     * @inheritDoc
     */
    public function lateCollect()
    {
        $this->data = $this->cloneVar($this->data);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->data['method'];
    }
}