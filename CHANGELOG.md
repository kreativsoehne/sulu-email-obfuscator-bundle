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
