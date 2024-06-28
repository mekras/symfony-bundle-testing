<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Проход компилятора контейнера на основе пользовательской функции
 *
 * Позволяет использовать произвольную пользовательскую функции при компиляции контейнера.
 *
 * @since x.x
 */
final class CallbackPass implements CompilerPassInterface
{
    /**
     * Пользовательская функция
     *
     * @var callable
     */
    private $callback;

    /**
     * Создаёт проход компилятора
     *
     * @param callable $callback Пользовательская функция, которую надо применить при компиляции.
     *
     * @since x.x
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Применяет пользовательскую функцию к контейнеру
     *
     * @param ContainerBuilder $container Контейнер зависимостей.
     */
    public function process(ContainerBuilder $container): void
    {
        ($this->callback)($container);
    }
}
