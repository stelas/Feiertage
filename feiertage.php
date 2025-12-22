<?php

require 'season.php';

abstract class Kategorie {
	const Feiertag = 1;
	const Gedenktag = 2;
	const Aktionstag = 3;
}

abstract class Bundesland {
	private static $ShortNames = array('BW', 'BY', 'BE', 'BB', 'HB', 'HH', 'HE', 'MV', 'NI', 'NW', 'RP', 'SL', 'SN', 'ST', 'SH', 'TH');
	private static $LongNames = array('Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg', 'Bremen', 'Hamburg', 'Hessen', 'Mecklenburg-Vorpommern', 'Niedersachsen', 'Nordrhein-Westfalen', 'Rheinland-Pfalz', 'Saarland', 'Sachsen', 'Sachsen-Anhalt', 'Schleswig-Holstein', 'Thüringen');

	const Baden_Wuerttemberg = 1;
	const Bayern = 2;
	const Berlin = 3;
	const Brandenburg = 4;
	const Bremen = 5;
	const Hamburg = 6;
	const Hessen = 7;
	const Mecklenburg_Vorpommern = 8;
	const Niedersachsen = 9;
	const Nordrhein_Westfalen = 10;
	const Rheinland_Pfalz = 11;
	const Saarland = 12;
	const Sachsen = 13;
	const Sachsen_Anhalt = 14;
	const Schleswig_Holstein = 15;
	const Thueringen = 16;

	public static function Count() {
		return count(self::$ShortNames);
	}

	public static function GetName(int $id, bool $short = false) {
		if ($id >= 1 && $id <= self::Count())
			return ($short) ? self::$ShortNames[$id - 1] : self::$LongNames[$id - 1];
		else
			return '';
	}

	public static function GetIdByCode(string $code) {
		$n = array_search($code, self::$ShortNames);
		if ($n !== false)
			return $n + 1;
		else
			return 0;
	}
}

class Feiertag {
	private $datum;
	private $name;
	private $laender;

	public function __construct(int $tag, string $name, array $laender = array()) {
		$this->datum = mktime(0, 0, 0, date('m', $tag), date('d', $tag), date('Y', $tag));
		$this->name = $name;
		$this->laender = array_unique($laender);
		sort($this->laender);
	}

	public function GetName() {
		return $this->name;
	}

	public function GetDatum(string $format, string $expr = 'now') {
		return date($format, strtotime($expr, $this->datum));
	}

	public function IsGesetzlich() {
		if (count($this->laender) > 0)
			return true;
		else
			return false;
	}

	public function IsBundesweit() {
		if (count($this->laender) == Bundesland::Count())
			return true;
		else
			return false;
	}

	public function IsInBundesland(int $landId) {
		return in_array($landId, $this->laender);
	}

	public function GetBundeslaender(string $sep = ', ', bool $short = false) {
		$s = '';
		for ($i = 0; $i < count($this->laender); $i++) {
			if (strlen($s) > 0)
				$s .= $sep;
			$s .= Bundesland::GetName($this->laender[$i], $short);
		}
		return $s;
	}

	public function GetVEvent() {
		$s = "BEGIN:VEVENT\r\n"
			. 'UID:' . uniqid(get_class()) . "\r\n"
			. "DTSTAMP:{$this->GetDatum('Ymd\THis\Z')}\r\n"
			. "DTSTART;VALUE=DATE:{$this->GetDatum('Ymd')}\r\n"
			. "DTEND;VALUE=DATE:{$this->GetDatum('Ymd', '+1 day')}\r\n"
			. 'SUMMARY:' . addcslashes($this->name, ',\\;') . "\r\n"
			. 'TRANSP:TRANSPARENT' . "\r\n";
		if ($this->IsGesetzlich()) {
			$s .= 'DESCRIPTION:Gesetzlicher Feiertag';
			if (!$this->IsBundesweit())
				$s .= ' in ' . addcslashes($this->GetBundeslaender(), ',\\;');
			$s .= "\r\n";
		}
		$s .= "END:VEVENT\r\n";
		return $s;
	}

	public function __toString() {
		return $this->GetDatum('Y-m-d') . ';' . $this->name . ';' . $this->GetBundeslaender(',', true);
	}
}

class FeiertagKalender {
	private $jahr;
	private $feiertage = array();

