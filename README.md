# Инструменты для тестирования кода для Symfony

## Оглавление

- [Установка](#установка)
  - [Требования](#требования)
  - [Установка через Composer](#установка-через-composer)
- [Использование](#использование)
  - [Определение имени главного класса вашего пакета](#определение-имени-главного-класса-вашего-пакета)
  - [Написание тестов](#написание-тестов)

## Установка

### Требования

- PHP 7.4+

### Установка через Composer

В консоли в корне проекта выполните команду:

    composer require --dev mekras/symfony-bundle-testing

## Использование

Рекомендуется создавать отдельный основной класс для всех классов ваших интеграционных тестов, как
единую точку определения конфигурации тестов и добавления общих методов.

Этот класс должен быть унаследован от 
[BaseSymfonyIntegrationTestCase](src/BaseSymfonyIntegrationTestCase.php).

Пример — `tests/Integration/IntegrationTestCase.php`:

```php
<?php
namespace Acme\MyBundle\Tests\Integration;

use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;

abstract class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
}
```

### Определение имени главного класса вашего пакета

Метод `BaseSymfonyIntegrationTestCase::getBundleClass()` отвечает за определение имени главного
класса вашего пакета (`*Bundle`). Если вы следуете
[соглашениям по именованию пакетов](https://symfony.com/doc/current/bundles/best_practices.html#bundles-naming-conventions),
то `getBundleClass()` должен правильно определять и возвращать имя класса. Если же он этого не
делает, можете переопределить его в своём `IntegrationTestCase`:

```php
<?php
namespace Acme\MyBundle\Tests\Integration;

use Acme\MyBundle\AcmeMyBundle;
use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;

abstract class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
    protected function getBundleClass(): string
    {
        return MyBundle::class;
    }
}
```

### Написание тестов

```php
<?php
namespace Acme\MyBundle\Tests\Integration;

final class SomeTest extends SymfonyIntegrationTestCase
{
    public function testSomething(): void
    {
        // Создаём тестовый контейнер зависимостей:
        $container = $this->createContainer();
        
        // Компилируем контейнер, чтобы подготовить его к тестам:
        $container->compile();
        
        // Ваши проверки…
    }
}
```

#### Добавление других пакетов в контейнер

Если вы хотите протестировать интеграцию с другими пакетами, их можно добавить в тестовый контейнер
с помощью метода `BaseSymfonyIntegrationTestCase::getBundleClass()`.

Для одного теста это можно сделать непосредственно в нём между созданием и компиляцией контейнера:

```php
<?php
namespace Acme\MyBundle\Tests\Integration;

use Symfony\Bundle\MonologBundle\MonologBundle;

final class SomeTest extends SymfonyIntegrationTestCase
{
    public function testSomething(): void
    {
        $container = $this->createContainer();
        // Добавляет в контейнер MonologBundle:
        $this->registerBundle($container, MonologBundle::class);
        $container->compile();
        
        // Ваши проверки…
    }
}
```

Для всех тестов рекомендуется переопределять метод `createContainer` в основном классе:

```php
<?php
namespace Acme\MyBundle\Tests\Integration;

use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
    protected function createContainer(array $public = []): ContainerBuilder
    {
        $container = parent::createContainer($public);

        $this->registerBundle($container, MonologBundle::class);

        return $container;
    }
}
```

#### Ошибка «service or alias has been removed or inlined»

Если при извлечении из тестового контейнера вы столкнулись с такой ошибкой:

> The "foo" service or alias has been removed or inlined when the container was compiled.
> You should either make it public, or stop using the container directly and use dependency
> injection instead.
 
то решить её можно сделав эту службу публичной, указав её в аргументе `$public` метода
`createContainer()`:

