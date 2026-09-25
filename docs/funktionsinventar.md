# Funktionsinventar Bestand → neues Frontend

Stand: 25.09.2026 · Grundlage: Code-Analyse von `main` (Commit 6c1f46f + uncommittete WordPress-Push-Änderungen).
Ziel: **Jede bestehende Funktion bleibt erhalten** – nur schöner und intuitiver. Spalte „Neu“ verweist auf die Designstudie.

## 1. Dashboard / Kalender

| Bestand | Wo heute | Neu |
|---|---|---|
| Gesamtansicht (1 Kalender) | Radio „Gesamtansicht“ | Umschalter „Gesamt“ |
| Raumansicht (1 Kalender je Raum mit `showOnDashboard`, synchron geblättert) | Radio „Raumansicht“, 2-Spalten-Raster | Umschalter „Räume“: Räume als Zeilen, Tage als Spalten |
| Termine ohne Raum erscheinen in *jedem* Raumkalender | Backend-Filter | eigene Zeile „Ganzes Haus / ohne Raum“ |
| Monat / Woche / Tag, 08–22 Uhr, Mo als Wochenstart, mobil Woche | FullCalendar-Toolbar | bleibt (Tag/Monat weiter mit FullCalendar) |
| Filter Produktion / Reinigung / Privat / Schlüssel (derzeit ausgeblendet) | versteckte Checkboxen | sichtbare Filter-Chips mit Farblegende |
| Vergangene Termine durchgestrichen + blass | `eventDidMount` | bleibt (dezent blass, ohne Durchstreichen) |
| Klick auf freie Fläche → neuer Termin (Zeit, 1 h, Raum vorbelegt) | `dateClick` → Modal | Schnellanlage-Popover → Panel |
| Klick auf Eintrag → je nach Art Modal / Bestätigungsdialog / `alert()` | `handleEventClick` | **ein** Seitenpanel, Inhalt je Eintragsart |
| Titel-Icons: Ereignisart, Bedarf Licht/Ton/Aufbau (grün wenn besetzt), Techniker bestätigt/offen, Freiwillige bestätigt/offen | HTML im Titel | Status-Zeile auf der Karte („Technik 1/2 bestätigt“), Details im Panel |
| Farben je Produktion (9 Grundfarben) | `CalendarColorService` | Farbe nach Eintragsart, Produktion als Text; Produktionsfarbe optional als Akzent |
| WP-Sync-Button „Veranstaltungen aus WP laden“ + Toast mit Statistik | Dashboard-Kopf | Statuszeile „WordPress · zuletzt 03:00 · jetzt laden“ |
| Reinigungs-Monatsbericht (Monat wählen → PDF) | Dashboard-Kopf | Button „Reinigungsbericht“ im Kalenderkopf |
| Todo-Liste (anlegen, bearbeiten, erledigt) | Sidebar-Box | Reiter „Todos“ neben „Schlüssel“ in der rechten Spalte |
| Schlüsselliste mit Filter Verliehen / Verfügbar / Verloren | Sidebar-Box | Reiter „Schlüssel“ (Standard), Filter-Chips mit Anzahl |
| Toasts (Erfolg/Fehler) | eigene Bootstrap-Alerts | Turbo-Stream-Toasts |

### Eintragsarten und ihr Panel