	private function calcOstersonntag(int $jahr) {
		// Osterformel nach Butcher
		$a = $jahr % 19;
		$b = (int) ($jahr / 100);
		$c = $jahr % 100;
		$d = (int) ($b / 4);
		$e = $b % 4;
		$f = (int) (($b + 8) / 25);
		$g = (int) (($b - $f + 1) / 3);
		$h = (19 * $a + $b - $d - $g + 15) % 30;
		$i = (int) ($c / 4);
		$j = $c % 4;
		$k = (32 + 2 * $e + 2 * $i - $h - $j) % 7;
		$l = (int) (($a + 11 * $h + 22 * $k) / 451);
		$m = $h + $k - 7 * $l + 114;
		$monat = (int) ($m / 31);
		$tag = ($m % 31) + 1;
		return mktime(0, 0, 0, $monat, $tag, $jahr);
	}

	public function __construct(int $jahr) {
		$this->jahr = $jahr;
		$alle = array(Bundesland::Baden_Wuerttemberg, Bundesland::Bayern, Bundesland::Berlin, Bundesland::Brandenburg,
				Bundesland::Bremen, Bundesland::Hamburg, Bundesland::Hessen, Bundesland::Mecklenburg_Vorpommern,
				Bundesland::Niedersachsen, Bundesland::Nordrhein_Westfalen, Bundesland::Rheinland_Pfalz,
				Bundesland::Saarland, Bundesland::Sachsen, Bundesland::Sachsen_Anhalt,
				Bundesland::Schleswig_Holstein, Bundesland::Thueringen);
		$ostersonntag = $this->calcOstersonntag($jahr);
		$busstag = mktime(0, 0, 0, 11, 22 - ($jahr - 1 + $jahr / 4) % 7, $jahr);
		$jahreszeiten = new Season();
		$jahreszeiten->calc($jahr);
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 1, 1, $jahr), 'Neujahr', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 1, 6, $jahr), 'Heilige Drei Könige',
					array(Bundesland::Baden_Wuerttemberg, Bundesland::Bayern, Bundesland::Sachsen_Anhalt)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 2, 14, $jahr), 'Valentinstag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 3, 8, $jahr), 'Internationaler Frauentag', array(Bundesland::Berlin, Bundesland::Mecklenburg_Vorpommern)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 3, 17, $jahr), 'Saint Patrick\'s Day'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 4, 7, $jahr), 'Weltgesundheitstag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 4, 30, $jahr), 'Walpurgisnacht'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 5, 1, $jahr), 'Tag der Arbeit', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 5, 8, $jahr), 'Tag der Befreiung'));
		array_push($this->feiertage, new Feiertag(strtotime('-52 days', $ostersonntag), 'Weiberfastnacht'));
		array_push($this->feiertage, new Feiertag(strtotime('-48 days', $ostersonntag), 'Rosenmontag'));
		array_push($this->feiertage, new Feiertag(strtotime('-47 days', $ostersonntag), 'Fastnacht'));
		array_push($this->feiertage, new Feiertag(strtotime('-46 days', $ostersonntag), 'Aschermittwoch'));
		array_push($this->feiertage, new Feiertag(strtotime('-7 days', $ostersonntag), 'Palmsonntag'));
		array_push($this->feiertage, new Feiertag(strtotime('-3 days', $ostersonntag), 'Gründonnerstag'));
		array_push($this->feiertage, new Feiertag(strtotime('-2 days', $ostersonntag), 'Karfreitag', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('-1 day', $ostersonntag), 'Karsamstag'));
		array_push($this->feiertage, new Feiertag($ostersonntag, 'Ostersonntag', array(Bundesland::Brandenburg, Bundesland::Hessen)));
		array_push($this->feiertage, new Feiertag(strtotime('+1 day', $ostersonntag), 'Ostermontag', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('+7 days', $ostersonntag), 'Weißer Sonntag'));
		array_push($this->feiertage, new Feiertag(strtotime('+39 days', $ostersonntag), 'Christi Himmelfahrt', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('+49 days', $ostersonntag), 'Pfingstsonntag', array(Bundesland::Brandenburg, Bundesland::Hessen)));
		array_push($this->feiertage, new Feiertag(strtotime('+50 days', $ostersonntag), 'Pfingstmontag', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('+60 days', $ostersonntag), 'Fronleichnam',
					array(Bundesland::Baden_Wuerttemberg, Bundesland::Bayern, Bundesland::Hessen,
						Bundesland::Nordrhein_Westfalen, Bundesland::Rheinland_Pfalz, Bundesland::Saarland)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 5, 14 - ($jahr - 1 + $jahr / 4) % 7, $jahr), 'Muttertag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 6, 1, $jahr), 'Internationaler Kindertag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 6, 5, $jahr), 'Weltumwelttag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 9, 20, $jahr), 'Weltkindertag', array(Bundesland::Thueringen)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 8, 15, $jahr), 'Mariä Himmelfahrt',
					array(Bundesland::Bayern, Bundesland::Saarland)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 3, $jahr), 'Tag der Deutschen Einheit', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 16, $jahr), 'Welternährungstag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 31, $jahr), 'Halloween'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 31, $jahr), 'Reformationstag',
					array(Bundesland::Brandenburg, Bundesland::Mecklenburg_Vorpommern, Bundesland::Sachsen,
						Bundesland::Sachsen_Anhalt, Bundesland::Thueringen, Bundesland::Bremen, Bundesland::Hamburg,
						Bundesland::Niedersachsen, Bundesland::Schleswig_Holstein)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 11, 1, $jahr), 'Allerheiligen',
					array(Bundesland::Baden_Wuerttemberg, Bundesland::Bayern, Bundesland::Nordrhein_Westfalen,
						Bundesland::Rheinland_Pfalz, Bundesland::Saarland)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 11, 2, $jahr), 'Allerseelen'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 7 - ($jahr + 5 + $jahr / 4) % 7, $jahr), 'Erntedankfest'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 11, 11, $jahr), 'Martinstag'));
		array_push($this->feiertage, new Feiertag(strtotime('-3 days', $busstag), 'Volkstrauertag'));
		array_push($this->feiertage, new Feiertag(strtotime('+4 days', $busstag), 'Totensonntag'));
		array_push($this->feiertage, new Feiertag($busstag, 'Buß- und Bettag', array(Bundesland::Sachsen)));
		array_push($this->feiertage, new Feiertag(strtotime('+11 days', $busstag), '1. Advent'));
		array_push($this->feiertage, new Feiertag(strtotime('+18 days', $busstag), '2. Advent'));
		array_push($this->feiertage, new Feiertag(strtotime('+25 days', $busstag), '3. Advent'));
		array_push($this->feiertage, new Feiertag(strtotime('+32 days', $busstag), '4. Advent'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 25, $jahr), '1. Weihnachtstag', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 26, $jahr), '2. Weihnachtstag', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 6, $jahr), 'Nikolaus'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 21, $jahr), 'Thomastag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 24, $jahr), 'Heiliger Abend'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 31, $jahr), 'Silvester'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 3, 31 - ($jahr + 4 + $jahr / 4) % 7, $jahr), 'Sommerzeit (+1h)'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 31 - ($jahr + 1 + $jahr / 4) % 7, $jahr), 'Winterzeit (-1h)'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 6, 14 - ($jahr + 2 + $jahr / 4) % 7, $jahr), 'Tag des Eisenbahners'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 7, 7 - ($jahr + 4 + $jahr / 4) % 7, $jahr), 'Tag des Bergmanns'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 7, 31 - ($jahr + 2 + $jahr / 4) % 7, $jahr), 'System Administrator Appreciation Day'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 3, 31, $jahr), 'World Backup Day'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 4, 22, $jahr), 'Tag der Erde'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 6, 12, $jahr), 'Anne Frank Tag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 8, 13, $jahr), 'Internationaler Linkshändertag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 9, 28, $jahr), 'Safe Abortion Day'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 4, $jahr), 'Welttierschutztag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 11, 25, $jahr), 'Internationaler Tag gegen Gewalt an Frauen'));
		// array_push($this->feiertage, new Feiertag($jahreszeiten->get(Season::Spring), 'Frühlingsanfang'));
		// array_push($this->feiertage, new Feiertag($jahreszeiten->get(Season::Summer), 'Sommeranfang'));
		// array_push($this->feiertage, new Feiertag($jahreszeiten->get(Season::Autumn), 'Herbstanfang'));
		// array_push($this->feiertage, new Feiertag($jahreszeiten->get(Season::Winter), 'Winteranfang'));
		sort($this->feiertage);
	}

	public function Count() {
		return count($this->feiertage);
	}

	public function GetFeiertag(int $n) {
		return $this->feiertage[$n];
	}

	private function GetHeader() {
		return "BEGIN:VCALENDAR\r\n"
			. "VERSION:2.0\r\n"
			. "PRODID:-//{$_SERVER['SERVER_NAME']}//" . get_class() . "//DE\r\n";
	}

	private function GetFooter() {
		return "END:VCALENDAR\r\n";
	}

	public function GetVCalendar(int $landId = 0) {
		$s = $this->GetHeader();
		for ($i = 0; $i < count($this->feiertage); $i++)
			if ($landId == 0 || !$this->feiertage[$i]->IsGesetzlich() || $this->feiertage[$i]->IsInBundesland($landId))
				$s .= $this->feiertage[$i]->GetVEvent();
		$s .= $this->GetFooter();
		return $s;
	}

	public function __toString() {
		$s = '';
		for ($i = 0; $i < count($this->feiertage); $i++)
			$s .= $this->feiertage[$i] . "\r\n";
		return $s;
	}
}

