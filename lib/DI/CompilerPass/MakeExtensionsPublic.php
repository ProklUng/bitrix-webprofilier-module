<?php

namespace Proklung\Profilier\DI\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MakeExtensionsPublic
 * Сделать все Extensions публичными.
 * @package  Proklung\Profilier\DI\CompilePasses
 *
 * @since 14.08.2021
 */
final class MakeExtensionsPublic implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container) : void
    {
        $this->makePublic($container, 'twig.extension');
    }

    /**
     * @param ContainerBuilder $container Контейнер.
     * @param string           $nameTag   Название тэга.
     *
     * @return void
     */
    private function makePublic(ContainerBuilder $container, string $nameTag) : void
    {
        $taggedServices = $container->findTaggedServiceIds($nameTag);

        foreach ($taggedServices as $id => $service) {
            $def = $container->getDefinition($id);
            $def->setPublic(true);
        }
    }
}
