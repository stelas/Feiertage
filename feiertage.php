<?php

abstract class Bundesland {
	private static $Namen = array(array('Baden-Württemberg', 'BW'), array('Bayern', 'BY'), array('Berlin', 'BE'), array('Brandenburg', 'BB'),
					array('Bremen', 'HB'), array('Hamburg', 'HH'), array('Hessen', 'HE'), array('Mecklenburg-Vorpommern', 'MV'),
					array('Niedersachsen', 'NI'), array('Nordrhein-Westfalen', 'NW'), array('Rheinland-Pfalz', 'RP'),
					array('Saarland', 'SL'), array('Sachsen', 'SN'), array('Sachsen-Anhalt', 'ST'),
					array('Schleswig-Holstein', 'SH'), array('Thüringen', 'TH'));

	const Baden_Wuerttemberg = 0;
	const Bayern = 1;
	const Berlin = 2;
	const Brandenburg = 3;
	const Bremen = 4;
	const Hamburg = 5;
	const Hessen = 6;
	const Mecklenburg_Vorpommern = 7;
	const Niedersachsen = 8;
	const Nordrhein_Westfalen = 9;
	const Rheinland_Pfalz = 10;
	const Saarland = 11;
	const Sachsen = 12;
	const Sachsen_Anhalt = 13;
	const Schleswig_Holstein = 14;
	const Thueringen = 15;

	public function Count() {
		return count(self::$Namen);
	}

