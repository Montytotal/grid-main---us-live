<?php

declare(strict_types=1);

/**
 * ui-inventory.php
 *
 * Small reflection helper to inspect classes/UI so we can see what
 * existing render components are available and how they are constructed.
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/.env');

/**
 * Try to load project PHP files directly.
 * This avoids depending on Composer/autoload assumptions.
 */
function requirePhpFilesRecursive(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        if ($file->getExtension() !== 'php') {
            continue;
        }

        require_once $file->getPathname();
    }
}

requirePhpFilesRecursive(__DIR__ . '/classes');

function formatType(?ReflectionType $type): string
{
    if ($type === null) {
        return 'mixed';
    }

    if ($type instanceof ReflectionNamedType) {
        $prefix = $type->allowsNull() && !$type->isBuiltin() ? '?' : ($type->allowsNull() ? '?' : '');
        return $prefix . $type->getName();
    }

    if ($type instanceof ReflectionUnionType) {
        $parts = [];
        foreach ($type->getTypes() as $subType) {
            $parts[] = $subType->getName();
        }
        return implode('|', $parts);
    }

    return 'mixed';
}

function formatParameters(array $params): string
{
    $out = [];

    foreach ($params as $param) {
        $piece = '';

        $type = $param->getType();
        if ($type !== null) {
            $piece .= formatType($type) . ' ';
        }

        if ($param->isPassedByReference()) {
            $piece .= '&';
        }

        if ($param->isVariadic()) {
            $piece .= '...';
        }

        $piece .= '$' . $param->getName();

        if ($param->isOptional() && $param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();

            if (is_string($default)) {
                $default = "'" . $default . "'";
            } elseif (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            } elseif ($default === null) {
                $default = 'null';
            } elseif (is_array($default)) {
                $default = '[]';
            }

            $piece .= ' = ' . $default;
        }

        $out[] = $piece;
    }

    return implode(', ', $out);
}

$allClasses = get_declared_classes();
sort($allClasses);

$uiClasses = array_values(array_filter($allClasses, function ($class) {
    return str_contains($class, 'UI') || str_contains($class, '\\UI\\');
}));

echo "=== UI CLASS INVENTORY ===\n\n";

if (!$uiClasses) {
    echo "No UI classes found via reflection.\n";
    exit(0);
}

foreach ($uiClasses as $class) {
    $reflection = new ReflectionClass($class);

    $file = $reflection->getFileName() ?: '(internal)';
    $shortFile = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);

    echo "CLASS: {$class}\n";
    echo "FILE : {$shortFile}\n";

    $parent = $reflection->getParentClass();
    if ($parent) {
        echo "EXTENDS: " . $parent->getName() . "\n";
    }

    $interfaces = $reflection->getInterfaceNames();
    if ($interfaces) {
        echo "IMPLEMENTS: " . implode(', ', $interfaces) . "\n";
    }

    $constructor = $reflection->getConstructor();
    if ($constructor) {
        echo "CTOR : __construct(" . formatParameters($constructor->getParameters()) . ")\n";
    } else {
        echo "CTOR : none\n";
    }

    echo "METHODS:\n";

    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    usort($methods, function (ReflectionMethod $a, ReflectionMethod $b) {
        return strcmp($a->getName(), $b->getName());
    });

    foreach ($methods as $method) {
        if ($method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        $static = $method->isStatic() ? 'static ' : '';
        $returnType = formatType($method->getReturnType());

        echo "  - {$static}{$method->getName()}(" .
            formatParameters($method->getParameters()) .
            "): {$returnType}\n";
    }

    echo "\n";
}