<?php

/**
 * Dates of seasons for any year
 * Credit: P. Rocher/G. Satre/IMCCE/CNRS
 * https://www.imcce.fr/en/grandpublic/temps/saisons.php
 * https://promenade.imcce.fr/en/pages4/439.html
 */

class Season {
	const Spring = 0;
	const Summer = 1;
	const Autumn = 2;
	const Winter = 3;

	private $JJD;
	private $Year;
	private $Month;
	private $Day;
	private $Dates = array();

	public function get($n) {
		return $this->Dates[$n];
	}

	private function trunc($x) {
		if ($x > 0.0)
			return floor($x);
		else
			return ceil($x);
	}

	private function JJDATEJ() {
		$z1 = $this->JJD + 0.5;
		$z = $this->trunc($z1);
		$a = $z;
		$b = $a + 1524;
		$c = $this->trunc(($b - 122.1) / 365.25);
		$d = $this->trunc(365.25 * $c);
		$e = $this->trunc(($b - $d) / 30.6001);
		$this->Day = $this->trunc($b - $d - $this->trunc(30.6001 * $e));
		if ($e < 13.5)
			$this->Month = $this->trunc($e - 1);
		else
			$this->Month = $this->trunc($e - 13);
		if ($this->Month >= 3)
			$this->Year = $this->trunc($c - 4716);
		else
			$this->Year = $this->trunc($c - 4715);
	}

	private function JJDATE() {
		$z1 = $this->JJD + 0.5;
		$z = $this->trunc($z1);
		if ($z < 2299161)
			$a = $z;
		else {
			$alpha = $this->trunc(($z - 1867216.25) / 36524.25);
			$a = $z + 1 + $alpha - $this->trunc($alpha / 4);
		}
		$b = $a + 1524;
		$c = $this->trunc(($b - 122.1) / 365.25);
		$d = $this->trunc(365.25 * $c);
		$e = $this->trunc(($b - $d) / 30.6001);
		$this->Day = $this->trunc($b - $d - $this->trunc(30.6001 * $e));
		if ($e < 13.5)
			$this->Month = $this->trunc($e - 1);
		else
			$this->Month = $this->trunc($e - 13);
		if ($this->Month >= 3)
			$this->Year = $this->trunc($c - 4716);
		else
			$this->Year = $this->trunc($c - 4715);
	}

	private function conv($n) {
		$fdj = ($this->JJD + 0.5E0) - floor($this->JJD + 0.5E0);
		$hh = floor($fdj * 24);
		$fdj -= $hh / 24.0;
		$mm = floor($fdj * 1440);
		$tz = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$this->Dates[$n] = mktime($hh, $mm, 0, $this->Month, $this->Day, $this->Year);
		date_default_timezone_set($tz);
	}

