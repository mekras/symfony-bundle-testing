#!/bin/sh

##
## Запуск мутационных тестов
##

# Позволяет указать через что запускать тесты.
# См. https://infection.github.io/guide/usage.html#Running-Infection
INFECTION_RUNNER=${INFECTION_RUNNER:-php}

# Скрипт для локального запуска мутационного тестирования.
MIN_MSI=80
MIN_COVERED_MSI=80

# Если исходная ветка не задана в аргументе скрипта, используем origin/master
if [ -z "$1" ]; then
  BRANCH=origin/master
else
  BRANCH="$1"
fi

# Запускаем только при наличии фреймворка, установленного через Composer.
FRAMEWORK=vendor/bin/infection

if [ -f "$FRAMEWORK" ]; then
  # Запускаем только на новых и измененных файлах
  CHANGED_FILES=$(git diff "$BRANCH" --diff-filter=AM --name-only | grep src/ | grep .php | paste -sd "," -)
  # И только, если они есть
  if [ -n "$CHANGED_FILES" ]; then
    INFECTION_FILTER="--filter=${CHANGED_FILES} --ignore-msi-with-no-mutations"
    # Добавляем настройки уровня MSI, если они заданы
    if [ -n "$MIN_MSI" ]; then
      MIN_MSI_RESTRICTION=--min-msi="$MIN_MSI"
    fi
    if [ -n "$MIN_COVERED_MSI" ]; then
      MIN_COVERED_MSI_RESTRICTION=--min-covered-msi="$MIN_COVERED_MSI"
    fi

    "${INFECTION_RUNNER}" "$FRAMEWORK" -j$(nproc) "$INFECTION_FILTER $MIN_MSI_RESTRICTION $MIN_COVERED_MSI_RESTRICTION"
  fi
fi
