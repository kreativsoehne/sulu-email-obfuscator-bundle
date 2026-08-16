# SuluEmailObfuscatorBundle

Liefert Mailadressen auf Sulu-Seiten verdreht aus: im Quelltext steht ROT13, lesbar wird die Adresse erst im Browser. Läuft mit Sulu 2.6 und Sulu 3, und ohne Sulu als gewöhnliches Symfony-Bundle.

Der Unterschied zu den üblichen Twig-Filtern: **es gibt nichts einzubauen.** Das Bundle schreibt die fertige Seite um, nicht einzelne Vorlagen. Eine Sulu-Seite trägt Adressen an Stellen, die keine Vorlage des Projekts kontrolliert — Fließtext aus dem Editor, Snippets, Artikel, Rechtstexte aus einer Fremd-API. Ein Filter erwischt immer nur die Stellen, an die jemand gedacht hat.

## Funktionsweise

- **Umgeschrieben wird die Antwort**, spät im `kernel.response` und vor dem HTTP-Cache. Was im Cache landet, ist bereits verdreht.
- **Nur im Website-Kontext.** Sulu betreibt Backend und Auftritt aus einer Codebasis und unterscheidet sie über `sulu.context`; daran hängt die Registrierung. Das Backend bleibt unangetastet — ein Eingabefeld muss die Adresse zeigen, die die Redaktion getippt hat.
- **Die Vorschau zeigt die echte Adresse.** Sulu rendert sie zwar mit einem Website-Kernel, markiert die Anfrage aber mit dem Attribut `preview`; daran erkennt der Listener sie und lässt sie liegen. Zweimal richtig so: die Redaktion prüft Inhalte, und die Vorschau tauscht beim Tippen Teile des Dokuments aus — darin eingesetzte Skripte laufen nicht, verdrehte Adressen blieben also stehen.
- **Verdrehte Verweise tragen `data-obfuscated-email`.** Daran erkennt das Skript sie im Browser, und daran erkennt der Umschreiber, was er schon bearbeitet hat. Zweimal angewendet ändert also nichts.
- **Beschriftungen bleiben lesbar**, außer sie sind selbst eine Adresse. „Schreib uns" bleibt „Schreib uns"; Browser und Server halten sich an dieselbe Regel.
- **Skripte und Stile bleiben unberührt.** Eine Adresse in einem Attribut wird nie angefasst, sonst zerfiele das Markup.
- **Das Skript geht nur auf Seiten mit, auf denen es etwas zu tun gibt** — knapp ein Kilobyte, inline, kein Build-Schritt, keine zusätzliche Anfrage.

## Installation

```bash
composer require kreativsoehne/sulu-email-obfuscator-bundle
```

Bundle registrieren (`config/bundles.php`), falls Flex das nicht übernimmt:

```php
KreativSoehne\SuluEmailObfuscatorBundle\SuluEmailObfuscatorBundle::class => ['all' => true],
```

Das war es. Ohne weitere Einstellung gehen ab dem nächsten Aufruf alle Adressen verdreht raus.

## Einstellungen

Alle Werte sind Vorgaben, die Datei ist nur nötig, wenn davon abgewichen wird:

```yaml
# config/packages/sulu_email_obfuscator.yaml
sulu_email_obfuscator:
    enabled: true                # schaltet das Bundle ganz ab
    automatic: true              # schreibt die fertige Seite um
    link_plain_addresses: true   # eine frei stehende Adresse wird zum Verweis
    script: auto                 # auto | manual | none
    excluded_routes: ['sulu_preview', 'sulu_admin']
```

- `automatic: false` lässt nur die Filter übrig, für Projekte, die jede Stelle selbst bestimmen wollen.
- `link_plain_addresses: false` lässt Adressen im Fließtext stehen, statt sie zu verlinken. Sie bleiben dann lesbar — verdrehen lässt sich nur, was ein Verweis ist.
- `script: manual` heißt: die Vorlage setzt `{{ sulu_email_obfuscator_script() }}` selbst, vor `</body>`. Nötig bei einer strengen Content Security Policy, dann mit Nonce: `{{ sulu_email_obfuscator_script(csp_nonce) }}`.
- `script: none` heißt: das Projekt bringt das Skript selbst mit. Es liegt nach `bin/console assets:install` unter `public/bundles/suluemailobfuscator/email-obfuscator.js` und lässt sich genauso aus `vendor/` in den eigenen Build importieren.

## Twig

Gebraucht nur, wo die Vorlage die Sache selbst in der Hand hat:

```twig
{# eine Adresse aus einem Feld, Verweis in der Vorlage #}
<a href="mailto:{{ address|sulu_obfuscate_email }}" {{ sulu_email_obfuscator_attribute() }}>
    {{ address|sulu_obfuscate_email }}
</a>

{# fertiges HTML, in dem Adressen stecken können #}
{{ content.text|sulu_obfuscate_emails }}
```

Beides ist neben dem automatischen Umschreiben unschädlich: was die Marke trägt, wird kein zweites Mal angefasst.

**`sulu_obfuscate_emails` gibt HTML zurück und wird deshalb nicht mehr maskiert.** Er gehört nur an Werte, die ohnehin als HTML ausgegeben werden — also dorthin, wo vorher `|raw` stand. An einer ungeprüften Eingabe steht er falsch: dann liefe der Text ungefiltert in die Seite. Für eine einzelne Adresse aus einem Textfeld ist `sulu_obfuscate_email` der richtige Filter, der maskiert wie jeder andere Wert.

## Was das Bundle nicht kann

- **Ohne JavaScript bleibt die Adresse unlesbar.** Das ist der Preis des Verfahrens, nicht ein Fehler. Wo das nicht tragbar ist, gehört eine Rufnummer oder ein Kontaktformular daneben.
- **Es ist Abschreckung, kein Schutz.** Wer ROT13 zurückdreht, hat die Adresse. Gemeint sind die Sammler, die Quelltexte nach dem Klammeraffen durchsuchen.
- **Strukturierte Daten bleiben lesbar.** Inhalte in `<script type="application/ld+json">` werden bewusst nicht angefasst — Suchmaschinen und KI-Systeme sollen sie lesen können, und abweichen darf die Auszeichnung vom Sichtbaren ohnehin nicht. Steht dieselbe Adresse in einer FAQ-Antwort, steht sie damit auch im JSON-LD. Solche Antworten gehören umformuliert, nicht verdreht.
- **Formulare fasst es nicht an.** Ein Kontaktformular ist eine andere Aufgabe, dafür gibt es [SuluRecaptchaBundle](https://github.com/kreativsoehne/sulu-recaptcha-bundle).

## Lizenz

MIT, siehe [LICENSE](LICENSE).
