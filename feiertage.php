<?php

abstract class Bundesland {
	private static $Namen = array('Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg',
					'Bremen', 'Hamburg', 'Hessen', 'Mecklenburg-Vorpommern',
					'Niedersachsen', 'Nordrhein-Westfalen', 'Rheinland-Pfalz',
					'Saarland', 'Sachsen', 'Sachsen-Anhalt',
					'Schleswig-Holstein', 'Thüringen');

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

	public function GetName(int $land) {
		if ($land >= 0 && $land < self::Count())
			return self::$Namen[$land];
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

	public function GetBundeslaender() {
		$s = '';
		for ($i = 0; $i < count($this->laender); $i++) {
			if (strlen($s) > 0)
				$s .= ', ';
			$s .= Bundesland::GetName($this->laender[$i]);
		}
		return $s;
	}

	public function GetVEvent() {
		$s = "BEGIN:VEVENT\r\n"
			. 'UID:' . uniqid(get_class()) . "\r\n"
			. "DTSTAMP:{$this->GetDatum(true)}\r\n"
			. "DTSTART;VALUE=DATE:{$this->GetDatum()}\r\n"
			. "DTEND;VALUE=DATE:{$this->GetDatum()}\r\n"
			. 'SUMMARY:' . addcslashes($this->name, ',\\;') . "\r\n";
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
		return date('Y-m-d', $this->datum) . ' ' . $this->name;
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
		array_push($this->feiertage, new Feiertag(mktime(0, 0, 0, 9, 20, $jahr), 'Weltkindertag'));
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

if (isset($_GET['jahr']) && is_numeric($_GET['jahr'])) {
	$jahr = max(2000, min(2099, intval($_GET['jahr'])));
	$feiertage = new FeiertagKalender($jahr);
	if (!isset($_GET['raw'])) {
		header('Content-Type: text/calendar; charset=utf-8');
		header("Content-Disposition: inline; filename=\"{$jahr}.ics\"");
		echo $feiertage->GetVCalendar();
	}
	else {
		header('Content-Type: text/plain; charset=utf-8');
		echo $feiertage;
	}
}
else {
	echo "<!DOCTYPE html>\r\n"
		. "<html lang=\"de\">\r\n"
		. "<head><meta charset=\"utf-8\"><title>Feiertage in Deutschland</title></head>\r\n"
		. "<body>\r\n"
		. "<b>Feiertage als iCal-Datei herunterladen</b>\r\n"
		. "<form method=\"get\">\r\n"
		. "<label>Jahr:\r\n"
		. "<select name=\"jahr\">\r\n";
	$year = date('Y');
	for ($i = 0; $i < 5; $i++)
		echo "<option>" . strval($year + $i) . "</option>\r\n";
	echo "</select>\r\n"
		. "</label><br>\r\n"
		. "<button type=\"submit\">Download</button>\r\n"
		. "</form>\r\n"
		. "</body>\r\n"
		. "</html>";
}

?>
