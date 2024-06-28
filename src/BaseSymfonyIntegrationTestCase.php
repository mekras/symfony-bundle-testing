<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting;

use Mekras\Symfony\BundleTesting\CompilerPass\CallbackPass;
use Mekras\Symfony\BundleTesting\DependencyInjection\ContainerExpectations;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Основной класс для тестов интеграции с Symfony
 *
 * @since x.x
 */
abstract class BaseSymfonyIntegrationTestCase extends TestCase
{
    /**
     * Проверяемые контейнеры
     *
     * @var ContainerInterface[]
     */
    private array $containers = [];

    /**
     * Поставщик возможных форматов файлов конфигурации
     *
     * @return array<string, array<string>>
     *
     * @since x.x
     */
    public static function configTypeProvider(): array
    {
        return [
            'PHP' => ['php'],
            'XML' => ['xml'],
            'YAML' => ['yml'],
        ];
    }

    /**
     * Добавляет службы в локатор, чтобы их можно было извлечь после компиляции контейнера
     *
     * Извлечь службу после компиляции можно методом {@see getFromLocator()}.
     *
     * @param array<string>    $serviceIds Добавляемые идентификаторы.
     * @param ContainerBuilder $container  Конструктор контейнера.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function addToLocator(array $serviceIds, ContainerBuilder $container): void
    {
        $references = array_map(
            static fn(string $serviceId): Reference => new Reference($serviceId),
            $serviceIds
        );
        $container->setDefinition(
            'test_locator',
            (new Definition(ServiceLocator::class))
                ->setPublic(true)
                ->addArgument($references)
                ->addTag('container.service_locator')
        );
    }

    /**
     * Создаёт контейнер зависимостей
     *
     * ВНИМАНИЕ! Перед использованием контейнер надо скомпилировать вызвав метод compile().
     *
     * @param array<string> $public Зависимости, которые надо сделать публичными.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function createContainer(array $public = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.build_dir', $this->getTempDir());
        $container->setParameter('kernel.bundles_metadata', []);
        $container->setParameter('kernel.cache_dir', $this->getTempDir());
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('kernel.container_class', 'TestContainer');
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.environment', 'dev');
        $container->setParameter('kernel.logs_dir', $this->getTempDir());
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->setParameter('kernel.root_dir', $this->getTempDir());
        $container->setParameter('kernel.runtime_environment', 'dev');
        $container->setParameter('kernel.runtime_mode.web', true);
        $container->setParameter('kernel.secret', 'test');

        foreach ($this->getRequiredBundles() as $className) {
            $this->registerBundle($container, $className);
        }

        $this->registerBundle($container, $this->getBundleClass());

        $container->addCompilerPass(
            new CallbackPass(
                function (ContainerBuilder $container) use ($public): void {
                    \reset($public);
                    while ($id = \current($public)) {
                        if ($container->hasDefinition($id)) {
                            $definition = $container->getDefinition($id);
                            $definition->setPublic(true);
                        } elseif ($container->hasAlias($id)) {
                            $alias = $container->getAlias($id);
                            $alias->setPublic(true);
                            $public[] = (string) $alias;
                        } else {
                            $this->fail(sprintf('В контейнере нет объявления «%s».', $id));
                        }
                        \next($public);
                    }
                }
            )
        );

        return $container;
    }

    /**
     * Загружает контейнер зависимостей из файла
     *
     * Файлы конфигурации контейнеров можно размещать в любой папке (путь к ней надо передавать в
     * аргументе $configDir). Внутри неё для каждого варианта конфигурации надо создать по 3 файла
     * с расширениями: php, xml и yml.
     *
     * ВНИМАНИЕ! Перед использованием контейнер надо скомпилировать вызвав метод compile().
     *
     * @param string $configDir Путь к папке тестовой конфигурации.
     * @param string $file      Имя файла (баз расширения).
     * @param string $type      Тип файла (xml, yml или php).
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function createContainerFromFile(
        string $configDir,
        string $file,
        string $type
    ): ContainerBuilder {
        $container = $this->createContainer();
        $locator = new FileLocator($configDir);

        switch ($type) {
            case 'xml':
                $loader = new XmlFileLoader($container, $locator);
                break;

            case 'yml':
                $loader = new YamlFileLoader($container, $locator);
                break;

            case 'php':
                $loader = new PhpFileLoader($container, $locator);
                break;

            default:
                throw new \LogicException(
                    sprintf('Неподдерживаемый формат конфигурационных файлов: «%s».', $type)
                );
        }

        $loader->load($file . '.' . $type);

        $this->containers[] = $container;

        return $container;
    }

    /**
     * Задаёт ожидание наличия зависимости в контейнере
     *
     * Метод следует вызывать до ContainerBuilder::compile().
     *
     * @param string           $id               Идентификатор ожидаемой зависимости.
     * @param ContainerBuilder $containerBuilder Конструктор контейнера.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function expectServiceExists(string $id, ContainerBuilder $containerBuilder): void
    {
        if (!$containerBuilder->hasDefinition(ContainerExpectations::class)) {
            $definition = new Definition(ContainerExpectations::class);
            $definition->setPublic(true);
            $definition->setAutowired(false);
            $containerBuilder->setDefinition(ContainerExpectations::class, $definition);
        }

        $definition = $containerBuilder->getDefinition(ContainerExpectations::class);
        $definition->addMethodCall('assertDependencyExists', [new Reference($id)]);
    }

    /**
     * Возвращает имя главного класса проверяемого пакета
     *
     * @throws \LogicException
     *
     * @since x.x
     */
    protected function getBundleClass(): string
    {
        $parts = explode('\\', get_class($this));
        $bundleClassName = $parts[0] . '\\' . $parts[1] . '\\' . $parts[0] . $parts[1];

        if (!\class_exists($bundleClassName)) {
            throw new \LogicException(
                \sprintf(
                    'Не удалось определить имя главного класса проверяемого пакета — класс «%s» не найден.'
                    . ' Вы можете переопределить метод %s чтобы он возвращал правильное имя класса.',
                    $bundleClassName,
                    __METHOD__
                )
            );
        }

        return $bundleClassName;
    }

