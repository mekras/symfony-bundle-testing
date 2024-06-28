<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting\Tests\Unit;

use Mekras\TestBundle\MekrasTestBundle;
use Mekras\TestBundle\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\TestCase;

/**
 * Тесты основного класса для тестов интеграции с Symfony
 *
 * @covers \Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase
 */
final class BaseSymfonyIntegrationTestCaseTest extends TestCase
{
    /**
     * Проверяет определение главного класса пакета
     *
     * @throws \Throwable
     */
    public function testGetBundleClass(): void
    {
        $testCase = new IntegrationTestCase();

        $getBundleClass = new \ReflectionMethod($testCase, 'getBundleClass');
        $getBundleClass->setAccessible(true);
        self::assertEquals(
            MekrasTestBundle::class,
            $getBundleClass->invoke($testCase)
        );
    }
}
