<?php
namespace App\Frontend\Modules\News;

use \OCFram\BackController;
use \OCFram\Cache;
use \OCFram\HTTPRequest;
use \Entity\Comment;
use \FormBuilder\CommentFormBuilder;
use \OCFram\FormHandler;

class NewsController extends BackController
{
  protected $cache;
    
  public function executeIndex(HTTPRequest $request)
  {
    // Instanciation de la classe Cache
    $cache = new Cache();
        
    $nombreNews = $this->app->config()->get('nombre_news');
    $nombreCaracteres = $this->app->config()->get('nombre_caracteres');
                
    // On ajoute une définition pour le titre.
    $this->page->addVar('title', 'Liste des '.$nombreNews.' dernières news');
    
    /* On regarder si les données sont dans le cache, 
    sinon on récupère toutes news dans la base de données,
    du coup on remplit le fichier avec TOUTES les news,ce qui nous servira pour d'autres vue,
    puis on affiche la quantité correspondante à $nombreNews */
    if($listeNews=$cache->read('datas/news')){
        for($i=0;$i<$nombreNews;$i++){
            $listeNews_[]=$listeNews[$i];
        }
        $listeNews = $listeNews_;
    }
    else{
        $listeNews = $this->managers->getManagerOf('News')->getList();
        $cache->write('datas/news', $listeNews);
        $listeNews = $this->managers->getManagerOf('News')->getList(0, $nombreNews);
    }
     
    //$listeNews = $manager->getList(0, $nombreNews);    
    foreach ($listeNews as $news)
    {
      if (strlen($news->contenu()) > $nombreCaracteres)
      {
        $debut = substr($news->contenu(), 0, $nombreCaracteres);
        $debut = substr($debut, 0, strrpos($debut, ' ')) . '...';
        
        $news->setContenu($debut);
      }
    }
    
    // On ajoute la variable $listeNews à la vue.
    $this->page->addVar('listeNews', $listeNews);
  }
  
  public function executeShow(HTTPRequest $request)
  {
      /*On regarde dans le cache si la news existe, on la récupère 
       * sinon on va lachercher dans la bdd, 
       * on en profite pour mettre à jour le cache*/
      $cache = new Cache();
      if ($listeNews = $cache->read('datas/news')) {
            foreach ($listeNews as $aNews) {
                if ($aNews->id() == $request->getData('id')) {
                    $news = $aNews;
                }
            }
        }
        else{
            $listeNews = $this->managers->getManagerOf('News')->getList();
            $cache->write('datas/news', $listeNews);
            $news = $this->managers->getManagerOf('News')->getUnique($request->getData('id'));
        }
        if (empty($news))
    {
      $this->app->httpResponse()->redirect404();
    }
    
    $this->page->addVar('title', $news->titre());
    $this->page->addVar('news', $news);
    
    //Mise en cache des commentaires si ce n'est pas encore fait
    if($cache->read('datas/commentaires')){
       $coms = $cache->read('datas/commentaires'); 
    }
    else{
        $coms = $this->managers->getManagerOf('Comments')->getAll();
        $cache->write('datas/commentaires', $coms);
    }
    $commentaires=array();
    foreach ($coms as $com){
        if($com->news()==$request->getData('id')){
            $commentaires[]=$com;
        }
    }
    
    $this->page->addVar('comments', $commentaires);
    
  }

  public function executeInsertComment(HTTPRequest $request)
  {
    // Si le formulaire a été envoyé.
    if ($request->method() == 'POST')
    {
      $comment = new Comment([
        'news' => $request->getData('news'),
        'auteur' => $request->postData('auteur'),
        'contenu' => $request->postData('contenu')
      ]);
    }
    else
    {
      $comment = new Comment;
    }

    $formBuilder = new CommentFormBuilder($comment);
    $formBuilder->build();

    $form = $formBuilder->form();

    $formHandler = new FormHandler($form, $this->managers->getManagerOf('Comments'), $request);

    if ($formHandler->process())
    {
      $this->app->user()->setFlash('Le commentaire a bien été ajouté, merci !');
      
      $this->app->httpResponse()->redirect('news-'.$request->getData('news').'.html');
    }

    $this->page->addVar('comment', $comment);
    $this->page->addVar('form', $form->createView());
    $this->page->addVar('title', 'Ajout d\'un commentaire');
    
    //Suppression du cache après un nouveau commentaire
    Cache::clear('datas/commentaires');
  }
}