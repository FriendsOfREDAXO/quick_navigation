<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use function count;

use rex_article;
use rex_be_controller;
use rex_category;
use rex_clang;
use rex_string;
use rex_url;

class ArticleNavigationButton implements ButtonInterface
{
    public function get(): string
    {
        // Initialisiere die Navigationspfeile nicht
        $prev = '';
        $next = '';
        if (rex_be_controller::getCurrentPage() == 'content/edit') {
            $cat = rex_category::getCurrent();
            $articles = $cat ? $cat->getArticles() : rex_article::getRootArticles();

            // Zeige die Navigationspfeile nur an, wenn mehr als der Startartikel vorhanden ist
            if (count($articles) > 1) {
                // Initialisiere Buttons als deaktiviert
                $prev = '<button class="btn btn-default" disabled><span class="fa fa-chevron-left"></span></button>';
                $next = '<button class="btn btn-default" disabled><span class="fa fa-chevron-right"></span></button>';

                $article_stack = [];
                foreach ($articles as $article) {
                    $article_stack[] = $article->getId();
                }

                $index = array_search(rex_request('article_id'), $article_stack);
                if ($index !== false) {
                    // Vorherigen Artikel aktivieren, wenn möglich
                    if ($index - 1 >= 0) {
                        $article = rex_article::get($article_stack[$index - 1]);
                        $attributes = [
                            'class' => 'btn btn-default',
                            'href' => rex_url::backendPage('content/edit', ['mode' => 'edit', 'clang' => rex_clang::getCurrentId(), 'category_id' => rex_request('category_id'), 'article_id' => $article->getId()]),
                            'title' => $article->getName(),
                        ];
                        $prev = '<a' . rex_string::buildAttributes($attributes). '><span class="fa fa-chevron-left"></span></a>';
                    }

                    // Nächsten Artikel aktivieren, wenn möglich
                    if ($index + 1 < count($article_stack)) {
                        $article = rex_article::get($article_stack[$index + 1]);
                        $attributes = [
                            'class' => 'btn btn-default',
                            'href' => rex_url::backendPage('content/edit', ['mode' => 'edit', 'clang' => rex_clang::getCurrentId(), 'category_id' => rex_request('category_id'), 'article_id' => $article->getId()]),
                            'title' => $article->getName(),
                        ];
                        $next = '<a' . rex_string::buildAttributes($attributes). '><span class="fa fa-chevron-right"></span></a>';
                    }
                }
            }
        }

        if ('' !== $prev && '' !== $next) {
            return '<div class="btn-group">' . $prev . $next . '</div>';
        }

        return '';
    }
}
