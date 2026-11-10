<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Redaxo\Core\Security\User;

use function addcslashes;
use function count;
use function ctype_digit;
use function implode;
use function max;
use function mb_strlen;
use function mb_strpos;
use function mb_strtolower;
use function preg_match;
use function preg_quote;
use function preg_split;
use function str_starts_with;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

/** Eingabe in der Befehlspalette, wie sie eine {@see Provider}-Quelle bekommt */
final readonly class Query
{
    /** @var list<string> Suchwörter, klein geschrieben */
    public array $words;

    public function __construct(
        /** Eingabe ohne Kürzel („m: logo“ → „logo“) */
        public string $term,
        public User $user,
        /** Höchstzahl Treffer dieser Gruppe */
        public int $limit,
        /** Nur in dieser Quelle gesucht (per Kürzel) */
        public bool $scoped = false,
    ) {
        $this->words = preg_split('/\s+/u', mb_strtolower(trim($term)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public function isEmpty(): bool
    {
        return [] === $this->words;
    }

    /** Die Eingabe ist eine Zahl (z. B. die ID eines Artikels) */
    public function isId(): bool
    {
        return 1 === count($this->words) && ctype_digit($this->words[0]);
    }

    /** Muster für SQL LIKE: '%eingabe%' (mit maskierten Platzhaltern), bei mehreren Wörtern nur das längste */
    public function like(): string
    {
        $longest = '';
        foreach ($this->words as $w) {
            if (mb_strlen($w) > mb_strlen($longest)) {
                $longest = $w;
            }
        }

        return '%' . addcslashes($longest, '%_\\') . '%';
    }

    /**
     * Relevanz eines Treffers, 0 = passt nicht. Alle Suchwörter müssen in einem der Texte vorkommen; der erste Text
     * (meist der Titel) zählt am meisten: gleich > beginnt mit > Wortanfang > enthalten.
     */
    public function score(?string $title, ?string ...$more): int
    {
        if ($this->isEmpty()) {
            return 1;
        }
        $title = mb_strtolower((string) $title);
        $all = $title . ' ' . mb_strtolower(implode(' ', $more));
        $phrase = implode(' ', $this->words);

        $score = 0;
        foreach ($this->words as $word) {
            if (false === mb_strpos($all, $word)) {
                return 0;
            }
            if (str_starts_with($title, $word)) {
                $score += 30;
            } elseif (1 === preg_match('/(^|[\s\-_\/.›>|(])' . preg_quote($word, '/') . '/u', $title)) {
                $score += 20;
            } elseif (false !== mb_strpos($title, $word)) {
                $score += 10;
            } else {
                $score += 2;
            }
        }
        if ($title === $phrase) {
            $score += 100;
        } elseif (str_starts_with($title, $phrase)) {
            $score += 40;
        }

        // kürzere Titel bei gleicher Güte zuerst
        return $score * 100 + max(0, 99 - mb_strlen($title));
    }
}
