<?php

namespace Proklung\Profilier\DI;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Configuration;
use Prokl\WebProfilierBundle\Events\BitrixAddButtonMenu;
use Prokl\WebProfilierBundle\Events\BitrixAfterHandleRequestListener;
use Prokl\WebProfilierBundle\Events\BitrixOnBeforePrologHandler;
use Prokl\WebProfilierBundle\Events\DataCollectingEventHandler;
use Prokl\BitrixSymfonyRouterBundle\Event\AfterHandleRequestEvent;
use Prokl\GuzzleBundle\DataCollector\GuzzleCollector;
use ProklUng\ContainerBoilerplate\DI\AbstractServiceContainer;
use Exception;
use ProklUng\ContainerBoilerplate\DI\LoaderBundles;
use Prokl\WebProfilierBundle\Utils\ExternalDataCollectorsBag;
use Proklung\Profilier\DI\CompilerPass\ExtensionPass;
use Proklung\Profilier\DI\CompilerPass\MakeExtensionsPublic;
use Proklung\Profilier\DI\CompilerPass\TwigEnvironmentPass;
use Proklung\Profilier\Utils\OptionsManager;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\ProfilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig_Environment;

/**
 * Class Services
 * @package Proklung\Notifier\DI
 *
 * @since 27.07.2021
 */
class Services extends AbstractServiceContainer
{
    /**
     * @var ContainerBuilder|null $container Контейнер.
     */
    protected static $container;

    /**
     * @var array $config Битриксовая конфигурация.
     */
    protected $config = [];

    /**
     * @var array $parameters Параметры битриксового сервис-локатора.
     */
    protected $parameters = [];

    /**
     * @var array $services Сервисы битриксового сервис-локатора.
     */
    protected $services = [];

    /**
     * @var array $bundles Бандлы.
     */
    protected $bundles = [];

    /**
     * @var string $moduleId ID модуля (переопределяется наследником).
     */
    protected $moduleId = 'proklung.profilier';

    /**
     * @var array $twigConfig Конфигурация Твига.
     */
    private $twigConfig;

    /**
     * @var boolean $loaded Загружен или нет.
     */
    private static $loaded = false;

    /**
     * @var array $collectors Коллекторы из конфига .settings.php.
     */
    private $collectors;

    /**
     * @var array $transformers Трансформеры.
     */
    private $transformers;

    /**
     * Services constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->config = Configuration::getInstance()->get($this->moduleId) ?? ['proklung.profilier' => []];

        $this->services = $this->config['services'] ?? [];
        $this->twigConfig = $this->config['twig'] ?? [];
        $this->bundles = $this->config['bundles'] ?? [];
        $this->collectors = $this->config['collectors'] ?? [];
        $this->transformers = $this->config['transformers'] ?? [];

        // Инициализация параметров контейнера.
        $this->parameters['cache_path'] = $this->config['parameters']['cache_path'] ?? '/bitrix/cache/proklung.profilier';
        $this->parameters['container.dumper.inline_factories'] = $this->config['parameters']['container.dumper.inline_factories'] ?? false;
        $this->parameters['compile_container_envs'] = (array)$this->config['parameters']['compile_container_envs'];

        unset(
            $this->config['parameters'],
            $this->config['services'],
        );
    }

    /**
     * @return void
     * @throws ArgumentNullException | Exception
     */
    public static function init(): void
    {
        if (static::$loaded) {
            return;
        }

        $enabled = OptionsManager::option('profiler_enabled');
        if (!$enabled || !$_SERVER['REQUEST_URI']) {
            return;
        }

        $request = Request::createFromGlobals();
        $container = static::boot();
        /** @var RequestStack */
        $container->get('request_stack')->push($request);

        $ignoringUrls = $container->getParameter('ignoring_url');
        $url = $request->getUri();
        foreach ($ignoringUrls as $ignoringUrl) {
            if (stripos($url, $ignoringUrl) !== false) {
                static::$loaded = true;

                return;
            }
        }

        $listenerCustomEvent = $container->get(BitrixAfterHandleRequestListener::class);
        $listenerProfiling = $container->get(DataCollectingEventHandler::class);
        $addButtonToPanel = $container->get(BitrixAddButtonMenu::class);
        $checkerAdmin = $container->get(BitrixOnBeforePrologHandler::class);

        AddEventHandler('main', 'OnAfterEpilog', [$listenerProfiling, 'handle']);
        AddEventHandler('main', 'OnBeforeProlog', [$addButtonToPanel, 'handle']);
        AddEventHandler('main', 'OnBeforeProlog', [$checkerAdmin, 'handle']);
        AddEventHandler('main', 'OnBeforeProlog', [$addButtonToPanel, 'handle']);

        // Эмуляция события по выставлению заголовков.
        $response = new Response();
        $response->headers->set('X-Debug-Token', 'module_token');
        $event = new AfterHandleRequestEvent($request, $response);

        $listenerCustomEvent->handle($event);

        AddEventHandler('', 'OnAfterDataCollectorDone', [static::class, 'handlerGuzzleHandler']);

        static::$loaded = true;
    }

