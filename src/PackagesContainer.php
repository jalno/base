<?php

namespace packages\base;


class PackagesContainer
{
    /**
     * @var Package[]
     */
    private array $items = [];

    /**
     * Register a new package.
     */
    public function register(Package $package): void
    {
        $this->items[$package->getName()] = $package;
    }

    /**
     * Return package by search its name.
     */
    public function package(string $name): ?Package
    {
        return $this->items[$name] ?? null;
    }

    /**
     * Get list of active packages.
     *
     * @param string[] $names
     *
     * @return Package[]
     */
    public function get(array $names = []): array
    {
        if (empty($names)) {
            return $this->items;
        }
        $return = [];
        foreach ($this->items as $name => $package) {
            if (in_array($name, $names)) {
                $return[] = $package;
            }
        }

        return $return;
    }

    /**
     * @return array<string,string> array of path to jalno manifests
     */
    private function findFromComposer(): array
    {
        $basePath = app()->basePath();

        $result = [];
        $composerFile = $basePath . "/composer.json";

        if (is_file($composerFile)) {
            $rootComposer = json_decode(file_get_contents($composerFile), true);
            if (isset($rootComposer['extra']['jalno']['manifest'])) {
                $name = explode("/", $rootComposer['name'], 2);
                $name = $name[1];
                $result[$name] = $basePath . DIRECTORY_SEPARATOR . $rootComposer['extra']['jalno']['manifest'];
            }
        }

        $installedJsonFile = $basePath . "/vendor/composer/installed.json";
        if (is_file($installedJsonFile)) {
            $installed = json_decode(file_get_contents($installedJsonFile), true);

            foreach ($installed['packages'] as $package) {
                if (isset($package['extra']['jalno']['manifest'])) {
                    $name = explode("/", $package['name'], 2);
                    $name = $name[1];
                    if (isset($result[$name])) {
                        throw new Exception("Duplicate package named '{$name}'");
                    }
                    $result[$name] = $basePath . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . $package['name'] . DIRECTORY_SEPARATOR . $package['extra']['jalno']['manifest'];
                }
            }
        }

        return $result;
    }

    public function registerFromComposer(): static
    {
        $app = app();
        $manifests = $this->findFromComposer();

        foreach ($manifests as $name => $manifest) {
            $package = Package::fromManifest($app, $name, $manifest);

            $this->items[$name] = $package;
            app()->register($package, true);
        }

        return $this;
    }

    public function registerTranslates(string $code): void
    {
        foreach ($this->items as $package) {
            $package->registerTranslates($code);
        }
    }

    public function loadDynamicStorages(): void
    {
        foreach ($this->items as $package) {
            $package->loadDynamicStorages();
        }
    }
}
