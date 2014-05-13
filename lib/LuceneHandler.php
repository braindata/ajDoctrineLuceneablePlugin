<?php

/**
 * Description of LuceneHandler
 *
 * Lucene Handler is the static class for distributing the LuceneIndex.
 * @author Arend van Waart <arendvw@dds.nl>
 */
class LuceneHandler {

    static public $_config = array();
    static public $_index = false;

    static public function getLuceneIndex()
    {
      if (self::$_index)
      {
          return self::$_index;
      }

      self::autoload();
      
      Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8());
      Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');

      Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());

      
      if (file_exists($index = self::getLuceneIndexFile()))
      {
        self::$_index = Zend_Search_Lucene::open($index);
        return self::$_index;
      }
      else
      {
        self::$_index = Zend_Search_Lucene::create($index);
        return self::$_index;
      }
    }

    static public function autoload()
    {
        if (sfConfig::get('sf_lucene_use_packaged_lucene',true))
        {
            self::registerZend();
        } else {
          if (sfContext::hasInstance()) {
            $dispatcher = sfContext::getInstance()->getEventDispatcher();
            $dispatcher->notify(new sfEvent(new stdClass(),'luceneable.autoload', array()));
          }
        }

        // set the Lucene analyzer to allow fuzzy plural searching
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new StandardAnalyzer_Analyzer_Standard_German());
    }
    
      static protected $zendLoaded = false;

      static public function registerZend()
      {
        if (self::$zendLoaded)
        {
          return;
        }

        set_include_path(dirname(__FILE__).'/../data/vendor'.PATH_SEPARATOR.get_include_path());
        require_once dirname(__FILE__).'/../data/vendor/Zend/Loader/Autoloader.php';
        Zend_Loader_Autoloader::getInstance();
        self::$zendLoaded = true;
      }

      // remove current index and create a new one.
      static public function recreateIndex()
      {
          $files = sfFinder::type('*')->in(self::getLuceneIndexFile());

          foreach ($files as $file)
          {
              unlink ($file);
          }
          rmdir(self::getLuceneIndexFile());
      }


    static public function getLuceneIndexFile()
    {
      $path = sfConfig::get('sf_lucene_data_dir',sfConfig::get('sf_data_dir') . '/search');
      if (sfConfig::get('sf_lucene_use_env',false))
      {
        return $path.'/lucene.'.sfConfig::get('sf_environment').'.index';
      } else {
        return $path.'/lucene.index';
      }
    }

}