    /**
     * @param DataCollector $dataCollector
     */
    public static function handlerGuzzleHandler(DataCollector $dataCollector)
    {
        $dataCollectorBag = new ExternalDataCollectorsBag();
        $dataCollectorBag->add('guzzle', $dataCollector);
    }

    /**
     * Инициализация контейнера.
     *
     * @return void
     * @throws Exception
     */
    public function initContainer(): void
    {
        static::$container->setParameter('mailer_enabled', false);
        static::$container->setParameter('kernel.container_class', 'ProfilierContainer');

        static::$container->setParameter('data_collector.templates', []);
        static::$container->setParameter('cache_path', $_SERVER['DOCUMENT_ROOT'].$this->parameters['cache_path']);
        static::$container->setParameter(
            'profiler_cache_path',
            $_SERVER['DOCUMENT_ROOT'].$this->parameters['cache_path'].'/module_profilier'
        );

        $loaderBundles = new LoaderBundles(
            static::$container,
            $this->environment
        );

        $loaderBundles->fromArray($this->bundles);

        $loaderYaml = new YamlFileLoader(static::$container, new FileLocator(__DIR__.'/../../configs'));
        $loader = new PhpFileLoader(static::$container, new FileLocator(__DIR__.'/../../configs'));

        $loaderYaml->load('base.yaml');
        $loader->load('services.php');
        $loaderYaml->load('services.yaml');
        $loaderYaml->load('transformers.yaml');
        $loaderYaml->load('data_collectors.yaml');

        // GuzzleBundle не установлен
        if (!class_exists(GuzzleCollector::class)) {
            static::$container->removeDefinition('csa_guzzle.data_collector.guzzle');
            static::$container->removeDefinition('csa_guzzle.data_collector.history_bag');
        }

        if (class_exists(Twig_Environment::class)
            ||
            class_exists(\Twig\Environment::class)
        ) {
            $loaderYaml->load('twig.yaml');

            static::$container->setParameter('twig_paths', $this->twigConfig['paths']);
            static::$container->setParameter('twig_cache_dir', $this->twigConfig['cache_dir']);
            static::$container->setParameter('twig_config', $this->twigConfig['config']);
        }

        if (class_exists(Stopwatch::class)) {
            static::$container->register('debug.stopwatch', Stopwatch::class)
                ->addArgument(true)
                ->addTag('kernel.reset', ['method' => 'reset']);
            static::$container->setAlias(Stopwatch::class, new Alias('debug.stopwatch', false));
        }

        // Регистрация сервисов data collector
        $this->processConfigServices(
            static::$container,
            'data_collector',
            $this->collectors,
            function ($item) {
                return [
                    'id' => $item['string'],
                    'template' => $item['template'],
                    'priority' => $item['priority'],
                ];
            }
        );

        // Регистрация сервисов data collector transformer
        $this->processConfigServices(
            static::$container,
            'web_profiler.transformer',
            $this->transformers,
            function ($item) {
                return [
                    'key' => $item['key'],
                ];
            }
        );

        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->setHotPathEvents([
            KernelEvents::REQUEST,
            KernelEvents::CONTROLLER,
            KernelEvents::CONTROLLER_ARGUMENTS,
            KernelEvents::RESPONSE,
            KernelEvents::FINISH_REQUEST,
        ]);

        static::$container->addCompilerPass($registerListenersPass);

        $this->setupAutowiring(static::$container);

        $this->build(static::$container);

        static::$container->addCompilerPass(new ProfilerPass());
        static::$container->addCompilerPass(new ExtensionPass());
        static::$container->addCompilerPass(new MakeExtensionsPublic());
        static::$container->addCompilerPass(new TwigEnvironmentPass());

        static::$container->compile(true);
    }

    private function processConfigServices(
        ContainerBuilder $containerBuilder,
        string $tag,
        array $config,
        callable $processor
    ) : void
    {
        if (count($config) === 0) {
            return;
        }

        foreach ($config as $serviceName => $item) {
            if (is_string($item['className'])) {
                // Если такой сервис уже есть - игнор.
                if ($containerBuilder->hasDefinition($serviceName)) {
                    continue;
                }

                $definition = $containerBuilder->setDefinition(
                    $serviceName,
                    new Definition($item['className'])
                )->setPublic(true);

                $definition->addTag($tag, $processor($item));
            }

            if (is_callable($item['className'])) {
                $definition = $containerBuilder->register($serviceName, FactoryClosure::class);
                $definition->setFactory([FactoryClosure::class, 'from']);
                $definition->addArgument($item['className']);
                $definition->setPublic(true);
            }
        }
    }

    /**
     * Autowiring.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function setupAutowiring(ContainerBuilder $container): void
    {
    }

    /**
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function build(ContainerBuilder $container): void
    {
    }
}
