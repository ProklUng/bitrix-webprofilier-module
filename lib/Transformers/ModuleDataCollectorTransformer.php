<?php

namespace Proklung\Profilier\Transformers;

use Proklung\Profilier\DataCollector\ModuleDataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Prokl\WebProfilierBundle\Contract\DataCollectorTransformerInterface;

/**
 * Class ModuleDataCollectorTransformer
 * @package Prokl\WebProfilierBundle\Transformers
 *
 * @since 19.08.2021
 */
class ModuleDataCollectorTransformer implements DataCollectorTransformerInterface
{
    /**
     * @inheritDoc
     * @param ModuleDataCollector $dataCollector Data collector.
     */
    public function transform($dataCollector) : array
    {
        return [
            'collector' => $dataCollector,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTemplate() : string
    {
        return '/collectors/module.html.twig';
    }

    /**
     * @inheritDoc
     */
    public static function support(DataCollector $dataCollector) : bool
    {
        return is_a($dataCollector, ModuleDataCollector::class);
    }
}