setlocale(LC_TIME, 'de_DE.utf8');
$now = date('Y');
$jahr = $now;
if (isset($_GET['jahr']) && is_numeric($_GET['jahr']))
	$jahr = max(2000, min(2099, intval($_GET['jahr'])));
$tage = new FeiertagKalender($jahr);
if (isset($_GET['jahr'])) {
	if (!isset($_GET['raw'])) {
		$landId = 0;
		if (isset($_GET['land']) && is_string($_GET['land']))
			$landId = Bundesland::GetIdByCode(strval($_GET['land']));
		$landName = Bundesland::GetName($landId, true);
		header('Content-Type: text/calendar; charset=utf-8');
		header("Content-Disposition: inline; filename=\"Feiertage{$landName}{$jahr}.ics\"");
		echo $tage->GetVCalendar($landId);
	}
	else {
		header('Content-Type: text/plain; charset=utf-8');
		echo $tage;
	}
	exit(0);
}
?>
<!doctype html>
<html lang="de">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="title" content="Feiertage in Deutschland | Kalender">
		<meta name="author" content="Steffen Lange">
		<meta name="description" content="iCal-Kalenderdatei mit bundes- und landesweiten Feiertagen für ausgewähltes Jahr zum Import in alle gängigen Kalenderprogramme herunterladen.">
		<meta property="og:type" content="website">
		<meta property="og:url" content="https://www.feiertage-kalender.de/">
		<meta property="og:title" content="Feiertage in Deutschland | Kalender">
		<meta property="og:description" content="iCal-Kalenderdatei mit bundes- und landesweiten Feiertagen für ausgewähltes Jahr zum Import in alle gängigen Kalenderprogramme herunterladen.">
		<meta property="og:image" content="https://www.feiertage-kalender.de/img/screenshot.jpg">
		<meta property="twitter:card" content="summary">
		<meta property="twitter:url" content="https://www.feiertage-kalender.de/">
		<meta property="twitter:title" content="Feiertage in Deutschland | Kalender">
		<meta property="twitter:description" content="iCal-Kalenderdatei mit bundes- und landesweiten Feiertagen für ausgewähltes Jahr zum Import in alle gängigen Kalenderprogramme herunterladen.">
		<meta property="twitter:image" content="https://www.feiertage-kalender.de/img/screenshot.jpg">
		<link rel="icon" href="favicon.ico">
		<link rel="stylesheet" type="text/css" href="assets/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="assets/datatables.min.css">
		<script src="assets/jquery-3.7.1.min.js"></script>
		<script src="assets/datatables.min.js"></script>
		<script src="assets/bootstrap.bundle.min.js"></script>
		<script>
			$(document).ready(function() {
				$('[data-toggle="tooltip"]').tooltip();
				$("#feiertage").DataTable( {
					language: { url: "assets/de-DE.json" },
					ordering: false,
					searching: false,
					lengthChange: false
				} );
			});
		</script>
		<title>Feiertagskalender <?php echo $now; ?> f&uuml;r Deutschland</title>
	</head>
	<body><div class="container p-3 text-center">
		<h1 class="my-4">Kalender <?php echo $now; ?> &ndash; Feiertage in Deutschland</h1>
		<p class="text-start">
			iCal-Kalenderdatei mit bundes- und landesweiten <a href="https://de.m.wikipedia.org/wiki/Gesetzliche_Feiertage_in_Deutschland" title="&Uuml;bersicht aller gesetzlichen Feiertage" rel="nofollow">Feiertagen</a> sowie <a href="https://de.m.wikipedia.org/wiki/Liste_von_Gedenk-_und_Aktionstagen" title="Liste von Gedenk- und Aktionstagen" rel="nofollow">Gedenk- und Aktionstagen</a> f&uuml;r ausgew&auml;hltes Jahr zum Import in alle g&auml;ngigen Kalenderprogramme herunterladen.
			iCal bzw. iCalendar ist ein standardisiertes Datenformat zum Austausch von Kalenderinhalten. Das Format wird von der Mehrzahl der Kalenderprogramme unterst&uuml;tzt, die webbasierte Kalenderdaten einbinden k&ouml;nnen,
			u.a. <a href="https://support.google.com/calendar/answer/37118" title="Termine in Google Kalender importieren" target="_blank" rel="nofollow">Google Kalender</a>, <a href="https://support.microsoft.com/de-de/office/importieren-oder-abonnieren-eines-kalenders-in-outlook-com-cff1429c-5af6-41ec-a5b4-74f2c278e98c" title="Importieren oder Abonnieren eines Kalenders in Outlook.com oder Outlook im Web" target="_blank" rel="nofollow">Microsoft Outlook</a>, <a href="https://support.mozilla.org/de/kb/Ferienkalender-hinzufuegen" title="Feiertags- und Schulferienkalender hinzuf&uuml;gen" target="_blank" rel="nofollow">Mozilla Thunderbird</a>, <a href="https://support.apple.com/de-de/guide/iphone/iph3d1110d4/ios#iph30203de42" title="Verwenden mehrerer Kalender auf dem iPhone" target="_blank" rel="nofollow">iPhone Kalender</a> und <a href="https://support.apple.com/de-de/guide/calendar/icl1023/mac" title="Exportieren oder Importieren von Kalendern auf dem Mac" target="_blank" rel="nofollow">macOS Kalender</a>.
		</p>
		<hr>
		<form>
			<div class="row">
				<div class="col"><div class="form-floating">
					<select class="form-select" id="jahr" name="jahr">
						<option selected><?php echo $now; ?></option>
