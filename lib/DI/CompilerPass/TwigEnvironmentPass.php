<?php

namespace Proklung\Profilier\DI\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TwigEnvironmentPass
 */
class TwigEnvironmentPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('twig.instance')) {
            return;
        }

        $definition = $container->getDefinition('twig.instance');

        // Extensions must always be registered before everything else.
        // For instance, global variable definitions must be registered
        // afterward. If not, the globals from the extensions will never
        // be registered.
        $currentMethodCalls = $definition->getMethodCalls();
        $twigBridgeExtensionsMethodCalls = [];
        $othersExtensionsMethodCalls = [];
        foreach ($this->findAndSortTaggedServices('twig.extension', $container) as $extension) {
            $methodCall = ['addExtension', [$extension]];
            $extensionClass = $container->getDefinition((string) $extension)->getClass();

            if (\is_string($extensionClass) && str_starts_with($extensionClass, 'Symfony\Bridge\Twig\Extension')) {
                $twigBridgeExtensionsMethodCalls[] = $methodCall;
            } else {
                $othersExtensionsMethodCalls[] = $methodCall;
            }
        }

        if (!empty($twigBridgeExtensionsMethodCalls) || !empty($othersExtensionsMethodCalls)) {
            $definition->setMethodCalls(array_merge($twigBridgeExtensionsMethodCalls, $othersExtensionsMethodCalls, $currentMethodCalls));
        }
    }
}