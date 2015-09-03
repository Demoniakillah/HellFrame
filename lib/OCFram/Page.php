<?php
namespace OCFram;

class Page extends ApplicationComponent
{
  protected $contentFile;
  protected $vars = [];

  public function addVar($var, $value)
  {
    if (!is_string($var) || is_numeric($var) || empty($var))
    {
      throw new \InvalidArgumentException('Le nom de la variable doit être une chaine de caractères non nulle');
    }

    $this->vars[$var] = $value;
  }

  public function getGeneratedPage()
  {
    if (!file_exists($this->contentFile))
    {
      throw new \RuntimeException('La vue spécifiée n\'existe pas');
    }

    $user = $this->app->user();

    extract($this->vars);
        
    $cache = new Cache();
    
    ob_start();
    
    require $this->contentFile;
    
    $content = ob_get_clean();
  
    ob_start();
    
    require __DIR__.'/../../App/'.$this->app->name().'/Templates/layout.php';
    
    //Code html stocké dans une variable
    $content = ob_get_clean();
    
    //Ecriture du code html dans un fichier
    $cache->write( $this->contentFile, $content,'views');
    
    return $content;
  }

  public function setContentFile($contentFile)
  {
    if (!is_string($contentFile) || empty($contentFile))
    {
      throw new \InvalidArgumentException('La vue spécifiée est invalide');
    }
    $this->contentFile = $contentFile;
  }
  
  public function getContentFile(){
      return $this->contentFile;
  }
}