	public function calc($yy) {
		$code1 = $yy;
		$nline = 1;
		$k = $yy - 2000 - 1;
		for ($n = 0; $n < 8; $n++) {
			$nn = $n % 4;
			$dk = $k + 0.25E0 * $n;
			$t = 0.21451814e0 + 0.99997862442e0 * $dk
				+ 0.00642125e0 * sin(1.580244e0 + 0.0001621008e0 * $dk)
				+ 0.00310650e0 * sin(4.143931e0 + 6.2829005032e0 * $dk)
				+ 0.00190024e0 * sin(5.604775e0 + 6.2829478479e0 * $dk)
				+ 0.00178801e0 * sin(3.987335e0 + 6.2828291282e0 * $dk)
				+ 0.00004981e0 * sin(1.507976e0 + 6.2831099520e0 * $dk)
				+ 0.00006264e0 * sin(5.723365e0 + 6.2830626030e0 * $dk)
				+ 0.00006262e0 * sin(5.702396e0 + 6.2827383999e0 * $dk)
				+ 0.00003833e0 * sin(7.166906e0 + 6.2827857489e0 * $dk)
				+ 0.00003616e0 * sin(5.581750e0 + 6.2829912245e0 * $dk)
				+ 0.00003597e0 * sin(5.591081e0 + 6.2826670315e0 * $dk)
				+ 0.00003744e0 * sin(4.3918e0 + 12.56578830e0 * $dk)
				+ 0.00001827e0 * sin(8.3129e0 + 12.56582984e0 * $dk)
				+ 0.00003482e0 * sin(8.1219e0 + 12.56572963e0 * $dk)
				- 0.00001327e0 * sin(-2.1076e0 + 0.33756278e0 * $dk)
				- 0.00000557e0 * sin(5.549e0 + 5.7532620e0 * $dk)
				+ 0.00000537e0 * sin(1.255e0 + 0.0033930e0 * $dk)
				+ 0.00000486e0 * sin(19.268e0 + 77.7121103e0 * $dk)
				- 0.00000426e0 * sin(7.675e0 + 7.8602511e0 * $dk)
				- 0.00000385e0 * sin(2.911e0 + 0.0005412e0 * $dk)
				- 0.00000372e0 * sin(2.266e0 + 3.9301258e0 * $dk)
				- 0.00000210e0 * sin(4.785e0 + 11.5065238e0 * $dk)
				+ 0.00000190e0 * sin(6.158e0 + 1.5774000e0 * $dk)
				+ 0.00000204e0 * sin(0.582e0 + 0.5296557e0 * $dk)
				- 0.00000157e0 * sin(1.782e0 + 5.8848012e0 * $dk)
				+ 0.00000137e0 * sin(-4.265e0 + 0.3980615e0 * $dk)
				- 0.00000124e0 * sin(3.871e0 + 5.2236573e0 * $dk)
				+ 0.00000119e0 * sin(2.145e0 + 5.5075293e0 * $dk)
				+ 0.00000144e0 * sin(0.476e0 + 0.0261074e0 * $dk)
				+ 0.00000038e0 * sin(6.45e0 + 18.848689e0 * $dk)
				+ 0.00000078e0 * sin(2.80e0 + 0.775638e0 * $dk)
				- 0.00000051e0 * sin(3.67e0 + 11.790375e0 * $dk)
				+ 0.00000045e0 * sin(-5.79e0 + 0.796122e0 * $dk)
				+ 0.00000024e0 * sin(5.61e0 + 0.213214e0 * $dk)
				+ 0.00000043e0 * sin(7.39e0 + 10.976868e0 * $dk)
				- 0.00000038e0 * sin(3.10e0 + 5.486739e0 * $dk)
				- 0.00000033e0 * sin(0.64e0 + 2.544339e0 * $dk)
				+ 0.00000033e0 * sin(-4.78e0 + 5.573024e0 * $dk)
				- 0.00000032e0 * sin(5.33e0 + 6.069644e0 * $dk)
				- 0.00000021e0 * sin(2.65e0 + 0.020781e0 * $dk)
				- 0.00000021e0 * sin(5.61e0 + 2.942400e0 * $dk)
				+ 0.00000019e0 * sin(-0.93e0 + 0.000799e0 * $dk)
				- 0.00000016e0 * sin(3.22e0 + 4.694014e0 * $dk)
				+ 0.00000016e0 * sin(-3.59e0 + 0.006829e0 * $dk)
				- 0.00000016e0 * sin(1.96e0 + 2.146279e0 * $dk)
				- 0.00000016e0 * sin(5.92e0 + 15.720504e0 * $dk)
				+ 0.00000115e0 * sin(23.671e0 + 83.9950108e0 * $dk)
				+ 0.00000115e0 * sin(17.845e0 + 71.4292098e0 * $dk);
			$jjd = 2451545 + $t * 365.25e0;
			$jjd += 0.0003472222e0; // add 30s for rounding to nearest minute
			$d = $code1 / 100.0;
			$tetuj = (32.23e0 * ($d - 18.30e0) * ($d - 18.30e0) - 15) / 86400.e0;
			$jjd -= $tetuj; // minus TE-TU before conversion to date
			$this->JJD = $jjd;
			if ($jjd < 2299160.5e0)
				$this->JJDATEJ();
			else
				$this->JJDATE();
			if ($this->Year == $code1)
				$this->conv($nn);
		}
	}
}

?>