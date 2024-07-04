<?php

declare(strict_types=1);

namespace Mekras\Symfony\BundleTesting\Tests\Integration\DependencyInjection;

use Mekras\Symfony\BundleTesting\DependencyInjection\TestContainerBuilder;
use Mekras\TestBundle\DependencyInjection\MekrasTestExtension;
use Mekras\TestBundle\MekrasTestBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @covers \Mekras\Symfony\BundleTesting\DependencyInjection\TestContainerBuilder
 */
final class TestContainerBuilderTest extends TestCase
{
    /**
     * @throws \Throwable
     */
    public function testAllExpectedDefinitionsExists(): void
    {
        $container = new TestContainerBuilder();
        $container->register('foo', \stdClass::class);
        $container->register('bar', \stdClass::class);
        $container->setAlias('baz', 'bar');
        $container->expectDefinitionsExists('foo', 'baz');
        $container->compile();

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws \Throwable
     */
    public function testLocate(): void
    {
        $container = new TestContainerBuilder();
        $container->register('foo', \stdClass::class);
        $container->makeLocatable('foo');
        $container->compile();

        self::assertNotNull($container->locate('foo'));
    }

    /**
     * @throws \Throwable
     */
    public function testExpectedDefinitionMissing(): void
    {
        $container = new TestContainerBuilder();
        $container->expectDefinitionsExists('foo');
        try {
            $container->compile();
            self::fail('Ожидание отсутствия объявления не проверено.');
        } catch (ServiceNotFoundException $exception) {
            $this->expectNotToPerformAssertions();
        }
    }

    /**
     * @throws \Throwable
     */
    public function testLoadExtension(): void
    {
        $container = new TestContainerBuilder();

        $extension = new MekrasTestExtension();
        $container->loadExtension($extension);

        $container->compile();

        self::assertTrue($container->hasExtension($extension->getAlias()));
    }

    /**
     * @throws \Throwable
     */
    public function testMakeAliasPublic(): void
    {
        $container = new TestContainerBuilder();
        $container->register('foo', \stdClass::class);
        $container->setAlias('bar', 'foo');
        $container->makePublic('bar');
        $container->compile();
        self::assertTrue($container->has('bar'));
    }

    /**
     * @throws \Throwable
     */
    public function testMakeServicePublic(): void
    {
        $container = new TestContainerBuilder();
        $container->register('foo', \stdClass::class);
        $container->makePublic('foo');
        $container->compile();
        self::assertTrue($container->has('foo'));
    }

    /**
     * @throws \Throwable
     */
    public function testRegisterBundle(): void
    {
        $container = new TestContainerBuilder();
        $container->registerBundle(MekrasTestBundle::class);
        $container->compile();

        self::assertTrue($container->hasExtension('mekras_test'));
    }
}
