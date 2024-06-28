<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting\Tests\Unit\CompilerPass;

use Mekras\Symfony\BundleTesting\CompilerPass\CallbackPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Тесты {@see CallbackPass}
 *
 * @covers \Mekras\Symfony\BundleTesting\CompilerPass\CallbackPass
 */
final class CallbackPassTest extends TestCase
{
    /**
     * Проверяет что функция применяется к контейнеру
     *
     * @throws \Throwable
     */
    public function testApplyCallbackToContainer(): void
    {
        $expectedContainer = $this->createMock(ContainerBuilder::class);

        $pass = new CallbackPass(
            static function (ContainerBuilder $container) use ($expectedContainer): void {
                self::assertSame($expectedContainer, $container);
            }
        );

        $pass->process($expectedContainer);
    }
}
