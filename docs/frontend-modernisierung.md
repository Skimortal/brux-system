# Frontend-Modernisierung – Vorschlag

Stand: 25.09.2026 · Branch `feature/frontend-modernization`
Designstudie: https://claude.ai/artifact/TAqvDJiZUXhyXP4JSZayuv
Vollständige Bestandsaufnahme alt → neu: [funktionsinventar.md](funktionsinventar.md)

## Ausgangslage

| Bereich | Heute |
|---|---|
| Backend | Symfony 7.3, Doctrine ORM 3, PHP 8.2 – bleibt |
| Build | Webpack Encore **und** AssetMapper/importmap parallel |
| UI-Basis | Adminator-Template (Bootstrap 5 + jQuery), Themify- und Font-Awesome-4-Icons |
| JS | `dashboard.js` mit 2.237 Zeilen imperativem jQuery/DOM-Code, Modals werden per String-HTML gebaut |
| Libs | FullCalendar 6, DataTables, daterangepicker + moment, Tom Select, Chart.js |
| Hotwire | Turbo und Stimulus sind installiert, aber kaum genutzt (3 Controller, Turbo auf Formularen oft deaktiviert) |

### Probleme im Produktions-/Veranstaltungs-Flow

1. **Zwei Welten für dieselbe Sache.** Vorstellungen sind `ProductionEvent` (aus WordPress), Proben und Aufbau sind `Appointment` vom Typ `production` und werden nur im Dashboard-Modal gepflegt. Einen gemeinsamen Spielplan pro Produktion gibt es nicht.
2. **Seitenwechsel für jede Kleinigkeit.** Termin bearbeiten → eigene Seite → Redirect zurück zur Produktion. Kein Inline-Edit.
3. **Termin-Formular ist fast komplett `disabled`.** Das passt, solange WordPress führt. Mit dem neuen `WordPressPushService` (noch uncommitted auf `main`) wird das System zur Quelle – dann müssen Datum, Zeit, Raum, Status und Kontingente editierbar sein.
4. **Keine Serienanlage.** Jede Vorstellung wird einzeln angelegt.
5. **Keine Konfliktprüfung** gegen Raumbelegung, Reinigung oder private Termine.
6. **Status nicht sichtbar:** Auslastung, offene Technik-Besetzung (`needs*Technician`) und Sync-Status mit WordPress sieht man nirgends auf einen Blick.
7. Die Produktionsseite zeigt reine WordPress-HTML-Inhalte als gesperrte Textareas – viel Platz für wenig Nutzen.

## Empfehlung: Symfony UX „modern“ statt SPA

Server-rendered bleiben, aber mit zeitgemäßem Hotwire-Stack. Kein zweites Frontend-Projekt, keine API-Schicht für alles, Formulare, Validierung und Security von Symfony bleiben nutzbar.

| Baustein | Einsatz |
|---|---|
| **AssetMapper** (Encore raus) | kein Node-Build mehr nötig, importmap ist schon da |
| **Tailwind CSS 4** via `symfonycasts/tailwind-bundle` | eigenes, schlankes Designsystem (Tokens aus der Designstudie), ersetzt Adminator/Bootstrap |
| **Twig Components** | wiederverwendbare UI-Bausteine: Button, Badge, Field, Drawer, StatusPill, CapacityBar … |
| **Live Components** | Termin-Seitenpanel mit Autosave, Serien-Generator mit Live-Vorschau und Konfliktprüfung, Filterlisten |
| **Turbo 8** (Frames, Streams, Morphing) | Seitenpanel als Turbo-Frame, Listen-Updates ohne Reload, Toasts per Stream |
| **Stimulus** | kleine Verhaltens-Controller (Shortcuts ⌘K, Dropdowns, Kalender-Brücke) |
| FullCalendar 6 | bleibt, als Stimulus-Controller gekapselt |
| Tom Select | bleibt, über `symfony/ux-autocomplete` |
| raus | jQuery, moment, daterangepicker, DataTables, Adminator, Themify, Font Awesome 4 |

**Alternative:** Vue 3 bzw. React als „Inseln“ nur für Kalender und Spielplan (über `symfony/ux-vue`). Das lohnt sich erst, wenn dort Drag-and-drop-Planung über mehrere Räume gewünscht ist. Eine komplette SPA würde eine API für alle Entitäten und doppelte Validierung bedeuten – für ein internes Verwaltungstool mit wenigen Nutzer:innen zu viel Aufwand.

## Bedienkonzept: Arbeiten vom Kalender aus

Der Kunde arbeitet hauptsächlich im Kalender. Deshalb ist das Dashboard **der Kalender** – alles andere ist zweite Ebene (Designstudie, Screens A–D):

