# История изменений

Формат этого файла соответствует рекомендациям
[Keep a Changelog](https://keepachangelog.com/ru/1.1.0/). Проект использует
[семантическое версионирование](http://semver.org/spec/v2.0.0.html).

## Новое

## 0.3.0 — 2024-07-04

### Изменено

- Объявлены устаревшими:
    - метод `BaseSymfonyIntegrationTestCase::loadExtension()`, вместо него следует использовать
      `TestContainerBuilder::loadExtension()`;
    - метод `BaseSymfonyIntegrationTestCase::registerBundle()`, вместо него следует использовать
      `TestContainerBuilder::registerBundle()`;
    - метод `BaseSymfonyIntegrationTestCase::expectServiceExists()`, вместо него следует
      использовать `TestContainerBuilder::expectDefinitionsExists()`;
    - метод `BaseSymfonyIntegrationTestCase::addToLocator()`, вместо него следует
      использовать `TestContainerBuilder::makeLocatable()`;
    - метод `BaseSymfonyIntegrationTestCase::getFromLocator()`, вместо него следует
      использовать `TestContainerBuilder::locate()`;
    - аргумент `$public` в методе `BaseSymfonyIntegrationTestCase::createContainer()`, вместо него
      следует использовать `TestContainerBuilder::makePublic()`.
- Методы `BaseSymfonyIntegrationTestCase::createContainer()` и
  `BaseSymfonyIntegrationTestCase::createContainerFromFile()` теперь возвращают
  `TestContainerBuilder`.

### Добавлено

- Добавлен класс `TestContainerBuilder`.

## 0.2.0 — 2024-07-01

### Удалено

- Удалена поддержка версий Symfony ниже 5.4.

### Добавлено

- Добавлена поддержка версий Symfony 7.x.

## 0.1.0 — 2024-07-01

### Добавлено

- Класс `BaseSymfonyIntegrationTestCase` — основа для интеграционных тестов пакетов Symfony.
