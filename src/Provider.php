<?php

namespace FriendsOfRedaxo\QuickNavigation;

/**
 * Quelle für Treffer in der Befehlspalette – eine Gruppe in der Ergebnisliste.
 *
 * Angemeldet wird per Attribut, die Klasse braucht einen Konstruktor ohne Pflichtargumente:
 *
 *     #[AsProvider('news', priority: 40, prefix: 'n')]
 *     final class NewsProvider implements Provider
 *     {
 *         public function label(?Query $query = null): string
 *         {
 *             return 'News';
 *         }
 *
 *         public function search(Query $query): iterable
 *         {
 *             if ($query->isEmpty() || !$query->user->hasPerm('news[]')) {
 *                 return;
 *             }
 *             foreach (News::query()->where('title', $query->like(), 'LIKE')->limit($query->limit) as $news) {
 *                 yield new Item($news->title, Url::backendPage('news', ['id' => $news->id]), icon: 'rex-icon fa-newspaper');
 *             }
 *         }
 *     }
 *
 * Die Quelle ist selbst für die Rechte verantwortlich: Sie darf nur liefern, was der Benutzer ($query->user) öffnen darf.
 */
interface Provider
{
    /** Überschrift der Gruppe – ohne $query für die Liste der Kürzel, mit $query z. B. „Zuletzt bearbeitet“ bei leerer Eingabe */
    public function label(?Query $query = null): string;

    /**
     * Treffer für die Eingabe – höchstens $query->limit; bei leerer Eingabe ($query->isEmpty()) Vorschläge wie
     * „zuletzt bearbeitet“ oder nichts.
     *
     * @return iterable<Item>
     */
    public function search(Query $query): iterable;
}