	public function GetName(int $land, bool $code = false) {
		if ($land >= 0 && $land < self::Count())
			return ($code) ? self::$Namen[$land][1] : self::$Namen[$land][0];
		else
			return '';
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

	public function GetDatum(bool $lang = false) {
		if ($lang)
			return date('Ymd\THis\Z', $this->datum);
		else
			return date('Ymd', $this->datum);
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

	public function IsInBundesland(int $land) {
		return in_array($land, $this->laender);
	}

	public function GetBundeslaender(bool $code = false) {
		$s = '';
		for ($i = 0; $i < count($this->laender); $i++) {
			if (strlen($s) > 0)
				$s .= ', ';
			$s .= Bundesland::GetName($this->laender[$i], $code);
		}
		return $s;
	}

	public function GetVEvent() {
		$s = "BEGIN:VEVENT\r\n"
			. 'UID:' . uniqid(get_class()) . "\r\n"
			. "DTSTAMP:{$this->GetDatum(true)}\r\n"
			. "DTSTART;VALUE=DATE:{$this->GetDatum()}\r\n"
			. "DTEND;VALUE=DATE:{$this->GetDatum()}\r\n"
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
		return date('Y-m-d', $this->datum) . "\t" . $this->name . "\t" . $this->GetBundeslaender(true);
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
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 1, 1, $jahr), 'Neujahr', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 1, 6, $jahr), 'Heilige Drei Könige',
					array(Bundesland::Baden_Wuerttemberg, Bundesland::Bayern, Bundesland::Sachsen_Anhalt)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 2, 14, $jahr), 'Valentinstag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 3, 8, $jahr), 'Internationaler Frauentag', array(Bundesland::Berlin)));
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
		array_push($this->feiertage, new Feiertag($ostersonntag, 'Ostersonntag', array(Bundesland::Brandenburg)));
		array_push($this->feiertage, new Feiertag(strtotime('+1 day', $ostersonntag), 'Ostermontag', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('+7 days', $ostersonntag), 'Weißer Sonntag'));
		array_push($this->feiertage, new Feiertag(strtotime('+39 days', $ostersonntag), 'Christi Himmelfahrt', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('+49 days', $ostersonntag), 'Pfingstsonntag', array(Bundesland::Brandenburg)));
		array_push($this->feiertage, new Feiertag(strtotime('+50 days', $ostersonntag), 'Pfingstmontag', $alle));
		array_push($this->feiertage, new Feiertag(strtotime('+60 days', $ostersonntag), 'Fronleichnam',
					array(Bundesland::Baden_Wuerttemberg, Bundesland::Bayern, Bundesland::Hessen,
						Bundesland::Nordrhein_Westfalen, Bundesland::Rheinland_Pfalz, Bundesland::Saarland)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 5, 14 - ($jahr - 1 + $jahr / 4) % 7, $jahr), 'Muttertag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 6, 1, $jahr), 'Internationaler Kindertag'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 9, 20, $jahr), 'Weltkindertag', array(Bundesland::Thueringen)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 8, 15, $jahr), 'Mariä Himmelfahrt',
					array(Bundesland::Bayern, Bundesland::Saarland)));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 3, $jahr), 'Tag der Deutschen Einheit', $alle));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 31, $jahr), 'Halloween'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 31, $jahr), 'Reformationstag',
					array(Bundesland::Brandenburg, Bundesland::Mecklenburg_Vorpommern, Bundesland::Sachsen,
						Bundesland::Sachsen_Anhalt, Bundesland::Thueringen)));
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
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 24, $jahr), 'Heiliger Abend'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 12, 31, $jahr), 'Silvester'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 3, 31 - ($jahr + 4 + $jahr / 4) % 7, $jahr), 'Sommerzeit (+1h)'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 10, 31 - ($jahr + 1 + $jahr / 4) % 7, $jahr), 'Winterzeit (-1h)'));
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 7, 31 - ($jahr + 2 + $jahr / 4) % 7, $jahr), 'System Administrator Appreciation Day'));
		sort($this->feiertage);
	}

	private function GetHeader() {
		return "BEGIN:VCALENDAR\r\n"
			. "VERSION:2.0\r\n"
			. "PRODID:-//{$_SERVER['SERVER_NAME']}//" . get_class() . "//DE\r\n";
	}

	private function GetFooter() {
		return "END:VCALENDAR\r\n";
	}

	public function GetVCalendar() {
		$s = $this->GetHeader();
		for ($i = 0; $i < count($this->feiertage); $i++)
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

$year = date('Y');
if (isset($_GET['jahr'])) {
	if (is_numeric($_GET['jahr']))
		$jahr = max(2000, min(2099, intval($_GET['jahr'])));
	else
		$jahr = $year;
	$feiertage = new FeiertagKalender($jahr);
	if (!isset($_GET['raw'])) {
		header('Content-Type: text/calendar; charset=utf-8');
		header("Content-Disposition: inline; filename=\"Feiertage{$jahr}.ics\"");
		echo $feiertage->GetVCalendar();
	}
	else {
		header('Content-Type: text/plain; charset=utf-8');
		echo $feiertage;
	}
}
else {
	echo '<!doctype html>
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
    <link rel="stylesheet" type="text/css" href="bootstrap.min.css">
    <title>Feiertage | Kalender</title>
  </head>
  <body>
    <div class="container p-5 text-center">
      <h2 class="mb-4">Feiertage in Deutschland</h2>
      <p>iCal<sup>1</sup>-Kalenderdatei mit bundes- und landesweiten Feiertagen f&uuml;r ausgew&auml;hltes Jahr zum Import in alle g&auml;ngigen Kalenderprogramme herunterladen.</p>
      <p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=M4Z52Q9299MCQ&source=url" target="_blank" rel="noopener"><img alt="Mit PayPal spenden" src="btn_donateCC_LG.gif" width="126" height="47"></a></p>
      <form class="border border-light">
        <div class="form-floating">
          <select class="form-select mb-2" id="jahr" name="jahr">
            ';
	for ($i = 0; $i < 5; $i++)
		echo '<option>' . strval($year + $i) . '</option>';
	echo '
          </select>
          <label for="jahr">Kalenderjahr</label>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary mb-2">Download</button>
        </div>
      </form>
      <ol class="text-start"><li>iCal bzw. iCalendar ist ein standardisiertes Datenformat zum Austausch von Kalenderinhalten. Das Format wird von der Mehrzahl der Kalenderprogramme unterst&uuml;tzt, die webbasierte Kalenderdaten einbinden k&ouml;nnen, u.a. <a href="https://support.google.com/calendar/answer/37100" target="_blank" rel="noopener">Google Kalender</a>, <a href="https://support.microsoft.com/de-de/office/importieren-oder-abonnieren-eines-kalenders-in-outlook-com-cff1429c-5af6-41ec-a5b4-74f2c278e98c" target="_blank" rel="noopener">Microsoft Outlook</a>, <a href="https://support.mozilla.org/de/kb/Ferienkalender-hinzufuegen" target="_blank" rel="noopener">Mozilla Thunderbird</a>, <a href="https://support.apple.com/de-de/guide/iphone/iph3d1110d4/ios#iph30203de42" target="_blank" rel="noopener">iPhone Kalender</a> und <a href="https://support.apple.com/de-de/HT202361" target="_blank" rel="noopener">macOS Kalender</a>.</li></ol>
      <p class="text-end">&copy; Steffen Lange | Alle Angaben ohne Gew&auml;hr. | <a href="https://www.dateihal.de/cms/imprint">Impressum</a> | <a href="https://www.dateihal.de/cms/privacy">Datenschutz</a></p>
    </div>
  </body>
</html>';
}

?>
