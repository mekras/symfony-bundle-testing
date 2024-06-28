<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting\DependencyInjection;

/**
 * Вспомогательный класс для задания ожиданий к контейнеру зависимостей в Symfony
 *
 * Внедряется в контейнер, что позволяет связывать его экземпляр с другими службами.
 *
 * @internal
 */
final class ContainerExpectations
{
    /**
     * Позволяет проверять наличие зависимости в контейнере
     *
     * Использование:
     *
     * ```
     * // $definition — объявление SymfonyContainerExpectations.
     * $definition->addMethodCall('assertDependencyExists', [new Reference($id)]);
     * ```
     *
     * @param mixed $dependency Идентификатор ожидаемой зависимости.
     */
    public function assertDependencyExists($dependency): void
    {
        // Никаких действий не требуется. Symfony сама вбросит исключение если зависимости нет.
    }
}
