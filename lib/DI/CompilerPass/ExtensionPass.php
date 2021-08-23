<?php

namespace Proklung\Profilier\DI\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ExtensionPass
 *
 * @since 19.08.2021
 */
class ExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has('router')) {
            $container->getDefinition('twig.extension.routing')->addTag('twig.extension');
        }

        if ($container->has('request_stack')) {
            $container->getDefinition('twig.extension.httpfoundation')->addTag('twig.extension');
        }

        if ($container->getParameter('kernel.debug')) {
            $container->getDefinition('twig.extension.profiler')->addTag('twig.extension');

            // only register if the improved version from DebugBundle is *not* present
            if (!$container->has('twig.extension.dump')) {
                $container->getDefinition('twig.extension.debug')->addTag('twig.extension');
            }
        }

        if ($container->has('web_link.add_link_header_listener')) {
            $container->getDefinition('twig.extension.weblink')->addTag('twig.extension');
        }

        if (class_exists(\Symfony\Component\Stopwatch\Stopwatch::class)) {
            $container->getDefinition('twig.extension.debug.stopwatch')->addTag('twig.extension');
        }

        if ($container->hasDefinition('twig.extension.expression')) {
            $container->getDefinition('twig.extension.expression')->addTag('twig.extension');
        }
    }
}