| Eintragsart | Quelle | Panel |
|---|---|---|
| **Vorstellung** (`ProductionEvent`) | WordPress | **Nur-Lese-Ansicht** der WP-Daten mit Kennzeichnung „aus WordPress · letzter Sync“, bearbeitbar nur Ansprechpartner (sofort gespeichert). Link „Auf Website ansehen“, „Bearbeiten“-Seite entfällt. |
| Termin **Privat** | lokal | Titel, Raum, Zeitraum, ganztägig, Wiederholung, Beschreibung |
| Termin **Produktion** | lokal | + Produktion, Bedarfsanzeige (Licht/Ton/Aufbau/Tribüne), Ereignisart (Probe / Aufbau-Abbau / Veranstaltung), Status (Reserviert / Fix), „Externe Techniker kommen“, Techniker, Freiwillige. Titel entfällt (= Produktion). |
| Termin **Geschlossene Veranstaltung / Schulveranstaltung / Intern** | lokal | Grundfelder + Techniker + Freiwillige |
| Termin **Reinigung** | lokal | Reinigungskraft (Kontaktkategorie Reinigung), Bereiche (Tägliche Reinigung, Raum Schwarz, Raum Weiss, Künstlerinnengarderobe, Publikumstoilette, Büro); Titel und Raum entfallen; legt Extra-Reinigung für den Bericht an |
| **Reinigungsplan-Termin** (aus `CleaningSchedule` erzeugt) | lokal, virtuell | Info zur Regel, „Für diesen Tag absagen“ / „Absage zurücknehmen“ (statt `confirm()`), „Reinigungsplan bearbeiten“ |
| **Schlüssel** (Ausgabe/Rückgabe) | lokal | Status Verfügbar / Verliehen / Verloren, verliehen an (Kontakt oder Benutzer), Ausgabe, Rückgabe geplant, Beschreibung, Hinweis bei Überfälligkeit |

Techniker-Zeile: Kontakt (Kategorie Techniker), bestätigt, **Licht / Ton / Aufbau** (im Modell vorhanden, im heutigen UI fehlend → wird ergänzt).
Freiwilligen-Zeile: Kontakt (Kategorie Freiwillige), bestätigt, Aufgaben (Bar, Aufbau, Abbau, Ticketing, Aufräumen, Anderes).
Aktionen im Termin-Panel: Speichern, Duplizieren („(Kopie)“), Löschen (Serie nur, wenn der Termin zu einer Serie gehört).

## 2. WordPress-Hybridbetrieb

Heute führt WordPress. Das Panel zeigt deshalb pro Feld, wem es gehört:

| Feld | Herkunft | Im Panel |
|---|---|---|
| Titel, Permalink, Bild, Beschreibung, Auszug der Produktion | WP, bei jedem Sync überschrieben | nur lesen |
| Datum, Beginn, Ende, Raum, Status, Reservierungsstatus, Kontingent, Reserviert, Frei, Reservierungshinweis, Kategorien, Preise | WP, bei jedem Sync überschrieben | nur lesen, Abschnitt „aus WordPress“ |
| Ansprechpartner der Vorstellung | lokal | bearbeitbar |
| Technik-Bedarf, Tribüne, Produktions-Techniker, Produktions-Ansprechpersonen | lokal (werden bereits nach WP gepusht) | bearbeitbar (Produktionsseite) |
| Raumname, „im Dashboard zeigen“ | lokal | bearbeitbar (Verwaltung) |

Technik: Das Panel wird aus einer **Feld-Eigentümer-Konfiguration** gebaut (`wp` / `local`). Wenn das System später führend wird, stellt man Felder auf `local` um – die Oberfläche muss nicht neu gebaut werden.

Bekannte Probleme des Hybridbetriebs (müssen vor „System führt“ gelöst sein):
- Push nach WP ist ohne Wirkung (`WORDPRESS_API_URL` leer), HTTP-Fehler werden als Erfolg gewertet, Rückmeldung fehlt im UI.
- Sync überschreibt lokale Änderungen an WP-Feldern ohne Rückfrage (nachts 03:00, beim ersten Login nach 24 h, per Button).
- Vorstellungen haben keine stabile WP-ID, Zuordnung nur über `eventIndex` → Kollisionsgefahr.
- Löschungen werden in keine Richtung übertragen; lokal gelöschte Vorstellungen kommen beim nächsten Sync zurück.
- **Vorstellung (`ProductionEvent`) und Produktions-Termin (`Appointment`) sind nicht verknüpft**: Eine Vorstellung kann doppelt im Kalender stehen; Technik/Freiwillige hängen nur am Termin, Ansprechpartner nur an der Vorstellung. Vorschlag: optionale Verknüpfung `Appointment.productionEvent` und im Vorstellungs-Panel „Technik & Freiwillige einplanen“.

