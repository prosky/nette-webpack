<?php

declare(strict_types=1);


namespace Prosky\NetteWebpack;

use Latte\Engine;
use Nette;
use Nette\Schema\Expect;
use Nette\Utils\Strings;
use Nette\DI\CompilerExtension;
use Nette\InvalidArgumentException;

class Extension extends CompilerExtension
{

    protected $provider;

    public function getConfigSchema(): Nette\Schema\Schema
    {
        $params = $this->getContainerBuilder()->parameters;
        return Expect::structure([
            'macro' => Expect::anyOf(false, Expect::string('asset')),
            'debugMode' => Expect::bool($params['debugMode']),
            'wwwDir' => Expect::string($params['wwwDir'])->assert('is_dir'),
            'publicPath' => Expect::string()->nullable(),
            'devServer' => Expect::bool(false),
            'devPort' => Expect::int(8080),
            'manifest' => Expect::string('manifest.json')
        ])->castTo('array');
    }


    public function loadConfiguration(): void
    {
        parent::loadConfiguration();
        $config = array_merge($this->getConfig());
        if (Strings::endsWith($config['publicPath'], '/')) {
            throw new InvalidArgumentException('Please provide public path without ending slash.');
        }
        $builder = $this->getContainerBuilder();
        unset($config['macro']);
        $this->provider = $builder->addDefinition($this->prefix('provider'))
            ->setFactory(PathProvider::class, $config);

    }

    public function beforeCompile(): void
    {
        parent::beforeCompile();
        $config = $this->getConfig();
        if (class_exists(Engine::class)) {
            $builder = $this->getContainerBuilder();
            $factory = $builder->getDefinition('latte.latteFactory');
            if ($factory instanceof Nette\DI\Definitions\FactoryDefinition) {
                $factory->getResultDefinition()
                    ->addSetup('$service->addProvider("assetsPathProvider",?)', [$this->provider]);
            }
            if ($config['macro']) {
                $this->addMacro(Macros::class . '::install', $config['macro']);
            }
        }
    }

    public function addMacro(string $macro, string $name): void
    {
        $builder = $this->getContainerBuilder();
        if (class_exists(Engine::class)) {
            $definition = $builder->getDefinition('latte.latteFactory')->getResultDefinition();
            $definition->addSetup('?->onCompile[] = function ($engine) { ?($engine->getCompiler(),?); }', ['@self', $macro, $name]);
        }
    }

}
