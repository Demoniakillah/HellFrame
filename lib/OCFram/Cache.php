<?php
namespace OCFram;

class Cache{
    
    //Répertoire principale du cache
    const DIR='../Temp/Cache/';
    //Durée de vie des fichiers
    const LIFE=1; //1 minute
    
    //Lit dans le dossier de cache et retourne les données
    function read($filename,$id=NULL,$bof=NULL){
        if(file_exists(self::DIR.$filename)){
            if(stripos($filename, '.php')){
                $filename=self::DIR.$filename;
                if(((time()- filemtime($filename))/60) > self::LIFE){
                    unlink($filename);
                    return false;
                }
                return file_get_contents($filename);
            }
            elseif($filename=='datas/news'){
                $filename=  self::DIR.'datas/news';
               if(((time()- filemtime($filename))/60) > self::LIFE){
                   unlink($filename);
                return false;
                }
                $content = explode('~', explode('|', file_get_contents($filename))[2]);
                foreach ($content as $data) {
                    $data = explode('©', $data);
                    $listNews[]=new \Entity\News(array(
                            'auteur'=>$data[0],
                            'titre'=>$data[1],
                            'contenu'=>$data[2],
                            'id'=> intval($data[5]) , 
                            'dateAjout'=>new \DateTime($data[3]),
                            'dateModif'=>new \DateTime($data[4]))
                            );
                }
                return $listNews;
            }
            elseif ($filename=='datas/commentaires') {
                $filename=  self::DIR.'datas/commentaires';
                if(((time()- filemtime($filename))/60) > self::LIFE){
                    unlink($filename);
                    return false;
                }
                $content = explode('~', explode('|', file_get_contents($filename))[2]);
                if(empty($id)){
                    foreach ($content as $commentaire){
                    $commentaire = explode('©', $commentaire);
                    $listCommentaires[]=new \Entity\Comment(array(
                   'id'=>$commentaire[0],
                    'news'=>$commentaire[2],
                    'auteur'=>$commentaire[1],
                    'contenu'=>$commentaire[3],
                    'date'=>new \DateTime($commentaire[4])
                    ));
                    }
                }
                else{
                    foreach ($content as $commentaire){
                        
                       if($id==$commentaire['1']){
                           $commentaire = explode('©', $commentaire);
                $listCommentaires[]=new \Entity\Comment(array(
                   'id'=>$commentaire[0],
                    'news'=>$commentaire[2],
                    'auteur'=>$commentaire[1],
                    'contenu'=>$commentaire[3],
                    'date'=>new \DateTime($commentaire[4])
                ));
                       }
                    }
                }
            return $listCommentaires;
            }
        }
        return false;
    }
    
    
    //Ecrit le contenu dans le cache
    function write($filename,$content,$dir=NULL){
        
        if (empty($content)) {
            return false;
        }
                
        if($filename=='datas/commentaires'){
           $filename=  self::DIR.$filename;
            foreach ($content as $commentaire){
                $data[]=$commentaire['id'].'©'.
                $commentaire['auteur'].'©'.
                $commentaire['news'].'©'.
                $commentaire['contenu'].'©'.
                $commentaire['date']->format('Y-m-d H:i:s').chr(13);
            }
            return file_put_contents($filename, 'expire le '.date('d/m/Y à H:i:s', (time() + self::LIFE * 60)).chr(13).'|commentaires|'.chr(13).implode('~', $data));
        }
        elseif ($filename=='datas/news') {
            $filename=  self::DIR.$filename;
            foreach ($content as $news) {
                $data[] = $news['auteur'].'©'
                    .$news['titre'].'©'
                    .$news['contenu'].'©'
                    .$news['dateAjout']->format('Y-m-d H:i:s').'©'
                    .$news['dateModif']->format('Y-m-d H:i:s').'©'
                    .$news['id'].chr(13);
            }
            return file_put_contents($filename, 'expire le ' . date('d/m/Y à H:i:s', (time() + self::LIFE * 60)) .chr(13). '|news|' .chr(13). implode('~', $data));
        } 
        elseif ($dir=='views') {
            if(stripos($filename, 'backend')){
                $bof = 'backend';
                }
            else{
                $bof =  'frontend';
            }
            $filename = basename($filename);
            return file_put_contents(self::DIR.$dir.'/'.$bof.'_'.$filename, '<!--expire le ' . date('d/m/Y à H:i:s', (time() + self::LIFE * 60)).'-->'.chr(13).$content);
    }
        else {
            return false;
        }
    }
    
    //Suppression des cache nécessaires après opérations sur le commentaire ou news
    static function clear($filename){
        if (file_exists(self::DIR . $filename)) {
            unlink(self::DIR.$filename);
        }
        $files=  glob(self::DIR.'views/*');
        foreach ($files as $file){
            unlink($file);
        }
    }

}