## 3. Verwaltung (zweite Ebene)

| Modul | Funktionen | Neu |
|---|---|---|
| Räume | Name, externe ID (WP-Slug), im Dashboard zeigen | Verwaltung |
| Kontakte + Kategorien | Kartenliste nach Kategorie, Filter; Techniker = Kategorie 1, Freiwillige = 2, Reinigung = 3 (fest verdrahtet) | Verwaltung; Kategorie-Rolle konfigurierbar statt ID |
| Produktionen | Liste (nur künftige / alle), Detail: WP-Felder lesend, Technik-Bedarf, Tribüne, Techniker, Ansprechpersonen, Vorstellungs-Kalender + Liste | Produktionsseite (Screens 1–2, Ausblick-Teile markiert) |
| Vorstellungen | Liste, Bearbeiten (nur Ansprechpartner) | im Kalender-Panel; eigene Seite entfällt |
| Schlüssel | Anlegen (Name, Räume); Verleihen nur im Dashboard | Stammdaten in Verwaltung, Verleihen im Kalender |
| Reinigungsplan | Kontakt, Wochentage, Zeit, aktiv von/bis | Verwaltung, erreichbar aus dem Reinigungs-Panel |
| Event-Kategorien | Name, externe ID, Slug (von WP überschrieben) | Verwaltung (Admin) |
| Benutzer, Profil (Avatar, Passwort), Passwort vergessen, E-Mail-Vorlagen | | Verwaltung / Einstellungen |
| Altbestände Reinigungsfirma, Techniker, Freiwillige, Produktions-Techniker-CRUD | nicht im Menü | nicht übernehmen (vorher Datenabgleich) |

## 4. Fehler im Bestand, die beim Umbau mit behoben werden sollten

Dashboard
1. Techniker-Zeile ohne Licht/Ton/Aufbau → Bedarfs-Icons werden nie grün.
2. Reinigungskraft wird beim Anlegen als `cleaning`, beim Bearbeiten als `cleaningContact` gespeichert, beim Öffnen aus dem ersten Techniker gelesen; Serien kopieren sie nicht.
3. Raum-Zwang Schwarz/Weiss bei Reinigung nur beim Bearbeiten, mit fest verdrahteten Raum-IDs.
4. Extra-Reinigungen werden über die Termin-ID statt die Kontakt-ID zugeordnet → erscheinen kaum im Kalender, ID-Kollisionen im Bericht möglich.
5. Kalender-Feed lädt immer **alle** Termine (Datumsbereich ignoriert).
6. Löschdialog bietet „ganze Serie“ auch bei Einzelterminen; Serien lassen sich nicht gemeinsam bearbeiten.
7. Schlüssel: Beschreibung wird nicht gespeichert, Datumsfelder werden immer befüllt, Öffnen aus dem Kalender scheitert, wenn der Schlüssel in der Liste ausgefiltert ist, Speichern lädt die ganze Seite neu.
8. Todo-Dialog zeigt nach Wechsel evtl. alten Text.
9. Titel und WP-Inhalte werden als rohes HTML ausgegeben.

Verwaltung / Sicherheit
10. Löschen über GET-Links ohne CSRF; JSON-Endpunkte ohne CSRF.
11. E-Mail-Vorlagen (Twig ohne Sandbox) für jeden eingeloggten Benutzer bearbeitbar.
12. Admin sieht leere Benutzer- und Vorlagenliste; Editor kann Superuser anlegen.
13. `isActive` wird beim Login nicht geprüft; Link „Passwort zurücksetzen“ vermutlich durch fehlerhafte Access-Regel blockiert.
14. Neue Produktion / neuer Produktions-Techniker / neue Vorstellung lassen sich nicht sinnvoll anlegen (Pflichtfelder gesperrt bzw. fehlend).
15. Fehlendes Template `contact/_form.html.twig` (AJAX-Pfad).
16. Kategorie löschen löscht alle Kontakte darin.
17. WP-Sync blockiert den Login-Request (synchron).
