<?php
class SufFixer{
	/*PHP 5.4 or higher */
	const ACC = "H";
	const DAT = "A";
	const LOC = "DA";
	const ABL = "DAn";
	const INS = "lA";
	const PLU = "lAr";
	public  $suffixes        = [self::ACC,self::DAT,self::LOC,self::ABL,self::INS,self::PLU];
	private $vowels          = ['a','ı','u','o','e','i','ü','ö'];
	private $frontvowels     = ['e','i','ü','ö'];
	private $backunrounded   = ['a','ı'];
	private $backrounded     = ['u','o'];
	private $frontunrounded  = ['e','i'];
	private $frontrounded    = ['ü','ö'];
	private $hardconsonant   = ['f','s','t','k','ç','ş','h','p'];
    private $roundvowels     = ["u","ü","o","ö"];
	private $H               = ['ı','i','u','ü'];
	private $numbers         = ["sıfır","bir","iki","üç","dört","beş","altı","yedi","sekiz","dokuz"];
	private $tens            = ["sıfır","on","yirmi","otuz","kırk","elli","altmış","yetmiş","seksen","doksan"];
	private $digits          = [0 => "yüz", 3 => "bin", 6 => "milyon", 9 => "milyar", 12 => "trilyon", 15 => "katrilyon"];
	private $consonantTuple  = [['g','k'],['ğ','k'],['b','p'],['c','ç'],['d','t']];
	private $translate_table = [["ae","A"],["ıiuü","H"]];
	private $superscript     = ["²" => "kare","³" => "küp"];
	private $sayi            = ["0","1","2","3","4","5","6","7","8","9"];
	private $dictionary;
	private $possesive;
	private $exceptions;
	private $haplology;
	private $others = [];
	private $updated  = [];
	private $posspath = "../sozluk/iyelik.txt";
	
