<?php
namespace MightyCore;
class TWIG {

    // FOR EXTENDING TWIG
    private $twig;
    public function __construct($file, $args){
        $loader = new \Twig\Loader\FilesystemLoader(VIEW_PATH);
        $this->twig = new \Twig\Environment($loader, [
            'cache' => '/path/to/compilation_cache',
        ]);
    
        $file = $file.'.html';
        return $this->twig->render($file, $args);
    }
}
