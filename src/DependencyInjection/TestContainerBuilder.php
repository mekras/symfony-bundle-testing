<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting\DependencyInjection;

use Mekras\Symfony\BundleTesting\CompilerPass\CallbackPass;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Тестовый конструктор контейнера зависимостей, предоставляющие дополнительные возможности для
 * тестирования
 *
 * @since x.x
 */
final class TestContainerBuilder extends ContainerBuilder
{
    private string $serviceLocatorId;

    /**
     * @throws \Throwable
     */
    public function __construct(?ParameterBagInterface $parameterBag = null)
    {
        parent::__construct($parameterBag);

        $this->serviceLocatorId = \uniqid('test.service_locator.', true);

        $this->setDefinition(
            $this->serviceLocatorId,
            (new Definition(ServiceLocator::class))
                ->addArgument([])
                ->setPublic(true)
                ->addTag('container.service_locator')
        );
    }

    /**
     * Задаёт ожидание наличия службы или псевдонима в контейнере
     *
     * Метод следует вызывать до ContainerBuilder::compile().
     *
     * @param string ...$ids Идентификаторы служб или псевдонимов, которые должны быть в контейнере.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    public function expectDefinitionsExists(string ...$ids): void
    {
        if (!$this->hasDefinition(ContainerExpectations::class)) {
            $definition = new Definition(ContainerExpectations::class);
            $definition->setPublic(true);
            $definition->setAutowired(false);
            $this->setDefinition(ContainerExpectations::class, $definition);
        }

        $definition = $this->getDefinition(ContainerExpectations::class);
        foreach ($ids as $id) {
            $definition->addMethodCall('assertDependencyExists', [new Reference($id)]);
        }
    }

    /**
     * Загружает в контейнер расширение контейнера
     *
     * Метод предназначен для тестирования расширений контейнера зависимостей Symfony и позволяет
     * проверить, правильно ли эти расширения загружаются, правильно ли настраивают контейнер.
     *
     * @param ExtensionInterface $extension Загружаемое расширение.
     * @param array<mixed>       $config    Конфигурация расширения.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    public function loadExtension(
        ExtensionInterface $extension,
        array $config = []
    ): void {
        $this->registerExtension($extension);
        $this->loadFromExtension($extension->getAlias(), $config);
    }

    /**
     * Извлекает службу после компиляции контейнера
     *
     * Предварительно, до компиляции, служба должна быть добавлена методом {@see makeLocatable()}.
     *
     * @param string $serviceId Идентификатор службы.
     *
     * @return mixed
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    public function locate(string $serviceId)
    {
        $locator = $this->get($this->serviceLocatorId);
        \assert($locator instanceof ServiceLocator);

        return $locator->get($serviceId);
    }

    /**
     * Делает указанные службы доступными для получения в тестах
     *
     * Метод может быть вызван только ДО компиляции контейнера (вызова метода {@see compile()}.
     *
     * @param string ...$ids Идентификаторы служб, которые надо получать в тестах.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    public function makeLocatable(string ...$ids): void
    {
        $newReferences = array_map(
            static fn(string $serviceId): Reference => new Reference($serviceId),
            $ids
        );

        $serviceLocator = $this->getDefinition($this->serviceLocatorId);
        $existedReferences = $serviceLocator->getArgument(0);
        \assert(is_array($existedReferences));
        $serviceLocator->setArgument(0, \array_merge($existedReferences, $newReferences));
    }

    /**
     * Делает указанные службы публичными
     *
     * Метод может быть вызван только ДО компиляции контейнера (вызова метода {@see compile()}.
     *
     * @param string ...$ids Идентификаторы служб, которые надо сделать публичными.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    public function makePublic(string ...$ids): void
    {
        $this->addCompilerPass(
            new CallbackPass(
                function () use ($ids): void {
                    \reset($ids);
                    while ($id = \current($ids)) {
                        if ($this->hasDefinition($id)) {
                            $definition = $this->getDefinition($id);
                            $definition->setPublic(true);
                        } elseif ($this->hasAlias($id)) {
                            $alias = $this->getAlias($id);
                            $alias->setPublic(true);
                            $ids[] = (string) $alias;
                        } else {
                            throw new ServiceNotFoundException(
                                sprintf('В контейнере нет объявления «%s».', $id)
                            );
                        }
                        \next($ids);
                    }
                }
            )
        );
    }

    /**
     * Загружает в контейнер произвольный пакет Symfony
     *
     * @param string       $bundleClassName Имя класса пакета.
     * @param array<mixed> $config          Конфигурация расширения.
     *
     * @throws \Throwable
     *
     * @since x.x
     */
    public function registerBundle(
        string $bundleClassName,
        array $config = []
    ): void {
        $bundle = new $bundleClassName();
        if (!$bundle instanceof BundleInterface) {
            throw new ExpectationFailedException(
                sprintf('Класс «%s» не является пакетом Symfony.', $bundleClassName)
            );
        }
        $bundle->build($this);

        $extension = $bundle->getContainerExtension();
        if ($extension !== null) {
            $this->loadExtension($extension, $config);
        }
    }
}
