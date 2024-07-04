# Инструменты для тестирования пакетов Symfony

Эта библиотека предоставляет инструменты для автоматизированного тестирования пакетов (bundles)
Symfony.

## Оглавление

- [Установка](#установка)
    - [Требования](#требования)
    - [Установка через Composer](#установка-через-composer)
- [Интеграционные тесты](#интеграционные-тесты)
    - [Основной класс интеграционных тестов](#основной-класс-интеграционных-тестов)
    - [Определение имени главного класса вашего пакета](#определение-имени-главного-класса-вашего-пакета)
    - [Написание тестов](#написание-тестов)
    - [Как проверить, что в контейнере есть нужна служба?](#как-проверить-что-в-контейнере-есть-нужна-служба)
    - [Как получить службу из контейнера?](#как-получить-службу-из-контейнера)
    - [Как добавить другие пакеты в контейнер?](#как-добавить-другие-пакеты-в-контейнер)
    - [Как устранить ошибку «service or alias has been removed or inlined»?](#как-устранить-ошибку-service-or-alias-has-been-removed-or-inlined)

## Установка

### Требования

- PHP 7.4+
- Symfony 5.4+

### Установка через Composer

В консоли в корне проекта выполните команду:

    composer require --dev mekras/symfony-bundle-testing

## Интеграционные тесты

Интеграционные тесты позволяют проверить как в действительности будет вести себя ваш код во
взаимодействии с Symfony.

### Основной класс интеграционных тестов

Рекомендуется в каждом проекте создавать отдельный основной класс для всех классов ваших
интеграционных тестов, как единую точку определения конфигурации тестов и добавления общих методов.

Этот класс должен быть унаследован от
[BaseSymfonyIntegrationTestCase](src/BaseSymfonyIntegrationTestCase.php).

Пример — `tests/Integration/IntegrationTestCase.php`:

```php
namespace Acme\MyBundle\Tests\Integration;

use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;

abstract class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
}
```

Все классы интеграционных тестов должны наследоваться либо от этого класса, либо от
`BaseSymfonyIntegrationTestCase`.

### Определение имени главного класса вашего пакета

Важным элементом интеграционного тестирования пакета Symfony является знание его главного класса
(`*Bundle`). За это отвечает метод `BaseSymfonyIntegrationTestCase::getBundleClass()`.
Если вы следуете
[соглашениям по именованию пакетов](https://symfony.com/doc/current/bundles/best_practices.html#bundles-naming-conventions),
то `getBundleClass()` должен правильно определять и возвращать имя класса. Если же он этого не
делает, можете переопределить его в своём `IntegrationTestCase`:

```php
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

Костяк теста выглядит так:

```php
namespace Acme\MyBundle\Tests\Integration;

final class SomeTest extends SymfonyIntegrationTestCase
{
    public function testSomething(): void
    {
        // Создаём тестовый контейнер зависимостей:
        $container = $this->createContainer();
        
        // Здесь можно настраивать контейнер зависимостей и задавать ожидания.
        
        // Компилируем контейнер, чтобы подготовить его к тестам:
        $container->compile();
        
        // Здесь можно писать утверждения.
    }
}
```

Как правило, вначале надо создать контейнер зависимостей, т. к. почти вся интеграция с Symfony
ведётся через него. Метод `$this->createContainer()` создаёт тестовый контейнер (он подробно описан
ниже), который будет являться основной ваших тестов.

Контейнер создаётся с минимально необходимой предварительной конфигурацией:

- устанавливаются важные параметры `kernel.*`;
- регистрируются пакеты, возвращаемые методом `getRequiredBundles()` (см. далее);
- регистрируется тестируемый пакет, определяемый описанным выше методом `getBundleClass()`;
- выполняется ряд других действий.

После создания контейнера, но **до его компиляции** вы можете произвести с ним дополнительные
действия по настройке или задать ожидания, как будет описано ниже.

Далее контейнер необходимо скомпилировать, чтобы привести его в состояние, соответствующее времени
выполнения приложения. Это делается методом `$container->compile()`.

После компиляции можно выполнить нужные действия по проверке получившегося контейнера.

Ниже эти вопросы будут рассмотрены подробнее.

### Тестовый контейнер зависимостей

Сердцем интеграционных тестов является контейнер зависимостей, позволяющий проверить правильность
регистрации в нём расширений вашего пакета, взаимодействие с ядром Symfony и/или другими пакетами.

Для создания тестового контейнера используйте метод
`BaseSymfonyIntegrationTestCase::createContainer`:

```php
final class SomeTest extends SymfonyIntegrationTestCase
{
    public function testSomething(): void
    {
        $container = $this->createContainer();

        // …
    }
}
```

Метод создаст и вернёт новый экземпляр класса
[TestContainerBuilder](src/DependencyInjection/TestContainerBuilder.php), расширяющего стандартный
`ContainerBuilder` Symfony описанными ниже возможностями.

В одном тесте можно создать несколько независимых контейнеров.

#### Загрузка расширений контейнера зависимостей

Метод предназначен для тестирования расширений контейнера зависимостей Symfony и позволяет
проверить, правильно ли эти расширения загружаются, правильно ли настраивают контейнер.

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    $container->loadExtension(new MyExtension());
    $container->compile();

    self::assertTrue($container->hasExtension('my_extension_alias'));
}
```

#### Загрузка в контейнер зависимостей других пакетов

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    $container->registerBundle(MonologBundle::class);
    $container->compile();

    self::assertTrue($container->hasExtension('monolog'));
}
```

### Как проверить, что в контейнере есть нужна служба?

Предположим, ваш пакет должен зарегистрировать в контейнере службы с идентификаторами
`some.service.id` и `щерук.service.id`. Проверить, что это действительно делается, можно так:

```php
public function testFooServiceExists(): void
{
    $container = $this->createContainer();
    $container->expectDefinitionsExists(
      'some.service.id',
      'other.service.id',
      // …
    );
    $container->compile();
    // Если не добавить эту строку, то PHPUnit может ругаться на отсутствие утверждений.
    $this->expectNotToPerformAssertions();
}
```

### Как получить службу из контейнера?

Публичные службы можно получить обычным способом — через метод `Container::get()`:

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    $container->compile();
    
    $someService = $container->get('some.service.id');
    // Ваши проверки…
}
```

Приватные службы можно получать через локатор или сделать публичными.

Получение через локатор:

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    $this->addToLocator(['some.private.service.id'], $container);
    $container->compile();
    
    $someService = $this->getFromLocator('some.private.service.id', $container);
    // Ваши проверки…
}
```

Сделать публичной:

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    $container->makePublic('some.private.service.id');
    $container->compile();
    
    $someService = $container->get('some.private.service.id');
    // Ваши проверки…
}
```

### Как добавить другие пакеты в контейнер?

Если вы хотите протестировать интеграцию с другими пакетами, их можно добавить в тестовый контейнер
с помощью метода `BaseSymfonyIntegrationTestCase::getBundleClass()`.

Для одного теста это можно сделать непосредственно в нём между созданием и компиляцией контейнера:

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    // Добавляет в контейнер MonologBundle:
    $container->registerBundle(MonologBundle::class);
    $container->compile();
    
    // Ваши проверки…
}
```

Если это нужно для всех ваших тестов, то рекомендуется переопределить метод
`createContainer` в основном классе:

```php
namespace Acme\MyBundle\Tests\Integration;

use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
    protected function createContainer(array $public = []): ContainerBuilder
    {
        $container = parent::createContainer($public);

        $container->registerBundle(MonologBundle::class);

        return $container;
    }
}
```

Так же есть возможность сразу задать список пакетов, которые надо загружать, переопределив метод
`getRequiredBundles()`:

```php
namespace Acme\MyBundle\Tests\Integration;

use Mekras\Symfony\BundleTesting\BaseSymfonyIntegrationTestCase;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class IntegrationTestCase extends BaseSymfonyIntegrationTestCase
{
    protected function getRequiredBundles(): array
    {
        return \array_merge(
            parent::getRequiredBundles(),
            [
                MonologBundle::class,
            ]
        );
    }
}
```

### Как устранить ошибку «service or alias has been removed or inlined»?

Если при извлечении из тестового контейнера вы столкнулись с такой ошибкой:

> The "foo" service or alias has been removed or inlined when the container was compiled.
> You should either make it public, or stop using the container directly and use dependency
> injection instead.

то решить её можно сделав эту службу публичной, указав её в аргументе `$public` метода
`createContainer()`:

```php
public function testSomething(): void
{
    $container = $this->createContainer();
    $container->makePublic('some.private.service.id');
    $container->compile();
    
    $someService = $container->get('some.private.service.id');
    // Ваши проверки…
}
```