<?php
	for ($i = 1; $i < 5; $i++)
		echo "\t\t\t\t\t\t<option>" . strval($now + $i) . '</option>' . "\n";
?>
					</select>
					<label for="jahr">Kalenderjahr</label>
				</div></div>
				<div class="col"><div class="form-floating">
					<select class="form-select" id="land" name="land">
						<option selected>Deutschland</option>
<?php
	for ($i = 1; $i <= Bundesland::Count(); $i++)
		echo "\t\t\t\t\t\t" . '<option value="' . Bundesland::GetName($i, true) . '">' . htmlentities(Bundesland::GetName($i)) . '</option>' . "\n";
?>
					</select>
					<label for="land">Bundesland</label>
				</div></div>
				<div class="col d-grid">
					<button type="submit" class="btn btn-primary">Download</button>
				</div>
			</div>
		</form>
		<hr>
		<table id="feiertage" class="table table-striped table-sm">
			<thead class="table-light">
				<tr>
					<th><?php echo $now; ?></th>
					<th>Feiertag</th>
<?php
	for ($i = 1; $i <= Bundesland::Count(); $i++)
		echo "\t\t\t\t\t" . '<th><abbr data-toggle="tooltip" title="' . htmlentities(Bundesland::GetName($i)) . '">' . Bundesland::GetName($i, true) . '</abbr></th>' . "\n";
