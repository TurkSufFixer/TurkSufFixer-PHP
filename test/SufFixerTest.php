<?php

class SufFixerTest extends PHPUnit_Framework_TestCase
{
    private $simplewords = [];
    private $numbers     = [];
    private $exceptions  = [];
    private $consonant   = [];
    private $possesive   = [];
    private $others      = [];
    private $iyelik;
    /*
	 * @before
	 */
    public function setUp(){
        $testlist_name = ["simplewords","numbers","exceptions","consonantharmony","possesive","others"];
        $testlist_ref  = [&$this->simplewords, &$this->numbers, &$this->exceptions, &$this->consonant,&$this->possesive,&$this->others];
        $len = count($testlist_name);
        for($i = 0; $i < $len; $i++){
            $filename = "../test/tests/" . $testlist_name[$i];
            $lines = file($filename,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach($lines as $tline){
                $line = trim($tline);
                if (mb_strlen($line) === 0) continue;
                $spl = mb_split("=",$line);
                $name = trim($spl[0]);
                $temp = trim($spl[1]);
                $sfx  = mb_substr($temp, 1, mb_strlen($temp) - 2);
                $testlist_ref[$i][$name] = mb_split(",",$sfx);
            }
        }
        $this->iyelik = file_get_contents("../sozluk/iyelik.txt");
    }
    public function testSimplewords(){
        $this->baseTest($this->simplewords);
    }
    public function testNumbers(){
        $this->baseTest($this->numbers);
    }
    public function testExceptions(){
        $this->baseTest($this->exceptions);
    }
    public function testConsonantHarmony(){
        $this->baseTest($this->consonant);
    }
    public function testPossessive(){
        $this->baseTest($this->possesive);
    }
    public function testOthers(){
        $this->baseTest($this->others);
    }
    public function baseTest($namelist){
        $suffixes = ["H", "A", "DA", "DAn"];
        $sfxr = new SufFixer();
        foreach($namelist as $name => $correct_suffixes){
            foreach (array_combine($suffixes,$correct_suffixes) as $sfx => $correctsfx){
                $getSfx = $sfxr->getSuffix($name,$sfx);
                $this->assertEquals($getSfx,$correctsfx, "İsim ->$name");
            }
        }
    }
    public function tearDown(){
        file_put_contents("../sozluk/iyelik.txt", $this->iyelik);
    }
}
