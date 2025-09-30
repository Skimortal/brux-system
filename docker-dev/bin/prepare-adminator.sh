#!/usr/bin/env bash
set -euo pipefail

# Zielordner kann optional als 1. Argument übergeben werden
TARGET_DIR="node_modules/adminator-admin-dashboard"

if [ ! -d "$TARGET_DIR" ]; then
  echo "Fehler: Ordner '$TARGET_DIR' nicht gefunden." >&2
  exit 1
fi

# Temporäres Perl-Skript (Parser) anlegen
PROCESSOR="$(mktemp)"
cat > "$PROCESSOR" <<'PERL'
use strict; use warnings;
local $/ = undef;
my $in = <STDIN>;

my $out = '';
my $len = length($in);
my $i = 0;

my $in_block = 0;        # /* ... */
my $in_line  = 0;        # // ... (nur SCSS)
my $in_string = 0;       # '...' oder "..."
my $string_ch = '';

my $in_url = 0;          # innerhalb url(...)
my $url_content = '';
my $url_quote = 0;       # Zitate innerhalb url(...)
my $url_quote_ch = '';

sub prev_is_http_scheme {
    my ($str, $idx) = @_;
    my $start = $idx - 6; $start = 0 if $start < 0;
    my $seg = substr($str, $start, 6);
    return 1 if $seg =~ /https:$/i;
    $start = $idx - 5; $start = 0 if $start < 0;
    $seg = substr($str, $start, 5);
    return 1 if $seg =~ /http:$/i;
    return 0;
}

while ($i < $len) {
    my $c = substr($in, $i, 1);
    my $n = ($i+1 < $len) ? substr($in, $i+1, 1) : '';

    # Blockkommentar: /* ... */
    if ($in_block) {
        if ($c eq '*' && $n eq '/') { $in_block = 0; $i += 2; next; }
        $i++; next;
    }

    # Zeilenkommentar: // ... (bis Zeilenende), CR/LF beachten
    if ($in_line) {
        if ($c eq "\n") { $in_line = 0; $out .= $c; $i++; next; }
        if ($c eq "\r") { $in_line = 0; $out .= $c; if ($n eq "\n") { $out .= $n; $i++; } $i++; next; }
        $i++; next;
    }

    # Innerhalb url(...)
    if ($in_url) {
        if ($url_quote) {
            if ($c eq '\\') { # Escape in Quotes
                $url_content .= $c;
                if ($i+1 < $len) { $url_content .= substr($in,$i+1,1); $i += 2; next; }
                else { $i++; next; }
            }
            if ($c eq $url_quote_ch) { $url_quote = 0; $url_content .= $c; $i++; next; }
            $url_content .= $c; $i++; next;
        } else {
            if ($c eq "'" || $c eq '"') { $url_quote = 1; $url_quote_ch = $c; $url_content .= $c; $i++; next; }
            if ($c eq ')') {
                # Nur innerhalb url(...) den Pfad anpassen
                my $uc = $url_content;
                $uc =~ s{\.\./static/}{../../static/}g;
                $out .= $uc . ')';
                $in_url = 0;
                $url_content = '';
                $i++; next;
            }
            $url_content .= $c; $i++; next;
        }
    }

    # Innerhalb String: nichts als Kommentar behandeln
    if ($in_string) {
        if ($c eq '\\') {
            $out .= $c;
            if ($i+1 < $len) { $out .= substr($in,$i+1,1); $i += 2; next; }
            else { $i++; next; }
        }
        if ($c eq $string_ch) { $in_string = 0; $out .= $c; $i++; next; }
        $out .= $c; $i++; next;
    }

    # Normalzustand -----------------------------

    # url(  erkennen (auch mit Whitespace: url   ( )), Case-insensitive
    if (lc(substr($in, $i, 3)) eq 'url') {
        my $j = $i + 3;
        my $ws = '';
        while ($j < $len) {
            my $w = substr($in, $j, 1);
            last unless ($w =~ /\s/);
            $ws .= $w; $j++;
        }
        if ($j < $len && substr($in, $j, 1) eq '(') {
            $out .= substr($in, $i, 3) . $ws . '('; # Originalschreibweise von 'url' + Whitespace
            $i = $j + 1;
            $in_url = 1;
            $url_content = '';
            next;
        }
    }

    # String-Beginn
    if ($c eq "'" || $c eq '"') { $in_string = 1; $string_ch = $c; $out .= $c; $i++; next; }

    # Blockkommentar-Beginn
    if ($c eq '/' && $n eq '*') { $in_block = 1; $i += 2; next; }

    # Zeilenkommentar-Beginn (SCSS): //, aber NICHT nach http: oder https:
    if ($c eq '/' && $n eq '/' && !prev_is_http_scheme($in, $i)) {
        $in_line = 1; $i += 2; next;
    }

    # Standard: Zeichen übernehmen
    $out .= $c; $i++;
}

print $out;
PERL

# Dateien verarbeiten
# (Nur .css und .scss innerhalb des Zielordners)
while IFS= read -r -d '' file; do
  tmp="$(mktemp)"
  perl "$PROCESSOR" < "$file" > "$tmp"
  mv "$tmp" "$file"
  echo "✔ Verarbeitet: $file"
done < <(find "$TARGET_DIR" -type f \( -name '*.css' -o -name '*.scss' \) -print0)

rm -f "$PROCESSOR"

echo "Fertig."