?>
				</tr>
			</thead>
			<tbody>
<?php
	for ($i = 0; $i < $tage->Count(); $i++) {
		$tag = $tage->GetFeiertag($i);
		echo "\t\t\t\t<tr>";
		echo '<td data-toggle="tooltip" title="' . strftime('%A', $tag->GetDatum('U')) . '">' . $tag->GetDatum('d.m.') . '</td><td>' . htmlentities($tag->GetName()) . '</td>' . "\n\t\t\t\t";
		for ($j = 1; $j <= Bundesland::Count(); $j++) {
			echo '<td>';
			if ($tag->IsInBundesland($j))
				echo '&bigstar;';
			else
				echo '&star;';
			echo '</td>';
		}
		echo '</tr>' . "\n";
	}
?>
			</tbody>
		</table>
		<hr>
		<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=M4Z52Q9299MCQ&amp;source=url" title="Mit PayPal spenden" rel="noopener"><img alt="Mit PayPal spenden" title="Mit PayPal spenden" src="assets/btn_donateCC_LG.gif" width="126" height="47"></a></p>
		<p class="text-end">&copy; 2018-<?php echo $now; ?> <a href="https://steffen.lange.tel/" title="Steffen Lange">Steffen Lange</a> | Alle Angaben ohne Gew&auml;hr. | <a rel="nofollow" href="https://github.com/stelas/Feiertage" title="Quellcode">Quellcode</a> | <a href="https://www.dateihal.de/cms/imprint" title="Kontakt">Kontakt</a> | <a href="https://www.dateihal.de/cms/privacy" title="Datenschutz">Datenschutz</a></p>
	</div></body>
</html>