    /**
     * Извлекает службу из локатора после компиляции контейнера
     *
     * Предварительно, до компиляции, служба должна быть добавлена методом {@see addToLocator()}.
     *
     * @param string             $serviceId Идентификатор службы.
     * @param ContainerInterface $container Скомпилированный контейнер.
     *
     * @return mixed
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function getFromLocator(string $serviceId, ContainerInterface $container)
    {
        $locator = $container->get('test_locator');
        self::assertInstanceOf(ServiceLocator::class, $locator);

        return $locator->get($serviceId);
    }

    /**
     * Возвращает список требуемых пакетов
     *
     * @return array<string> Имена главных классов пакетов, например, «[FooBundle::class]».
     *
     * @since x.x
     */
    protected function getRequiredBundles(): array
    {
        return [FrameworkBundle::class];
    }

    /**
     * Возвращает путь к папке для размещения временных файлов
     *
     * @since x.x
     */
    protected function getTempDir(): string
    {
        return sys_get_temp_dir();
    }

    /**
     * Загружает расширение контейнера
     *
     * @param ContainerBuilder   $container Конструктор контейнера.
     * @param ExtensionInterface $extension Загружаемое расширение.
     * @param array<mixed>       $config    Конфигурация расширения.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function loadExtension(
        ContainerBuilder $container,
        ExtensionInterface $extension,
        array $config = []
    ): void {
        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias(), $config);
    }

    /**
     * Загружает в контейнер произвольный пакет Symfony
     *
     * @param ContainerBuilder $container       Конструктор контейнера.
     * @param string           $bundleClassName Имя класса пакета.
     * @param array<mixed>     $config          Конфигурация расширения.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    protected function registerBundle(
        ContainerBuilder $container,
        string $bundleClassName,
        array $config = []
    ): void {
        $bundle = new $bundleClassName();
        if (!$bundle instanceof BundleInterface) {
            throw new ExpectationFailedException(
                sprintf('Класс «%s» не является пактом Symfony.', $bundleClassName)
            );
        }
        $bundle->build($container);

        $extension = $bundle->getContainerExtension();
        if ($extension !== null) {
            $this->loadExtension($container, $extension, $config);
        }
    }

    /**
     * Очищает окружение после завершения теста
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        foreach ($this->containers as $container) {
            if ($container->has(ContainerExpectations::class)) {
                self::assertNotNull($container->get(ContainerExpectations::class));
            }
        }

        parent::tearDown();
    }
}
