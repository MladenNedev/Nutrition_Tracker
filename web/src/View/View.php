<?php
namespace App\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

class View 
{

    private Environment $twig;

    public function __construct(string $viewsPath, ?string $cachePath = null, bool $debug = false)
    {
        $loader = new FilesystemLoader ($viewsPath);

        $option = ['autoescape' => 'html'];
        if ($cachePath) { $option['cache'] = $cachePath;    }
        if ($debug)     { $option ['debug'] = true;    }

        $this->twig = new Environment($loader, $option);
        if ($debug) { $this->twig->addExtension(new DebugExtension()); }

        $this->twig->addGlobal('app_name', 'Nutrition Tracker');
    }
    
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }

    public function addGlobal(string $key, mixed $value): void
    {
        $this->twig->addGlobal($key,$value);
    }
}