	function __construct($dictpath = "../sozluk/kelimeler.txt", $exceptpath = "../sozluk/istisnalar.txt", $posspath = "../sozluk/iyelik.txt", 
								$haplopath = "../sozluk/unludusmesi.txt", $othpath = "../sozluk/digerleri.txt"){
		$this->posspath   = $posspath;
		$this->dictionary = file($dictpath  ,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->possesive  = file($posspath  ,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->exceptions = file($exceptpath,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->haplology  = file($haplopath ,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->dictionary = array_unique(array_merge($this->dictionary, $this->exceptions));
		$this->dictionary = array_unique(array_merge($this->dictionary, $this->haplology));
		$otherscontent = file($othpath ,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($otherscontent as $line){
			$l = turkishToLower($line);
			$match = preg_match("/(?P<abbr>\w+) +-> +(?<eqv>\w+)/u",$l,$matches);
			if ($match){
				$this->others[$matches["abbr"]] = $matches["eqv"];
			}
			else{
				$this->others[$l] = $l . "e";
			}		
		}
		
	}
	private function readNumber($number){
		$len = strlen($number);
		
		if ($len == 1 and $number === '0') return "sıfır";
		for ($i = $len - 1; $i >= 0; $i--){
			if ($number[$i] != '0' and is_numeric($number[$i]))
			{
				
				$n = intval($number[$i]);
				$i = $len - $i - 1;
		
				if ($i == 0) {
					return $this->numbers[$n];
				}
				else if ($i == 1){
					return $this->tens[$n];
				}
				else {
					$n = floor($i / 3) * 3;
					$n = $n > 15 ? 15 : $n;
					return $this->digits[$n];
					
				}
				
			}
		}
		return "sıfır";
	}
	private function divideWord($name, $suffix){
		$sufflen = strlen($suffix);
		$realsuffix = mb_substr($name,-$sufflen);
		$name = $sufflen > 0 ? mb_substr($name, 0,-$sufflen) : $name;
		if (in_array($name, $this->dictionary) or $this->checkConsonantHarmony($name, $suffix))
			yield array("",$name);
		else{
			$realword = $this->checkEllipsisAffix($name, $realsuffix);
			if ($realword !== "") yield array("",$realword);
		}
		$nmlen = mb_strlen($name);
		for($i = 2; $i < $nmlen - 1; $i++){
			$firstWord  = mb_substr($name,0,$i);
			$secondWord = mb_substr($name,$i);
			if (in_array($firstWord, $this->dictionary)){
				if (in_array($secondWord, $this->dictionary) or $this->checkConsonantHarmony($secondWord, $suffix))
					yield array($firstWord,$secondWord);
				else{
					$secondWord = $this->checkEllipsisAffix($secondWord, $realsuffix);
					if ($secondWord !== "") yield array($firstWord,$secondWord);
				}
			}
		}
				
		
	}
	private function checkEllipsisAffix($name,$realsuffix){
		if (!in_array($realsuffix, $this->H)) return "";
		$name = mb_substr($name,0,-1) . $realsuffix . mb_substr($name, -1);
		return in_array($name, $this->haplology) ? $name : "";
	}
	private function checkConsonantHarmony($name, $suffix){
		if ($suffix === "H"){
			$split_to_letters = mb_str_split($name);
			$lastletter = array_pop($split_to_letters);
			$rest = mb_substr($name, 0,-1);
			foreach ($this->consonantTuple as $letter){
				if ($lastletter === $letter[0] and in_array($rest . $letter[1], $this->dictionary))
					return true;
			}
		}
		return false;
	}
	private function checkVowelHarmony($name,$suffix){
		$lastVowelOfName = "";
		$isFrontVowel = False;
		if (in_array($name,$this->exceptions))
			$isFrontVowel = True;
		$split_to_letters = mb_str_split($name);
		$vowels_in_name = array_filter($split_to_letters, function($letter) {return in_array($letter,$this->vowels);});
		$lastVowelOfName = array_pop($vowels_in_name);
		// Not first vowel of suffix but the last one must follow first one so it doesn't matter
		$split_to_letters = mb_str_split($suffix);
		$vowels_in_suffix = array_filter($split_to_letters, function($letter) {return in_array($letter, $this->vowels);});
		$reverse = array_reverse($vowels_in_suffix);
		$firstVowelOfSuffix = array_pop($reverse);
		return ((in_array($lastVowelOfName, $this->frontvowels) or $isFrontVowel) === (in_array($firstVowelOfSuffix, $this->frontvowels))
		        && (in_array($lastVowelOfName, $this->roundvowels) == in_array($firstVowelOfSuffix,$this->roundvowels)));
	}
	private function surfacetolex($suffix){
		// TODO: ciddi performans kaybı
		foreach ($this->translate_table as $entry){
			foreach (mb_str_split($entry[0]) as $letter){
				$suffix = str_replace($letter, $entry[1], $suffix);
			}
		}
		return $suffix;
	}
	private function checkCompoundName($name){
		$possessivesuff = ["lArH","H","yH","sH"];
		$probablesuff = [];
		$namelen = mb_strlen($name);
		for($i = 1; $i < 5 and $i < $namelen; $i++){
			$temp = mb_substr($name,-$i);
			$probablesuff[$this->surfacetolex($temp)] = $temp;  
		}
		foreach ($possessivesuff as $posssuff){
			// TODO: performans iyileştirmesi
			if (array_key_exists($posssuff, $probablesuff)){
				$realsuffix = $probablesuff[$posssuff];
				$wordpairs = $this->divideWord($name, $posssuff);
				foreach($wordpairs as $wordpair){
					if($this->checkVowelHarmony($wordpair[1], $realsuffix)){
						array_push($this->updated, $name);
						array_push($this->possesive, $name);
						return true;
					}
				}
			}
		}
		return false;
	}
	private function checkExceptionalWord($name){
		foreach($this->divideWord($name,"") as $words){
			if ($words[1] !== "" and in_array($words[1],$this->exceptions))
				return true;
		}
		return false;
	}
	public function makeAccusative($name, $apostrophe = True){
		return $this->constructName($name, self::ACC, $apostrophe);
	}
	public function makeDative($name, $apostrophe = True){
		return $this->constructName($name, self::DAT, $apostrophe);
	}
	public function makeLocative($name, $apostrophe = True){
		return $this->constructName($name, self::LOC, $apostrophe);
	}
	public function makeAblative($name, $apostrophe = True){
		return $this->constructName($name, self::ABL, $apostrophe);
	}
	public function makeInstrumental($name, $apostrophe = True){
		return $this->constructName($name, self::INS, $apostrophe);
	}
	public function makePlural($name, $apostrophe = True){
		return $this->constructName($name, self::PLU, $apostrophe);
	}
	private function constructName($name, $suffix, $apostrophe){
		return sprintf("%s%s%s",$name,$apostrophe ? "'":"",$this->getSuffix($name,$suffix));
	}
	public function getSuffix($name, $suffix){

		$name = trim($name);
		if ($name === ""){
			throw new Exception("Not valid string!");
		}
		if(!in_array($suffix,$this->suffixes)){
			throw new Exception("Not valid suffix!");
		}
		$rawsuffix = $suffix;
		$soft = false;
		$split = explode(" ", $name);
		$wordNumber = count($split);
		$name = turkishToLower($split[$wordNumber - 1]);
		$namearray = mb_str_split($name);

		if (in_array(end($namearray),$this->H) and ($rawsuffix !== self::INS or $rawsuffix !== self::PLU) and
			($wordNumber > 1 or !in_array($name, $this->dictionary)) and (in_array($name, $this->possesive) or $this->checkCompoundName($name)))
				$suffix = 'n'.$suffix;
		else if (in_array(end($namearray), $this->sayi)){
				$name = $this->readNumber($name);
				$namearray = mb_str_split($name);
		}
		else if (in_array($name, $this->exceptions) or 
				 (!in_array($name, $this->dictionary) and $this->checkExceptionalWord($name)))
			$soft = true;
				 // TODO: isset kullan
		else if (array_key_exists($name, $this->others)){
			$name = $this->others[$name];
			$namearray = mb_str_split($name);
		}
		else if (array_key_exists(end($namearray), $this->superscript)){
			$name = $this->superscript[end($namearray)];
			$namearray = mb_str_split($name);
		}
		// TODO: name arrayinin güncellenmesi lazım
		// http://us2.php.net/manual/tr/function.str-split.php
		$vowels = array_filter($namearray, function($letter) {return in_array($letter,$this->vowels);});
		if (empty($vowels)){
			$lastVowel = 'e';
			$name = $name . 'e';
		}
		else 
			$lastVowel = array_pop($vowels);
		if (substr($suffix, -1) === "H"){
			$replacement = "";
			if (in_array($lastVowel, $this->frontrounded)  or ($soft and in_array($lastVowel, $this->backrounded)))
					$replacement = "ü";
			else if (in_array($lastVowel, $this->frontunrounded) or ($soft and in_array($lastVowel, $this->backunrounded)))
				$replacement = "i";
			else if (in_array($lastVowel, $this->backrounded))
				$replacement = "u";
			else
			    $replacement = "ı";
			$suffix = str_replace("H", $replacement, $suffix);
		}
		else {
			if (in_array($lastVowel, $this->frontvowels) or $soft)
				$suffix = str_replace("A","e",$suffix);
			else 
				$suffix = str_replace("A", "a", $suffix);
			if (in_array(mb_substr($name,-1),$this->hardconsonant))
				$suffix = str_replace("D", "t", $suffix);
			else
				$suffix = str_replace("D", "d", $suffix);
		}
		if (in_array(mb_substr($name,-1),$this->vowels) and
			(in_array(mb_substr($suffix, 0,1), $this->vowels) or $rawsuffix === self::INS))
			$suffix = "y" . $suffix;
		return $suffix;
	}
	function __destruct ()
	{
		if (!empty($this->updated)){
			$possfile = fopen($this->posspath, "a");
			foreach($this->updated as $newposs){
				fwrite($possfile, $newposs . "\n");
			}
			fclose($possfile);
		}
	}
}

function turkishToLower($name){
	$lcase_table = ['a','b','c','ç','d','e','f','g','ğ','h','ı','i','j','k','l','m','n','o','ö','p','r','s','ş','t','u','ü','v','y','z',"e","e","ü","ü","ö","ö"];
	$ucase_table = ['A','B','C','Ç','D','E','F','G','Ğ','H','I','İ','J','K','L','M','N','O','Ö','P','R','S','Ş','T','U','Ü','V','Y','Z',"Â","â","Û","û","Ô","ô"];
	$table = array_combine($ucase_table, $lcase_table);
	$result = "";
	foreach(mb_str_split($name) as $letter){
		if (array_key_exists($letter, $table))
			$result .= $table[$letter];
		else 
			$result .= $letter;
	}
	return $result;
}
function mb_str_split( $string ) {
	return preg_split('/(?<!^)(?!$)/u', $string );
}

?>
