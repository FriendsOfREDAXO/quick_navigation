<?php

namespace FriendsOfRedaxo\QuickNavigation\ExtensionPoint;

use FriendsOfRedaxo\QuickNavigation\Domain\Domain;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;

/**
 * Domains der Website für den Strukturbaum und die Badges in der Palette – AddOns mit Domains (fluid, yrewrite …)
 * tragen ihre hier ein. Erst ab zwei Domains zeigt der Baum die Auswahl.
 *
 *     Extension::register(QuickNavigationDomains::class, static function (QuickNavigationDomains $ep): array {
 *         $domains = $ep->subject;
 *         foreach (YRewrite::getDomains() as $name => $domain) {
 *             $domains[] = new Domain($name, $domain->getHost(), $domain->getMountId(), $domain->getUrl());
 *         }
 *         return $domains;
 *     });
 *
 * `::class` lädt die Klasse nicht – die Anmeldung schadet also nicht, wenn Quick Navigation fehlt.
 *
 * @extends ExtensionPoint<list<Domain>>
 */
final class QuickNavigationDomains extends ExtensionPoint
{
    /** @param list<Domain> $domains */
    public function __construct(array $domains = [])
    {
        parent::__construct(self::class, $domains);
    }
}
