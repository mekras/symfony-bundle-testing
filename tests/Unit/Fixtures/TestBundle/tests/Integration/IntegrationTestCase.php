<?php

declare(strict_types=1);

namespace Mekras\TestBundle\Tests\Integration;


use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;

/**
 * Класс для проверки {@see BaseSymfonyIntegrationTestCase}
 */
final class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
    public function callProtectedMethod(string $method, ...$arguments)
    {
        return $this->$method(...$arguments);
    }
}