- **Layout:** Die Navigation schrumpft auf eine Icon-Leiste. Der Kalender bekommt die volle Breite, rechts steht dauerhaft die **Schlüsselliste**.
- **Ansicht:** Woche mit Räumen als Zeilen und Tagen als Spalten (Umschalter auf Tag, Monat und „Alles in einem“). Farblegende und Filter für Veranstaltungen, Proben/Aufbau, Reinigung, Vermietung/Privat, Intern und Schlüssel.
- **Schlüssel-Zeile:** Unter den Räumen zeigt eine eigene Zeile jede Ausgabe als Balken von–bis: an wen, geplant, ausgegeben oder überfällig.
- **Hybridbetrieb mit WordPress:** Vorstellungen aus WordPress werden im Panel gelesen, nicht bearbeitet (Kennzeichnung „aus WordPress · letzter Sync“). Hier gepflegt werden nur lokale Ergänzungen (Ansprechpartner, Technik, Freiwillige). Welche Felder wem gehören, steht in einer Konfiguration – später lassen sich Felder auf „hier führend“ umstellen, ohne das UI neu zu bauen.
- **Ein Panel für alles:** Ein Klick auf einen Termin ersetzt die Schlüsselliste durch das passende Bearbeitungs-Panel mit Autosave:
  - *Veranstaltung/Probe/Aufbau:* Zeiten, Raum, Reservierungen, Team, zugehöriger Schlüssel, Link zur Produktion
  - *Reinigung:* Reinigungskraft, Herkunft aus dem Reinigungsplan, „nur diesen Termin verschieben/absagen“ (= `CleaningException`), Lösungsvorschlag bei Konflikt
  - *Vermietung/Privat:* Kontakt, Zeiten, „Reinigung danach einplanen“, Schlüssel
  - *Schlüssel:* Status Verfügbar / Verliehen / Verloren, an wen (Kontakt oder Benutzer), Ausgabe, Rückgabe geplant, Beschreibung. Ausbaustufe: Übergaben im Voraus planen, Verlauf, Erinnerung (braucht `KeyLoan`)
- **Schnellanlage:** Klick auf eine freie Fläche öffnet ein Popover mit Raum und Tag vorausgefüllt; Typ wählen (auch per Tastenkürzel) → Panel öffnet sich.
- **Konflikte** (Raum doppelt belegt, Reinigung überschneidet Probe) werden im Kalender gestrichelt markiert und im Panel erklärt.
- **Ziehen** verschiebt Termine und verlängert Schlüssel-Ausgaben.

## Produktionen als zweite Ebene

Für die Detailpflege einer Produktion (Screens 1–4):

1. **Produktionen – Übersicht:** Wochenleiste „Diese Woche im Haus“, Liste mit nächstem Termin, Auslastung, Besetzungsstatus (Licht/Ton/Aufbau) und Website-Status; Schnellfilter „Besetzung offen“, „Website-Fehler“.
2. **Produktion als Arbeitsplatz:** Tabs Spielplan · Team & Kontakte · Technik & Bühne · Preise · Website-Inhalt. Der **Spielplan** führt Vorstellungen, Proben und Aufbau zusammen, gruppiert nach Monat. Klick auf einen Termin öffnet ein **Seitenpanel** (Datum, Raum, Zeiten, Status, Kontingente je Preiskategorie, Team, Ansprechperson) mit Autosave und Sync-Rückmeldung.
3. **Terminserie anlegen:** Zeitraum + Wochentage + Zeit + Raum → Live-Vorschau mit Raumkonflikten (Privatveranstaltung, Reinigung) → Konflikte überspringen oder anderen Raum wählen → alles in einem Schritt anlegen, inkl. Preise/Team übernehmen und optionalem Aufbau-Termin.
4. **Mobil „Heute im Haus“:** für Abenddienst und Technik – heutige Vorstellung, Zuständige mit Anruf-Button, offene Positionen, wer den Schlüssel hat.

## Fachliche Entscheidungen vorab

- **Führendes System:** Sind Datum/Zeit/Raum/Kontingent künftig hier editierbar und werden nach WordPress gepusht? (Der `WordPressPushService` deutet darauf hin.) Davon hängt ab, welche Felder im Panel editierbar sind.
- **Proben/Aufbau:** Bleiben das `Appointment`s mit Verknüpfung zur Produktion (nur UI vereinheitlichen) oder wird das Modell zusammengeführt? Empfehlung: zunächst nur in der UI zusammenführen (ein Read-Model „Spielplan-Eintrag“), keine Migration.
- **Schlüssel-Ausgaben planen:** `KeyManagement` kennt heute nur *einen* aktuellen Zustand (Status, `borrowDate`, `returnDate`, eine von fünf möglichen Personen-Relationen). Für geplante Übergaben, die Schlüssel-Zeile im Kalender und einen Verlauf braucht es eine eigene Entität, z. B. `KeyLoan` (Schlüssel, Empfänger, von, bis, Status, optional verknüpfter Termin). Der Status des Schlüssels wird daraus abgeleitet. Das ist die einzige Modelländerung, die das Kalenderkonzept wirklich voraussetzt.
- **Sync-Status:** Für die Anzeige „Website aktuell / fehlgeschlagen“ braucht `ProductionEvent`/`Production` Felder wie `lastPushedAt` und `lastPushError`.

## Vorgehen (inkrementell, jederzeit lauffähig)

1. **Fundament:** AssetMapper-only, Tailwind + Tokens, neues Layout (Sidebar/Topbar) als Twig Components. Alt-Seiten laufen parallel über das alte Layout weiter.
2. **Kalender-Dashboard (Priorität):** Raum-Wochenansicht als eigene Twig/Live Component (die Ressourcen-Ansichten von FullCalendar gehören zur kostenpflichtigen Premium-Version; FullCalendar bleibt für Tag/Monat), Drag-and-drop per Stimulus, Schlüsselliste, kontextabhängiges Seitenpanel (Live Components je Typ), Schnellanlage, Konfliktprüfung als eigener Service mit Tests. `KeyLoan` einführen und bestehende Daten migrieren. `dashboard.js` wird dabei vollständig ersetzt.
3. **Produktionen:** Übersicht, Arbeitsplatz mit Spielplan (nutzt dasselbe Panel), Serien-Generator.
4. **Restliche CRUD-Bereiche** (Kontakte, Räume, Schlüssel, Reinigung, Benutzer) auf die neuen Komponenten umstellen.
5. **Aufräumen:** Encore, jQuery, Adminator und Alt-Templates entfernen.
