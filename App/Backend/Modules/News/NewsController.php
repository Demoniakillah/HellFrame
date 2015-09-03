<?php
namespace App\Backend\Modules\News;

use \OCFram\BackController;
use \OCFram\HTTPRequest;
use \Entity\News;
use \Entity\Comment;
use \FormBuilder\CommentFormBuilder;
use \FormBuilder\NewsFormBuilder;
use \OCFram\FormHandler;
use OCFram\Cache;

class NewsController extends BackController
{
  public function executeDelete(HTTPRequest $request)
  {
    $newsId = $request->getData('id');
    
    $this->managers->getManagerOf('News')->delete($newsId);
    $this->managers->getManagerOf('Comments')->deleteFromNews($newsId);

    $this->app->user()->setFlash('La news a bien été supprimée !');
    
    //Suppression du cache des news
    Cache::clear('datas/news');

    $this->app->httpResponse()->redirect('.');
  }

  public function executeDeleteComment(HTTPRequest $request)
  {
    //Suppression du cache des commentaires
    Cache::clear('datas/commentaires');
      
    $this->managers->getManagerOf('Comments')->delete($request->getData('id'));
    
    $this->app->user()->setFlash('Le commentaire a bien été supprimé !');
    
    $this->app->httpResponse()->redirect('.');
  }

  public function executeIndex(HTTPRequest $request)
  {
    $this->page->addVar('title', 'Gestion des news');

    $manager = $this->managers->getManagerOf('News');
    $cache = new Cache();
    if($listNews=$cache->read('datas/news')){
        $this->page->addVar('listeNews', $listNews);
    }
    else{
        $this->page->addVar('listeNews', $manager->getList());
        $cache->write('datas/news', $listNews);
    }
    $listNews = $this->page->addVar('nombreNews', $manager->count());
    
    
  }

  public function executeInsert(HTTPRequest $request)
  {
    //Suppression du cache des news
    Cache::clear('datas/news');
      
    $this->processForm($request);

    $this->page->addVar('title', 'Ajout d\'une news');
    
  }

  public function executeUpdate(HTTPRequest $request)
  {
    //Suppression du cache des news
    Cache::clear('datas/news'); 
    
    $this->processForm($request);

    $this->page->addVar('title', 'Modification d\'une news');
  }

  public function executeUpdateComment(HTTPRequest $request)
  {
    //Suppression du cache des commentaires
    Cache::clear('datas/commentaires');
      
    $this->page->addVar('title', 'Modification d\'un commentaire');

    if ($request->method() == 'POST')
    {
      $comment = new Comment([
        'id' => $request->getData('id'),
        'auteur' => $request->postData('auteur'),
        'contenu' => $request->postData('contenu')
      ]);
    }
    else
    {
      $comment = $this->managers->getManagerOf('Comments')->get($request->getData('id'));
    }

    $formBuilder = new CommentFormBuilder($comment);
    $formBuilder->build();

    $form = $formBuilder->form();

    $formHandler = new FormHandler($form, $this->managers->getManagerOf('Comments'), $request);

    if ($formHandler->process())
    {
      $this->app->user()->setFlash('Le commentaire a bien été modifié');

      $this->app->httpResponse()->redirect('/admin/');
    }

    $this->page->addVar('form', $form->createView());
  }

  public function processForm(HTTPRequest $request)
  {
    if ($request->method() == 'POST')
    {
      $news = new News([
        'auteur' => $request->postData('auteur'),
        'titre' => $request->postData('titre'),
        'contenu' => $request->postData('contenu')
      ]);

      if ($request->getExists('id'))
      {
        $news->setId($request->getData('id'));
      }
    }
    else
    {
      // L'identifiant de la news est transmis si on veut la modifier
      if ($request->getExists('id'))
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
      }
      else
      {
        $news = new News;
      }
    }

    $formBuilder = new NewsFormBuilder($news);
    $formBuilder->build();

    $form = $formBuilder->form();

    $formHandler = new FormHandler($form, $this->managers->getManagerOf('News'), $request);

    if ($formHandler->process())
    {
      $this->app->user()->setFlash($news->isNew() ? 'La news a bien été ajoutée !' : 'La news a bien été modifiée !');
      
      $this->app->httpResponse()->redirect('/admin/');
    }

    $this->page->addVar('form', $form->createView());
  }
}