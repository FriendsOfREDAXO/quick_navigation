<?php
namespace FriendsOfRedaxo\QuickNavigation\Buttons;

use rex_article;
use rex_be_controller;
use rex_category;
use rex_clang;
use rex_request;
use rex_url;

class ArticleNavButton
{
    public function get(): string
    {
        $article_directions = '';
        if (rex_be_controller::getCurrentPage() == 'content/edit') {
            $predecessor = '';
            $successor = '';
            $article_stack = array();
            // Objekt der aktuellen Kategorie laden
            $cat = rex_category::getCurrent();
            if ($cat) {
                $article = $cat->getArticles(false);
            } else {
                $article  = rex_article::getRootArticles();
            }
            $current_id = rex_request('article_id');
            if ($article && $current_id) {
                if (is_array($article)) {
                    // Artikelreihenfolge in eine Array schreiben
                    foreach ($article as $var) {
                        $article_stack[] = $var->getId();
                    }
                    $i = 0;
                    // Zahl der Artikel ermitteln
                    $catcount = count($article_stack);
                    foreach ($article_stack as $var) {
                        if ($var == $current_id) {
                            $successor = '
        <button class="btn btn-default" disabled>
           <span class="fa fa-chevron-right"> 
        </button>
    ';
                            if ($i + 1 < $catcount) {
                                // ID des nachfolgenden Artikels ermitteln
                                $next_id = $article_stack[$i + 1];
                                // Artikel-Objekt holen, um den Namen des vorhergehenden Artikels zu ermitteln,
                                // danach Link schreiben
                                $article = rex_article::get($next_id);

                                $href_next = rex_url::backendPage(
                                    'content/edit',
                                    [
                                        'mode' => 'edit',
                                        'clang' => rex_clang::getCurrentId(),
                                        'category_id' => rex_request('category_id'),
                                        'article_id' => $next_id
                                    ]
                                );
                                $successor = '

    <a class="btn btn-default" title="' . $article->getName() . '" href="' . $href_next . '">
      <span class="fa fa-chevron-right"> 
    </a>
';
                            }

                            if ($i - 1 > -1) {
                                $prev_id = $article_stack[$i - 1];

                                $href_prev = rex_url::backendPage(
                                    'content/edit',
                                    [
                                        'mode' => 'edit',
                                        'clang' => rex_clang::getCurrentId(),
                                        'category_id' => rex_request('category_id'),
                                        'article_id' => $prev_id
                                    ]
                                );

                                if ($i < $catcount) {
                                    $article = rex_article::get($prev_id);

                                    $predecessor = '

        <button class="btn btn-default" disabled>
           <span class="fa fa-chevron-left"> 
        </button>
    ';

                                    if ($article) {
                                        $predecessor = '

        <a class="btn btn-default" title="' . $article->getName() . '" href="' . $href_prev . '">
           <span class="fa fa-chevron-left"> 
        </a>
    ';
                                    }
                                }
                            }
                        }
                        $i++;
                    }
                }
                $article_directions = '

' . $predecessor . '

' . $successor . '
';
            }
        }
        return $article_directions;
    }
}
