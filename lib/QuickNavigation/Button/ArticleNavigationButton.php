<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use rex_article;
use rex_be_controller;
use rex_category;
use rex_clang;
use rex_url;

use function count;

class ArticleNavigationButton implements ButtonInterface
{
    public function get(): string
    {
        // Initialisiere die Navigationspfeile nicht
        $article_directions = '';
        if (rex_be_controller::getCurrentPage() == 'content/edit') {
            $cat = rex_category::getCurrent();
            $articles = $cat ? $cat->getArticles(false) : rex_article::getRootArticles(false);

            // Zeige die Navigationspfeile nur an, wenn mehr als der Startartikel vorhanden ist
            if (count($articles) > 1) {
                // Initialisiere Buttons als deaktiviert
                $predecessor = '<button class="btn btn-default" disabled><span class="fa fa-chevron-left"></span></button>';
                $successor = '<button class="btn btn-default" disabled><span class="fa fa-chevron-right"></span></button>';

                $article_stack = [];
                $current_id = rex_request('article_id');
                foreach ($articles as $article) {
                    $article_stack[] = $article->getId();
                }

                $index = array_search($current_id, $article_stack);
                if ($index !== false) {
                    // Vorherigen Artikel aktivieren, wenn möglich
                    if ($index - 1 >= 0) {
                        $prev_id = $article_stack[$index - 1];
                        $href_prev = rex_url::backendPage('content/edit', [
                            'mode' => 'edit',
                            'clang' => rex_clang::getCurrentId(),
                            'category_id' => rex_request('category_id'),
                            'article_id' => $prev_id,
                        ]);
                        $predecessor = '<a class="btn btn-default" title="' . htmlspecialchars(rex_article::get($prev_id)->getName()) . '" href="' . $href_prev . '"><span class="fa fa-chevron-left"></span></a>';
                    }

                    // Nächsten Artikel aktivieren, wenn möglich
                    if ($index + 1 < count($article_stack)) {
                        $next_id = $article_stack[$index + 1];
                        $href_next = rex_url::backendPage('content/edit', [
                            'mode' => 'edit',
                            'clang' => rex_clang::getCurrentId(),
                            'category_id' => rex_request('category_id'),
                            'article_id' => $next_id,
                        ]);
                        $successor = '<a class="btn btn-default" title="' . htmlspecialchars(rex_article::get($next_id)->getName()) . '" href="' . $href_next . '"><span class="fa fa-chevron-right"></span></a>';
                    }
                }

                // Zusammenbau der Navigationspfeile, falls zutreffend
                $article_directions = $predecessor . ' ' . $successor;
            }
        }

        return $article_directions;
    }
}
