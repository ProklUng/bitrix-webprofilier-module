<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bridge\Twig\DataCollector\TwigDataCollector;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Symfony\Bridge\Twig\Extension\ExpressionExtension;
use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\StopwatchExtension;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\DataCollector\ConfigDataCollector;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Extension\DebugExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('profiler', Profiler::class)
        ->public()
        ->args([service('profiler.storage'), service('logger')->nullOnInvalid()])
        ->tag('monolog.logger', ['channel' => 'profiler'])

        ->set('profiler.storage', FileProfilerStorage::class)
        ->args([param('profiler.storage.dsn')])

        ->set('profiler_listener', ProfilerListener::class)
        ->args([
            service('profiler'),
            service('request_stack'),
            null,
            param('profiler_listener.only_exceptions'),
            param('profiler_listener.only_master_requests'),
        ])
        ->tag('kernel.event_subscriber')

        ->set('data_collector.config', ConfigDataCollector::class)
        ->call('setKernel', [service('kernel')->ignoreOnInvalid()])
        ->tag('data_collector', ['template' => '@WebProfiler/Collector/config.html.twig', 'id' => 'config', 'priority' => -255])

        ->set('data_collector.config', ConfigDataCollector::class)
        ->call('setKernel', [service('kernel')->ignoreOnInvalid()])
        ->tag('data_collector', ['template' => '@WebProfiler/Collector/config.html.twig', 'id' => 'config', 'priority' => -255])

        ->set('data_collector.twig', TwigDataCollector::class)
        ->args([service('twig.profile'), service('twig.instance')])
        ->tag('data_collector', ['template' => '@WebProfiler/Collector/twig.html.twig', 'id' => 'twig', 'priority' => 257])

        ->set('data_collector.request', RequestDataCollector::class)
        ->public()
        ->args([
            service('request_stack')->ignoreOnInvalid(),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('data_collector', ['template' => '@WebProfiler/Collector/request.html.twig', 'id' => 'request', 'priority' => 335])

        ->set('data_collector.request.session_collector', \Closure::class)
        ->factory([\Closure::class, 'fromCallable'])
        ->args([[service('data_collector.request'), 'collectSessionUsage']])

        ->set('data_collector.exception', ExceptionDataCollector::class)
        ->tag('data_collector', ['template' => '@WebProfiler/Collector/exception.html.twig', 'id' => 'exception', 'priority' => 305])

        ->set('twig.extension.debug.stopwatch', StopwatchExtension::class)
        ->args([service('debug.stopwatch')->ignoreOnInvalid(), param('kernel.debug')])

        ->set('twig.extension.expression', ExpressionExtension::class)

        ->set('twig.extension.httpkernel', HttpKernelExtension::class)

        ->set('twig.extension.debug', DebugExtension::class)

        ->set('twig.extension.httpfoundation', HttpFoundationExtension::class)
        ->args([service('url_helper')])

        ->set('twig.extension.profiler', ProfilerExtension::class)
        ->args([service('twig.profile'), service('debug.stopwatch')->ignoreOnInvalid()])

        ->set('twig.profile', Profile::class)

        ->set('url_helper', UrlHelper::class)
        ->args([
            service('request_stack'),
            service('router.request_context')->ignoreOnInvalid(),
        ])
        ->alias(UrlHelper::class, 'url_helper')

        ->set('twig.extension.code', CodeExtension::class)
        ->public()
        ->args([service('debug.file_link_formatter')->ignoreOnInvalid(), param('kernel.project_dir'), param('kernel.charset')])
        ->tag('twig.extension')

        ->set('twig.extension.routing', RoutingExtension::class)
        ->args([service('router')])
    ;
};
