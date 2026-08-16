# Changelog

## 1.0.0 – noch nicht veröffentlicht

Erste Fassung. Herausgelöst aus www.sn-plus.de, wo dieselbe Sache zuvor als
Projektcode lag, und aus kreativundsoehne.de, wo eine ältere Variante läuft.

- Verdreht die fertige Antwort im Website-Kontext, ohne Eingriff in Vorlagen.
- Twig-Filter `sulu_obfuscate_email` und `sulu_obfuscate_emails` für die Fälle,
  in denen die Vorlage selbst bestimmen soll.
- Marke `data-obfuscated-email` an verdrehten Verweisen: der Browser weiß, was er
  zurückdrehen soll, der Umschreiber weiß, was er schon gesehen hat.
- Beschriftungen, die keine Adresse sind, bleiben lesbar — auf beiden Seiten
  dieselbe Regel.
- Die Vorschau bleibt unangetastet, erkannt am Attribut `preview` der Anfrage.
  Sulu rendert sie mit einem Website-Kernel, der Kontext allein genügt also nicht.
- Unberührt bleiben ausserdem Kommentare und die Elemente `script`, `style`,
  `textarea` und `title`, deren Inhalt Text ist und kein Markup, sowie der Inhalt
  von Verweisen ohne `mailto` — ein Verweis im Verweis überlebt das Parsen nicht.
